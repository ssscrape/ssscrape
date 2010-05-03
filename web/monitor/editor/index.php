<?php

define('MONITOR_DIR', dirname(__FILE__) . "/../../../lib/monitor/");
define('IMG_URL', '../img/');

require_once("../../../lib/ext/anewt/anewt.lib.php");

require(MONITOR_DIR . 'editor.php');

run_editor();

?>

