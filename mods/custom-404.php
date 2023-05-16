<?php
add_action( 'template_redirect', 'fxwp_override_404' );
function fxwp_override_404() {
    if(is_404()){
        $selected_404_page_id = get_option('fxwp_404_page');
        if($selected_404_page_id){
            $redirect_url = get_permalink($selected_404_page_id);
            if($redirect_url){
                wp_redirect($redirect_url);
                exit();
            }
        }
    }
}
