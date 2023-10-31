<?php
add_filter('sanitize_file_name', 'modify_dall_filenames', 10, 1);

function modify_dall_filenames($filename) {
    $pathinfo = pathinfo($filename);
    $extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
    $name = isset($pathinfo['filename']) ? $pathinfo['filename'] : '';

    // Überprüfen Sie, ob der Dateiname mit "DALL" beginnt.
    if (strpos($name, 'DALL') === 0 || strpos($name, 'dall') === 0) {
        // Entfernen Sie die ersten 29 Zeichen vom Dateinamen.
        $new_name = substr($name, 26);
        $filename = $new_name . '.' . $extension;

        // and replace Photorealistic
        $filename = str_replace('photorealistic', '', $filename);
        $filename = str_replace('Photorealistic', '', $filename);
    }

    return $filename;
}

add_filter('wp_insert_attachment_data', 'modify_dall_attachment_title', 10, 2);

function modify_dall_attachment_title($data, $postarr) {
    $filename = $data['post_name'];  // Der Slug des Attachments, der oft dem Dateinamen entspricht.

    // Überprüfen Sie, ob der Dateiname mit "DALL" oder "dall" beginnt.
    if (strpos($filename, 'DALL') === 0 || strpos($filename, 'dall') === 0) {
        // Entfernen Sie die ersten 26 Zeichen vom Titel.
        $new_title = substr($data['post_title'], 26);

        // Ersetzen Sie "photorealistic" im Titel.
        $new_title = str_replace('photorealistic', '', $new_title);

        $data['post_title'] = $new_title;
    }

    return $data;
}
