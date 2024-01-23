<?php
function fxwp_show_log($log)
{
    // have textarea with log
    echo '<textarea style="width: 100%; height: 300px;">';
    print_r($log);
    echo '</textarea>';
}

