<?php
// auto_prepend_file for tests/entrypoints.php: gives a CLI run of monitor.php the
// form fields a web request would have.
$fxwp_test_post = getenv('FXWP_TEST_POST');
if ($fxwp_test_post !== false && $fxwp_test_post !== '') {
    $_POST = json_decode($fxwp_test_post, true);
}
unset($fxwp_test_post);
