<?php
function fxwp_google_fonts_mod()
{


    // if option fxwp_google_fonts_remove == 'einfach' or 'aggresiv'
    $google_fonts_remove = get_option('fxwp_google_fonts_remove', 'nein');
    if ($google_fonts_remove == 'nein')
        return;


    // if option fxwp_google_fonts_remove == 'einfach' or 'aggresiv'
    remove_action('wp_enqueue_scripts', 'fxwp_enqueue_google_fonts', 9999);

    if ($google_fonts_remove == 'einfach')
        return;

    // if option fxwp_google_fonts_remove == 'aggresiv'
    ob_start("fxwp_callback");
}

function fxwp_callback($buffer)
{
    $buffer = str_replace("https://fonts.googleapis.com/", "", $buffer);
    return $buffer;
}


// call the function at the very end of the file
add_action('wp_enqueue_scripts', 'fxwp_google_fonts_mod', 9999);
