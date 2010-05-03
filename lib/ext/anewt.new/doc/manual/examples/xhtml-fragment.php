<?php

/* Build a fragment containing a few paragraphs of text */

$paragraphs = array(
	ax_p('Paragraph one.'),
	ax_p('Paragraph two.'),
	ax_p('Paragraph three.'));

$fragment = ax_fragment($paragraphs);

$fragment->append_child(ax_p('Paragraph four'));

?>
