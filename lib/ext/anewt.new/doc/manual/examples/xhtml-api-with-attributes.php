<?php

// Results in <p class="foo">Paragraph text</p> when rendered
$p = ax_p_class('Paragraph text', 'foo');

// The same can be achieved like this:
$p = ax_p('Paragraph text');
$p->set_class('foo');

// Results in <div id="bar">Text here</div> when rendered
$p = ax_div_id('Text here', 'bar');

// The same can be achieved like this:
$div = ax_div('Text here');
$div->set_attribute('id', 'bar');

// Hyperlinks can be built like this:
$link = ax_a_href('Hyperlink text', 'http://anewt.net');
$link = ax_a_href_title('Hyperlink text',
	'http://anewt.net', 'This will be a tooltip');

// Images can be built like this:
$img = ax_img_src_alt('example.png', 'An example image');

?>
