<?php
add_filter('sanitize_file_name', 'modify_dall_filenames', 10, 1);

function modify_dall_filenames($filename) {
    $pathinfo = pathinfo($filename);
    $extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
    $name = isset($pathinfo['filename']) ? $pathinfo['filename'] : '';

    // Überprüfen Sie, ob der Dateiname mit "DALL" beginnt.
    if (strpos($name, 'DALL') === 0) {
        // Entfernen Sie die ersten 29 Zeichen vom Dateinamen.
        $new_name = substr($name, 29);
        $filename = $new_name . '.' . $extension;

        // and replace Photorealistic
        $filename = str_replace('Photorealistic', '', $filename);
    }

    return $filename;
}