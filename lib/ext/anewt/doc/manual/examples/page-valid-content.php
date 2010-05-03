<?php

$p->append(ax_p('This is a paragraph of text.'));
$p->append(ax_raw('<p>This is some <code>pre-formatted</code> text.</p>'));
$p->append('This string will be escaped: <, &, and > are no problem!');

?>
