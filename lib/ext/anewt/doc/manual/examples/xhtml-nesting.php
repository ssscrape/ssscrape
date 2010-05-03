<?php

$div = ax_blockquote(ax_p('Some text in a blockquote.'));

$div = ax_blockquote(array(
	ax_p('Some text in a blockquote.'),
	ax_p('Another paragraph in the same blockquote.'),
	));

$p = ax_p(array(
	'This is a ',
	ax_a_href('hyperlink', 'http://anewt.net'),
	' inside some text.'));

?>
