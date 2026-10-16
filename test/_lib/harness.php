<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Tiny shared assertion harness for the example tests.

$GLOBALS["__xyo_test"] = ["pass" => 0, "fail" => 0];

function t_section($name)
{
    echo "\n== " . $name . " ==\n";
}

function check($label, $condition)
{
    if ($condition) {
        $GLOBALS["__xyo_test"]["pass"]++;
        echo "  PASS  " . $label . "\n";
        return true;
    }
    $GLOBALS["__xyo_test"]["fail"]++;
    echo "  FAIL  " . $label . "\n";
    return false;
}

function check_eq($label, $expected, $actual)
{
    $ok = ($expected === $actual);
    if (!$ok) {
        $label .= "  (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")";
    }
    return check($label, $ok);
}

function check_contains($label, $needle, $haystack)
{
    return check($label, is_string($haystack) && (strpos($haystack, $needle) !== false));
}

function check_not_contains($label, $needle, $haystack)
{
    return check($label, is_string($haystack) && (strpos($haystack, $needle) === false));
}

function t_done()
{
    $pass = $GLOBALS["__xyo_test"]["pass"];
    $fail = $GLOBALS["__xyo_test"]["fail"];
    echo "\nRESULT: " . $pass . " passed, " . $fail . " failed\n";
    exit($fail === 0 ? 0 : 1);
}
