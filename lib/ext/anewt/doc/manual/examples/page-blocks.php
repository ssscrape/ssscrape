<?php

anewt_include('page');
$p = new AnewtPage();

$p->set('blocks', array('header', 'content', 'footer'));
$p->set('default-block', 'content');

/* Add content to specific blocks */
$p->append_to('header', ax_p('This is the header text.'));
$p->append_to('footer', ax_p('This is the footer text.'));

/* Add content to the default block */
$p->append(ax_h1('Hello, world!'));
$p->append(ax_p('This is a simple test page.'));

/* You can add content to the default block using append_to() as well, but that
 * involves a bit more typing. */
$p->append_to('content', ax_p('Another paragraph.'));

$p->flush();

?>
