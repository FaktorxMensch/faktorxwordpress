<?php
// Minimal test harness (no Composer / PHPUnit needed, so the hook runs anywhere PHP does).
if (PHP_SAPI !== 'cli') { exit; }

$GLOBALS['__t'] = array('pass' => 0, 'fail' => 0, 'failures' => array());

function t(string $name, callable $fn): void
{
    try {
        $fn();
        $GLOBALS['__t']['pass']++;
        echo "  ok    $name\n";
    } catch (Throwable $e) {
        $GLOBALS['__t']['fail']++;
        $GLOBALS['__t']['failures'][] = $name;
        echo "  FAIL  $name\n        " . get_class($e) . ': ' . str_replace("\n", "\n        ", $e->getMessage())
            . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ")\n";
    }
}

class TestFailure extends Exception {}

function eq($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new TestFailure(($msg ? $msg . ' -- ' : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function ok($cond, string $msg = 'expected condition to be true'): void
{
    if (!$cond) {
        throw new TestFailure($msg);
    }
}

/** Runs $fn and returns the exception it threw (fails if it did not throw $class). */
function throws(string $class, callable $fn): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $class) {
            return $e;
        }
        throw $e;
    }
    throw new TestFailure("expected $class to be thrown");
}

function finish(): void
{
    $r = $GLOBALS['__t'];
    echo "\n  {$r['pass']} passed, {$r['fail']} failed\n";
    exit($r['fail'] > 0 ? 1 : 0);
}
