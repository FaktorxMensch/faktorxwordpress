<?php

function fxwp_debug_log_widget() {
	$log = file_get_contents( WP_CONTENT_DIR . '/debug.log' );
	if ( ! current_user_can( 'fxm_admin' )  || fxwp_check_deactivated_features('fxwp_deact_debug_log_widget') || empty( $log ) ) {
		return;
	}
	echo '<div class="debug_log"><div class="fullscreen_layer">
	<pre>';
	//if file is empty or not exists, show default message
	if ( empty( $log ) ) {
		echo 'No log entries yet.';
	}
	$logEntries = explode( "\n", $log );
	$logEntries = array_reverse( $logEntries );
	$logEntries = array_slice( $logEntries, 0, 100 );
	$levelColor = array("Notice" => "#00a0d2", "Warning" => "#ffb900", "Fatal error" => "#ff3333", "Parse error" => "#ff8b4d", "" => "unset");
	//Split each line into [date], [level], [message]. Make sure to have exact regex, line looks like [24-Jan-2024 09:13:33 UTC] PHP Notice:  Function map_meta_cap was called incorrectly. The post type shop_order is not registered, so it may not be reliable to check the capability edit_post against a post of that type. Please see Debugging in WordPress for more information. (This message was added in version 4.4.0.) in /Users/ema/Local Sites/cornelia-tests/app/public/wp-includes/functions.php on line 6031
	foreach ( $logEntries as $key => $line ) {
		// Regular expression to match the pattern
		$pattern = '/\[(.*?)\](?: PHP (.*?):)?\s+(.*)/';
		if ( preg_match( $pattern, $line, $matches ) ) {
			// Extracting date, level, and the rest of the message
			$date = strtotime($matches[1]);
			$level = $matches[2];
			$field = $matches[3];
			// Reformatting the line
//			$logEntries[$key] = "Date: ".date(DATE_ATOM,$date).", Level: $level, Message: $field";
			$logEntries[$key] = "[".date(DATE_ATOM,$date)."] ";
			$logEntries[$key] .= ($level!=="") ? "<span style='color: $levelColor[$level]'>PHP $level</span> " : "";
			$logEntries[$key] .= "$field";
		} else {
			// Handle lines that do not match the pattern
			$logEntries[$key] = $line;
		}
	}
	//Add lines back together
	$log = implode( "\n", $logEntries );
	echo nl2br( $log );
    ?>
	</pre></div></div>
	<style>
			div.debug_log pre {background-color: #1c1b22;
                color: #fff;
                padding: 10px;
                margin-bottom: 20px;
                overflow: scroll;
                max-height: 40em;
                line-height: 1em;
			}
			div.debug_log .debug_log_arrow {
                position: absolute;
                top: 10px;
                right: 20px;
            }
            div.debug_log .debug_log_arrow::before {
                display: inline-block;
                /*svg "fullscreen", showing two arrows pointing to top right and bottom left*/
                content: url("data:image/svg+xml,%3Csvg%20fill%3D%27%23fff%27%20height%3D%2720px%27%20width%3D%2720px%27%20viewBox%3D%270%200%2024%2024%27%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20%3E%20%3Cpath%20d%3D%27M0%2024v-9h2v5.6l7.3-7.3%201.4%201.4-7.3%207.3h5.6v2h-9zM24%200v9h-2v-5.6l-7.3%207.3-1.4-1.4%207.3-7.3h-5.6v-2h9z%27%2F%3E%20%3C%2Fsvg%3E");
                width: 20px;
                height: 20px;
            }
            div.debug_log .debug_log_arrow:hover {cursor: pointer;}
            div.debug_log_fullscreen {
                position: fixed;
                z-index: 9997;
                max-height: unset;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                -webkit-backdrop-filter: blur(15px);
                backdrop-filter: blur(15px);
            }
            div.debug_log_fullscreen .fullscreen_layer {
                position: relative;
                top: 30px;
                right: 30px;
                left: 30px;
                bottom: 30px;
                max-width: calc(100% - 60px);
                max-height: calc(100% - 60px);
                z-index: 9998;
                overflow: scroll;
            }
            div.debug_log_fullscreen .fullscreen_layer pre {
                max-height: unset;
                padding: 0 20px 20px 20px;
            }
            div.debug_log_fullscreen .debug_log_arrow_fullscreen {
                position: fixed;
                top: 60px;
                right: 60px;
                z-index: 9999;
            }
            div.debug_log_fullscreen .debug_log_arrow_fullscreen::before {
                /*Inverted arrows. "Close fullscreen" */
                content: url("data:image/svg+xml,%3Csvg%20fill%3D%27%23fff%27%20height%3D%2720px%27%20width%3D%2720px%27%20viewBox%3D%270%200%2024%2024%27%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20%3E%20%3Cpath%20d%3D%27M14%2010v-9h2v5.6l7.3-7.3%201.4%201.4-7.3%207.3h5.6v2h-9zM10%2014v9h-2v-5.6l-7.3%207.3-1.4-1.4%207.3-7.3h-5.6v-2h9z%27%2F%3E%20%3C%2Fsvg%3E");
            }
    </style>
	<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Insert arrow icon
            var debugLog = document.querySelector('.debug_log');
            var debugLogArrow = document.createElement('div');
            debugLogArrow.className = 'debug_log_arrow';
            debugLog.prepend(debugLogArrow);

            var escapeHandler = function(e) {
                if (e.key === 'Escape') {
                    if (debugLog.classList.contains('debug_log_fullscreen')) {
                        debugLogArrow.click();
                    }
                }
            };
            var fullscreenCloser = function (e) {
                // if self is not target, dont prevent default
                if (e.target !== e.currentTarget) {
                    return;
                }
                var debugLog = document.querySelector('.debug_log');
                if (debugLog.classList.contains('debug_log_fullscreen')) {
                    debugLogArrow.click();
                }
            }
            document.addEventListener('keyup', escapeHandler);

            debugLogArrow.addEventListener('click', function(e) {
                // var debugLog = document.querySelector('.debug_log');
                debugLog.classList.toggle('debug_log_fullscreen');
                debugLogArrow.classList.toggle('debug_log_arrow_fullscreen');
            });
            debugLog.addEventListener('click', fullscreenCloser);

        });
    </script>
    <div>
    <?php
    echo '<form action="'. esc_url(admin_url('admin-post.php')) .'" method="post" style="display:flex;gap:4px;align-items:center;width:100%">';
    echo '<input type="hidden" name="action" value="fxwp_log_clear">';
    wp_nonce_field('fxwp_debug_log_clear_action', 'fxwp_debug_log_clear_nonce');
    echo '<input type="submit" value="' . esc_attr__('Log leeren', 'fxwp') . '" class="button button-secondary" style="margin-top: 20px" />';
    echo '</form>';
    ?>
    </div>
    <?php
}

function fxwp_register_debug_log_widget()
{
	$log = file_get_contents( WP_CONTENT_DIR . '/debug.log' );
	if (!current_user_can('fxm_admin')  || fxwp_check_deactivated_features('fxwp_deact_debug_log_widget') || empty( $log )) {
		return;
	}

	wp_add_dashboard_widget(
		'fxwp_debug_log_widget', // Widget slug.
		'Debug Log', // Title.
		'fxwp_debug_log_widget' // Display function.
	);
}

add_action('wp_dashboard_setup', 'fxwp_register_debug_log_widget');

function fxwp_handle_debug_log_clear() {
	// verify the nonce
	if (!isset($_POST['fxwp_debug_log_clear_nonce']) || !wp_verify_nonce($_POST['fxwp_debug_log_clear_nonce'], 'fxwp_debug_log_clear_action')) {
		wp_die('Security check fail');
	}
    //Clear log
    file_put_contents( WP_CONTENT_DIR . '/debug.log', "");
    error_log("User cleared log.");
    //Redirect to dashboard
    wp_redirect(admin_url());
	exit();
}
add_action('admin_post_fxwp_log_clear', 'fxwp_handle_debug_log_clear');