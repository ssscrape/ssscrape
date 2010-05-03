<?php

$text_node_1 = ax_sprintf(
    'This is a %s inside some text',
    ax_a_href('hyperlink', 'http://anewt.net'));

$spice = 'Spice';
$text_node_2 = ax_sprintf(
	'%s & %s',
	ax_span_class('Sugar', 'sweet'),
	$spice);

?>
