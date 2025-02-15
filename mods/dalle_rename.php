<?php
/**
 * Filter, der den Dateinamen anpasst und gleichzeitig einen "schönen" Titel speichert.
 */
add_filter('sanitize_file_name', 'my_modify_image_filename', 10, 1);
function my_modify_image_filename($filename) {
    $pathinfo  = pathinfo($filename);
    $extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
    $name      = isset($pathinfo['filename']) ? $pathinfo['filename'] : '';

    // DALL*-Bilder: Entferne die ersten 29 Zeichen und unerwünschte Wörter.
    if ( stripos($name, 'dall') === 0 ) {
        $name = ltrim(substr($name, 28), ' _-');
        $name = str_ireplace('photorealistic', '', $name);
    }

    // Midjourney-Bilder: Falls mehr als ein Unterstrich enthalten ist, filtern.
    if ( substr_count($name, '_') > 1 ) {
        $parts = explode('_', $name);
        $filteredParts = array();

        foreach ($parts as $part) {
            if ( $part === '' ) {
                continue;
            }
            preg_match_all('/\d/', $part, $matches);
            $digitCount = count($matches[0]);
            if ( is_numeric($part) || $digitCount > 1 ) {
                continue;
            }
            $filteredParts[] = $part;
        }
        if ( !empty($filteredParts) ) {
            // Nutze Leerzeichen als Trenner für den "schönen" Titel.
            $name = implode(' ', $filteredParts);
        }
    }

    // Speichere den "schönen" Titel (mit Originalformatierung) in einer globalen Variable.
    $GLOBALS['pretty_attachment_title'] = trim($name);

    // Finaler Dateiname: Ersetze Leerzeichen durch Unterstriche.
    $finalName = str_replace(' ', '_', $name);

    // Falls mehr als 7 Unterstriche vorhanden sind, entferne das letzte Segment.
    $parts = explode('_', $finalName);
    if ( count($parts) > 7 ) {
        array_pop($parts);
        $finalName = implode('_', $parts);
    }

    // Ganze Zeichenkette in Kleinbuchstaben.
    $finalName = strtolower($finalName);

    if ( $extension ) {
        $finalName .= '.' . $extension;
    }

    return $finalName;
}

/**
 * Nach dem Anlegen des Attachment-Posts wird der Titel/Slug anhand des gespeicherten "schönen" Titels aktualisiert.
 */
add_action('add_attachment', 'my_update_attachment_title');
function my_update_attachment_title($post_ID) {
    if ( !empty($GLOBALS['pretty_attachment_title']) ) {
        $new_title = $GLOBALS['pretty_attachment_title'];
        $new_title = str_ireplace('photorealistic', '', $new_title);
        $new_title = trim($new_title);

        // Falls mehr als 7 Wörter vorhanden sind, entferne das letzte.
        $words = explode(' ', $new_title);
        if ( count($words) > 7 ) {
            array_pop($words);
            $new_title = implode(' ', $words);
        }

        $updated_post = array(
            'ID'         => $post_ID,
            'post_title' => $new_title,
            'post_name'  => sanitize_title($new_title)
        );
        wp_update_post($updated_post);

        // Aufräumen.
        unset($GLOBALS['pretty_attachment_title']);
    }
}
?>
