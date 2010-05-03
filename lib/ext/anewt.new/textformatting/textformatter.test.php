<?php

error_reporting(E_ALL);
require_once '../anewt.lib.php';
anewt_include('textformatting');

/* Simple text */
$input = 'This is a test.';
var_dump(TextFormatter::format($input));
var_dump(TextFormatter::format($input, 'raw'));
var_dump(TextFormatter::format($input, 'specialchars'));
var_dump(TextFormatter::format($input, 'entities'));
var_dump(TextFormatter::format($input, 'textile'));

/* HTML text (with leading whitespace!) */
$input = ' <p>This is a <strong>test</strong>.</p>';
var_dump(TextFormatter::format($input));
var_dump(TextFormatter::format($input, 'raw'));
var_dump(TextFormatter::format($input, 'specialchars'));
var_dump(TextFormatter::format($input, 'entities'));
var_dump(TextFormatter::format($input, 'textile'));

?>
