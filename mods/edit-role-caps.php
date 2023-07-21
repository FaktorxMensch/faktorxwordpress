<?php

function fxm_edit_role_caps()
{
	$roleObject = get_role('editor');
	if (!$roleObject->has_cap('edit_theme_options')) {
		$roleObject->add_cap('edit_theme_options');
	}
}

add_action('admin_head', 'fxm_edit_role_caps');