<?php

anewt_include('page');

$p = new AnewtPage();
$p->append(ax_h1('Hello, world!'));
$p->append(ax_p('This is a simple test page.'));
$p->flush();

?>
