<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Off-site backup copy to S3-compatible storage.
 *
 * Design goals (per requirements):
 *  - Minimal memory: large archives are uploaded with a resumable multipart
 *    upload, reading one part (~16 MB) at a time; small files stream directly
 *    from disk via cURL (≈0 bytes buffered). The SigV4 payload hash for whole
 *    files uses hash_file() (streaming), never file_get_contents().
 *  - Secrets stay secret: credentials come from wp-config constants if defined,
 *    otherwise from options where the SECRET key is stored AES-256 encrypted
 *    with a key derived from the site's wp-config salts. Nothing S3-related is
 *    registered in options-config.php, so it is never exposed via the generic
 *    fx_plugin_get_options AJAX or echoed to the browser.
 *  - Correctness: AWS Signature Version 4 (path-style), resumable across the
 *    backup job's slices, and S3 failures never invalidate the (already
 *    verified) local backup.
 *
 * Optional wp-config.php constants (most secure):
 *   define('FXWP_S3_ENDPOINT',   'https://s3.example.com');
 *   define('FXWP_S3_REGION',     'us-east-1');   // many providers accept this
 *   define('FXWP_S3_BUCKET',     'my-bucket');
 *   define('FXWP_S3_ACCESS_KEY', '...');
 *   define('FXWP_S3_SECRET_KEY', '...');
 *   define('FXWP_S3_PREFIX',     'optional/path/');
 */

/* -------------------------------------------------------------------------- */
/*  Configuration & secrets                                                    */
/* -------------------------------------------------------------------------- */

function fxwp_s3_get_value($optionKey, $constName)
{
    if (defined($constName) && constant($constName) !== '' && constant($constName) !== false) {
        return (string)constant($constName);
    }
    return (string)get_option($optionKey, '');
}

function fxwp_s3_config()
{
    $endpoint = rtrim(fxwp_s3_get_value('fxwp_s3_endpoint', 'FXWP_S3_ENDPOINT'), '/');

    $region = fxwp_s3_get_value('fxwp_s3_region', 'FXWP_S3_REGION');
    if ($region === '') {
        $region = 'eu-central-1';
    }

    $bucket = fxwp_s3_get_value('fxwp_s3_bucket', 'FXWP_S3_BUCKET');
    $access = fxwp_s3_get_value('fxwp_s3_access_key', 'FXWP_S3_ACCESS_KEY');

    if (defined('FXWP_S3_SECRET_KEY') && FXWP_S3_SECRET_KEY !== '') {
        $secret = (string)FXWP_S3_SECRET_KEY;
    } else {
        $secret = fxwp_s3_decrypt((string)get_option('fxwp_s3_secret_key_enc', ''));
    }

    $prefix = fxwp_s3_get_value('fxwp_s3_prefix', 'FXWP_S3_PREFIX');
    if ($prefix === '') {
        // Default prefix = site host, so one bucket can hold many sites tidily.
        $prefix = preg_replace('#^https?://#', '', rtrim(get_site_url(), '/'));
    }
    $prefix = trim($prefix, '/');
    if ($prefix !== '') {
        $prefix .= '/';
    }

    return compact('endpoint', 'region', 'bucket', 'access', 'secret', 'prefix');
}

function fxwp_s3_enabled()
{
    $c = fxwp_s3_config();
    return $c['endpoint'] !== '' && $c['bucket'] !== '' && $c['access'] !== '' && $c['secret'] !== '';
}

/**
 * Extract the calendar month ("YYYY-MM") from a backup base name
 * ("backup_YYYY-MM-DD_HH-ii-ss").
 */
function fxwp_s3_backup_month($base)
{
    if (preg_match('/(\d{4}-\d{2})-\d{2}_/', (string)$base, $m)) {
        return $m[1];
    }
    return current_time('Y-m');
}

/**
 * Extract the calendar day ("YYYY-MM-DD") from a backup base name.
 */
function fxwp_s3_backup_day($base)
{
    if (preg_match('/(\d{4}-\d{2}-\d{2})_/', (string)$base, $m)) {
        return $m[1];
    }
    return current_time('Y-m-d');
}

/**
 * Decide whether (and as which tier) THIS backup should be copied off-site.
 *
 * Tiers map to distinct key prefixes so AWS lifecycle rules can expire them
 * differently (e.g. father/ after 30 days, grandfather/ after a year):
 *   - grandfather: the first successful backup of each calendar month
 *   - father:      the first successful backup of each calendar day
 *
 * Markers are only advanced on a *successful* upload (see the upload phase), so
 * a failed upload is retried by the next backup in the same window.
 *
 * Modes: "tiered" (default, father + grandfather), "monthly" (grandfather only),
 * "all" (every backup, no tier folder).
 *
 * @return array{upload:bool,tier:string}
 */
function fxwp_s3_upload_plan($base)
{
    $mode = get_option('fxwp_s3_upload_mode', 'tiered');
    if ($mode === 'all') {
        return array('upload' => true, 'tier' => 'all');
    }

    $month = fxwp_s3_backup_month($base);
    if ($month !== '' && $month !== (string)get_option('fxwp_s3_last_uploaded_month', '')) {
        return array('upload' => true, 'tier' => 'grandfather');
    }

    if ($mode === 'tiered') {
        $day = fxwp_s3_backup_day($base);
        if ($day !== '' && $day !== (string)get_option('fxwp_s3_last_uploaded_day', '')) {
            return array('upload' => true, 'tier' => 'father');
        }
    }

    return array('upload' => false, 'tier' => '');
}

/**
 * Storage class for a tier. Defaults are cost-aware: the long-lived grandfather
 * goes to GLACIER, but the short-lived father uses STANDARD_IA -- putting a
 * 30-day father into Glacier would trigger Glacier's 90-day minimum-duration
 * charge (you'd pay for 90 days to keep it 30).
 */
function fxwp_s3_storage_class_for($tier)
{
    if ($tier === 'grandfather') {
        return get_option('fxwp_s3_class_grandfather', 'GLACIER');
    }
    if ($tier === 'father') {
        return get_option('fxwp_s3_class_father', 'STANDARD_IA');
    }
    return get_option('fxwp_s3_class_default', 'STANDARD');
}

/**
 * Encryption key derived from the site's salts (live in wp-config.php, not the
 * DB) so a leaked database dump alone cannot decrypt the stored secret.
 */
function fxwp_s3_crypto_key()
{
    return hash('sha256', wp_salt('secure_auth'), true);
}

function fxwp_s3_encrypt($plain)
{
    if ($plain === '') {
        return '';
    }
    if (!function_exists('openssl_encrypt')) {
        return ''; // refuse to store unencrypted; use wp-config constant instead
    }
    $iv = random_bytes(16);
    $ct = openssl_encrypt($plain, 'aes-256-cbc', fxwp_s3_crypto_key(), OPENSSL_RAW_DATA, $iv);
    if ($ct === false) {
        return '';
    }
    return 'v1:' . base64_encode($iv . $ct);
}

function fxwp_s3_decrypt($enc)
{
    if ($enc === '' || strpos($enc, 'v1:') !== 0 || !function_exists('openssl_decrypt')) {
        return '';
    }
    $raw = base64_decode(substr($enc, 3), true);
    if ($raw === false || strlen($raw) < 17) {
        return '';
    }
    $iv = substr($raw, 0, 16);
    $ct = substr($raw, 16);
    $pt = openssl_decrypt($ct, 'aes-256-cbc', fxwp_s3_crypto_key(), OPENSSL_RAW_DATA, $iv);
    return $pt === false ? '' : $pt;
}

/* -------------------------------------------------------------------------- */
/*  Upload phase (called from the backup job's process loop)                   */
/* -------------------------------------------------------------------------- */

/**
 * Resumable S3 upload phase. Advances within the slice's time budget and is
 * re-entered across slices until the upload completes. Errors are handled here
 * (logged + emailed, multipart aborted) and never re-thrown, so a failed
 * off-site copy does not mark the verified local backup as failed.
 */
function fxwp_s3_upload_phase(&$state, $backupDir, $backupFile, $dumpFile, $deadline)
{
    $cfg = array();
    try {
        if (!fxwp_s3_enabled()) {
            $state['active'] = false;
            return;
        }
        $cfg = fxwp_s3_config();

        if (empty($state['s3'])) {
            $size = (int)@filesize($backupFile);
            $partSize = max(5 * 1024 * 1024, (int)get_option('fxwp_s3_part_size', 16 * 1024 * 1024));
            // Tier becomes a top-level key folder (father/ , grandfather/) so AWS
            // lifecycle rules can expire each tier on its own schedule across all
            // sites. "all" mode uses no tier folder.
            $tier = isset($state['s3_tier']) ? $state['s3_tier'] : '';
            $tierFolder = ($tier === 'father' || $tier === 'grandfather') ? $tier . '/' : '';
            $state['s3'] = array(
                'stage'         => $size <= $partSize ? 'zip_single' : 'zip_init',
                'upload_id'     => '',
                'part_number'   => 1,
                'offset'        => 0,
                'parts'         => array(),
                'zip_key'       => $tierFolder . $cfg['prefix'] . basename($backupFile),
                'sql_key'       => $tierFolder . $cfg['prefix'] . basename($dumpFile),
                'zip_size'      => $size,
                'part_size'     => $partSize,
                'storage_class' => fxwp_s3_storage_class_for($tier),
            );
        }
        $s = &$state['s3'];

        // Small archive: one streamed PUT.
        if ($s['stage'] === 'zip_single') {
            fxwp_s3_put_file($cfg, $s['zip_key'], $backupFile, $s['storage_class']);
            $s['stage'] = 'sql';
        }

        // Large archive: initialise the multipart upload (storage class is set
        // here, on the resulting object, not on the individual parts).
        if ($s['stage'] === 'zip_init') {
            $s['upload_id'] = fxwp_s3_multipart_create($cfg, $s['zip_key'], $s['storage_class']);
            $s['stage'] = 'zip';
        }

        // Large archive: upload parts (resumable, one part at a time in memory).
        if ($s['stage'] === 'zip') {
            $fh = fopen($backupFile, 'rb');
            if (!$fh) {
                throw new Exception('cannot open archive for upload: ' . $backupFile);
            }
            while ($s['offset'] < $s['zip_size']) {
                if ($deadline && microtime(true) >= $deadline) {
                    fclose($fh);
                    return; // resume next slice
                }
                fseek($fh, $s['offset']);
                $len = (int)min($s['part_size'], $s['zip_size'] - $s['offset']);
                $chunk = fread($fh, $len);
                if ($chunk === false || strlen($chunk) === 0) {
                    fclose($fh);
                    throw new Exception('read error at offset ' . $s['offset']);
                }
                $etag = fxwp_s3_multipart_upload_part($cfg, $s['zip_key'], $s['upload_id'], $s['part_number'], $chunk);
                $s['parts'][$s['part_number']] = $etag;
                $s['offset'] += strlen($chunk);
                $s['part_number']++;
                update_option('fxwp_backup_state', $state); // persist progress per part
            }
            fclose($fh);
            fxwp_s3_multipart_complete($cfg, $s['zip_key'], $s['upload_id'], $s['parts']);
            $s['stage'] = 'sql';
        }

        // Database dump (small): one streamed PUT, same storage class.
        if ($s['stage'] === 'sql') {
            if (file_exists($dumpFile) && filesize($dumpFile) > 0) {
                fxwp_s3_put_file($cfg, $s['sql_key'], $dumpFile, $s['storage_class']);
            }
            $s['stage'] = 'done';
        }

        if ($s['stage'] === 'done') {
            update_option('fxwp_s3_last_upload', time());
            // Advance the tier marker only now that the upload truly succeeded.
            $tier = isset($state['s3_tier']) ? $state['s3_tier'] : '';
            $base = isset($state['base']) ? $state['base'] : '';
            $zipKey = $s['zip_key'];
            if ($tier === 'grandfather') {
                update_option('fxwp_s3_last_uploaded_month', fxwp_s3_backup_month($base));
            } elseif ($tier === 'father') {
                update_option('fxwp_s3_last_uploaded_day', fxwp_s3_backup_day($base));
            }
            delete_option('fxwp_s3_last_error');
            unset($state['s3'], $state['s3_tier']);
            $state['active'] = false;
            error_log('fxwp s3 upload completed (' . $tier . '): ' . $zipKey);
        }
    } catch (\Throwable $e) {
        if (!empty($cfg) && !empty($state['s3']['upload_id'])) {
            @fxwp_s3_multipart_abort($cfg, $state['s3']['zip_key'], $state['s3']['upload_id']);
        }
        update_option('fxwp_s3_last_error', gmdate('c') . ' ' . $e->getMessage());
        error_log('fxwp s3 upload failed: ' . $e->getMessage());
        fxwp_s3_notify_failure($e->getMessage());
        unset($state['s3']);
        $state['active'] = false; // local backup remains valid
    }
}

function fxwp_s3_notify_failure($message)
{
    // Throttle to at most one email per day per site.
    $last = (int)get_option('fxwp_s3_last_fail_mail', 0);
    if (time() - $last < DAY_IN_SECONDS) {
        return;
    }
    update_option('fxwp_s3_last_fail_mail', time());
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail(
        FXWP_ERROR_EMAIL,
        'Off-site backup upload failed on ' . get_site_url(),
        'The local backup succeeded but the off-site (S3) upload failed on '
        . get_site_url() . ':<br><br>' . esc_html($message),
        $headers
    );
}

/* -------------------------------------------------------------------------- */
/*  S3 operations                                                              */
/* -------------------------------------------------------------------------- */

function fxwp_s3_put_file($cfg, $key, $path, $storageClass = '')
{
    $size = (int)filesize($path);
    $hash = hash_file('sha256', $path); // streaming, low memory
    $fh = fopen($path, 'rb');
    if (!$fh) {
        throw new Exception('cannot open file for upload: ' . $path);
    }
    $headers = array();
    if ($storageClass !== '') {
        $headers['x-amz-storage-class'] = $storageClass;
    }
    $res = fxwp_s3_curl($cfg, 'PUT', $key, array(), $headers, $hash, null, $fh, $size);
    fclose($fh);
    if ($res['code'] < 200 || $res['code'] >= 300) {
        throw new Exception('PUT ' . $key . ' -> HTTP ' . $res['code'] . ' ' . substr($res['body'], 0, 300));
    }
    return true;
}

function fxwp_s3_multipart_create($cfg, $key, $storageClass = '')
{
    $headers = array();
    if ($storageClass !== '') {
        $headers['x-amz-storage-class'] = $storageClass;
    }
    $res = fxwp_s3_curl($cfg, 'POST', $key, array('uploads' => ''), $headers, hash('sha256', ''), '', null, null);
    if ($res['code'] < 200 || $res['code'] >= 300) {
        throw new Exception('create multipart -> HTTP ' . $res['code'] . ' ' . substr($res['body'], 0, 300));
    }
    if (!preg_match('#<UploadId>(.*?)</UploadId>#', $res['body'], $m)) {
        throw new Exception('no UploadId in create-multipart response');
    }
    return $m[1];
}

function fxwp_s3_multipart_upload_part($cfg, $key, $uploadId, $partNumber, $chunk)
{
    $hash = hash('sha256', $chunk);
    $query = array('partNumber' => (string)$partNumber, 'uploadId' => $uploadId);
    $res = fxwp_s3_curl($cfg, 'PUT', $key, $query, array('content-type' => 'application/octet-stream'), $hash, $chunk, null, null);
    if ($res['code'] < 200 || $res['code'] >= 300) {
        throw new Exception('upload part ' . $partNumber . ' -> HTTP ' . $res['code'] . ' ' . substr($res['body'], 0, 300));
    }
    $etag = isset($res['headers']['etag']) ? trim($res['headers']['etag'], '"') : '';
    if ($etag === '') {
        throw new Exception('missing ETag for part ' . $partNumber);
    }
    return $etag;
}

function fxwp_s3_multipart_complete($cfg, $key, $uploadId, $parts)
{
    ksort($parts, SORT_NUMERIC);
    $xml = '<CompleteMultipartUpload>';
    foreach ($parts as $num => $etag) {
        $xml .= '<Part><PartNumber>' . (int)$num . '</PartNumber><ETag>"' . $etag . '"</ETag></Part>';
    }
    $xml .= '</CompleteMultipartUpload>';

    $res = fxwp_s3_curl($cfg, 'POST', $key, array('uploadId' => $uploadId), array('content-type' => 'application/xml'), hash('sha256', $xml), $xml, null, null);
    if ($res['code'] < 200 || $res['code'] >= 300) {
        throw new Exception('complete multipart -> HTTP ' . $res['code'] . ' ' . substr($res['body'], 0, 300));
    }
    // S3 may return 200 with an <Error> body on completion failure.
    if (strpos($res['body'], '<Error>') !== false) {
        throw new Exception('complete multipart error: ' . substr($res['body'], 0, 300));
    }
    return true;
}

function fxwp_s3_multipart_abort($cfg, $key, $uploadId)
{
    return fxwp_s3_curl($cfg, 'DELETE', $key, array('uploadId' => $uploadId), array(), hash('sha256', ''), null, null, null);
}

/**
 * Signed S3 request (AWS Signature V4, path-style addressing).
 *
 * @param resource|null $infile     Open read handle to stream as the PUT body.
 * @param int|null      $infileSize Size for a streamed body.
 * @return array{code:int,body:string,headers:array<string,string>}
 */
function fxwp_s3_curl($cfg, $method, $key, array $query, array $extraHeaders, $payloadHash, $body, $infile, $infileSize)
{
    $p = parse_url($cfg['endpoint']);
    $scheme = isset($p['scheme']) ? $p['scheme'] : 'https';
    $host = isset($p['host']) ? $p['host'] : '';
    $port = isset($p['port']) ? $p['port'] : null;
    $hostHeader = $host . ($port ? ':' . $port : '');

    // Canonical URI: /bucket/key. S3 encodes each path segment once and keeps
    // the slashes; rawurlencode matches the required RFC-3986 encoding.
    $encodedKey = implode('/', array_map('rawurlencode', explode('/', $key)));
    $canonicalUri = '/' . rawurlencode($cfg['bucket']) . '/' . $encodedKey;

    ksort($query);
    $qparts = array();
    foreach ($query as $k => $v) {
        $qparts[] = rawurlencode($k) . '=' . rawurlencode((string)$v);
    }
    $canonicalQuery = implode('&', $qparts);

    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');

    $signedHeadersMap = array(
        'host'                 => $hostHeader,
        'x-amz-content-sha256' => $payloadHash,
        'x-amz-date'           => $amzDate,
    );
    // Extra headers (e.g. x-amz-storage-class, content-type) must be *signed* --
    // S3 rejects requests where an x-amz-* header is present but not signed.
    foreach ($extraHeaders as $hk => $hv) {
        $signedHeadersMap[strtolower($hk)] = trim((string)$hv);
    }
    ksort($signedHeadersMap);
    $canonicalHeaders = '';
    foreach ($signedHeadersMap as $hk => $hv) {
        $canonicalHeaders .= $hk . ':' . $hv . "\n";
    }
    $signedHeaders = implode(';', array_keys($signedHeadersMap));

    $canonicalRequest = $method . "\n" . $canonicalUri . "\n" . $canonicalQuery . "\n"
        . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;

    $scope = $dateStamp . '/' . $cfg['region'] . '/s3/aws4_request';
    $stringToSign = "AWS4-HMAC-SHA256\n" . $amzDate . "\n" . $scope . "\n" . hash('sha256', $canonicalRequest);

    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $cfg['secret'], true);
    $kRegion = hash_hmac('sha256', $cfg['region'], $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $cfg['access'] . '/' . $scope
        . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

    $url = $scheme . '://' . $hostHeader . $canonicalUri . ($canonicalQuery !== '' ? '?' . $canonicalQuery : '');

    $headers = array(
        'Authorization: ' . $authorization,
        'Expect:', // avoid 100-continue stalls on some servers
    );
    foreach ($signedHeadersMap as $hk => $hv) {
        $headers[] = ($hk === 'host' ? 'Host' : $hk) . ': ' . $hv;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);

    $respHeaders = array();
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($c, $h) use (&$respHeaders) {
        $parts = explode(':', $h, 2);
        if (count($parts) === 2) {
            $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
        }
        return strlen($h);
    });

    if ($method === 'PUT' && $infile) {
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_INFILE, $infile);
        curl_setopt($ch, CURLOPT_INFILESIZE, $infileSize);
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body === null ? '' : $body);
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body === null ? '' : $body);
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $respBody = curl_exec($ch);
    if ($respBody === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL error: ' . $err);
    }
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array('code' => $code, 'body' => (string)$respBody, 'headers' => $respHeaders);
}

/* -------------------------------------------------------------------------- */
/*  Settings (fxm_admin only) & connection test                               */
/* -------------------------------------------------------------------------- */

/**
 * Persist S3 settings from the Archiv page form. The secret field is write-only:
 * it is only updated when a new value is submitted, and is stored encrypted.
 */
function fxwp_s3_handle_settings_post()
{
    if (empty($_POST['fxwp_s3_settings_nonce']) || !current_user_can('fxm_admin')) {
        return;
    }
    if (!wp_verify_nonce($_POST['fxwp_s3_settings_nonce'], 'fxwp_s3_settings')) {
        return;
    }

    update_option('fxwp_s3_endpoint', esc_url_raw(trim(wp_unslash($_POST['fxwp_s3_endpoint'] ?? ''))), false);
    update_option('fxwp_s3_region', sanitize_text_field(wp_unslash($_POST['fxwp_s3_region'] ?? '')), false);
    update_option('fxwp_s3_bucket', sanitize_text_field(wp_unslash($_POST['fxwp_s3_bucket'] ?? '')), false);
    update_option('fxwp_s3_prefix', sanitize_text_field(wp_unslash($_POST['fxwp_s3_prefix'] ?? '')), false);
    update_option('fxwp_s3_access_key', sanitize_text_field(wp_unslash($_POST['fxwp_s3_access_key'] ?? '')), false);

    $mode = in_array($_POST['fxwp_s3_upload_mode'] ?? '', array('tiered', 'monthly', 'all'), true)
        ? $_POST['fxwp_s3_upload_mode'] : 'tiered';
    update_option('fxwp_s3_upload_mode', $mode, false);

    $classes = array('STANDARD', 'STANDARD_IA', 'ONEZONE_IA', 'INTELLIGENT_TIERING', 'GLACIER_IR', 'GLACIER', 'DEEP_ARCHIVE');
    $father = (string)($_POST['fxwp_s3_class_father'] ?? '');
    $grand = (string)($_POST['fxwp_s3_class_grandfather'] ?? '');
    if (in_array($father, $classes, true)) {
        update_option('fxwp_s3_class_father', $father, false);
    }
    if (in_array($grand, $classes, true)) {
        update_option('fxwp_s3_class_grandfather', $grand, false);
    }

    $secret = isset($_POST['fxwp_s3_secret_key']) ? trim(wp_unslash($_POST['fxwp_s3_secret_key'])) : '';
    if ($secret !== '') {
        update_option('fxwp_s3_secret_key_enc', fxwp_s3_encrypt($secret), false);
    }
}

function fxwp_s3_test()
{
    if (!fxwp_s3_enabled()) {
        return array('ok' => false, 'message' => 'S3 ist nicht vollständig konfiguriert.');
    }
    try {
        $cfg = fxwp_s3_config();
        $key = $cfg['prefix'] . 'fxwp-s3-test.txt';
        $body = 'fxwp connection test ' . gmdate('c');
        $put = fxwp_s3_curl($cfg, 'PUT', $key, array(), array('content-type' => 'text/plain'), hash('sha256', $body), $body, null, null);
        if ($put['code'] < 200 || $put['code'] >= 300) {
            return array('ok' => false, 'message' => 'PUT HTTP ' . $put['code'] . ' ' . substr($put['body'], 0, 200));
        }
        // Best-effort cleanup. With a recommended write-only IAM policy the DELETE
        // may be denied -- that's fine, the upload itself works.
        $del = fxwp_s3_curl($cfg, 'DELETE', $key, array(), array(), hash('sha256', ''), null, null, null);
        if ($del['code'] >= 200 && $del['code'] < 300) {
            return array('ok' => true, 'message' => 'Verbindung OK (Test-Objekt geschrieben und gelöscht).');
        }
        return array('ok' => true, 'message' => 'Verbindung OK – Upload funktioniert. Test-Objekt konnte nicht gelöscht werden (vermutlich write-only Rechte); ggf. „' . $key . '" manuell entfernen.');
    } catch (\Throwable $e) {
        return array('ok' => false, 'message' => $e->getMessage());
    }
}
