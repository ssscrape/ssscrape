<?php

error_reporting(E_ALL | E_STRICT);
require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('page');
anewt_include('page/blank');

$bp = new AnewtBlankPage();
$bp->flush();

?>
