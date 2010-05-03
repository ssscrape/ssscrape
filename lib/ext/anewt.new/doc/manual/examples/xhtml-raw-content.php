<?php

// This will result in <a href="#">...</a> showing up in your browser:
$p = ax_p('This should be a <a href="#">hyperlink</a>, but it is not.');

// This will result in a functioning hyperlink:
$p = ax_p(ax_raw('This is a real <a href="#">hyperlink</a>.'));

// This is the same as the previous line of code:
$p = new AnewtXHTMLParagraph(new AnewtXHTMLRaw('This is a real <a href="#">hyperlink</a>.'));

?>
