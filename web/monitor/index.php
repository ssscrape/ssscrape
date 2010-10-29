<?php

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED);

define('MONITOR_DIR', dirname(__FILE__) . "/../../lib/monitor/");
define('IMG_URL', 'img/');

require_once("../../lib/ext/anewt/anewt.lib.php");

require(MONITOR_DIR . 'monitor.php');

run_monitor()

?>

