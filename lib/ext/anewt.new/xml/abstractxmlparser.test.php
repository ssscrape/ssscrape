<?php

require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('xml');
anewt_include('xml/abstractxmlparser');


function println($str) {
	echo $str, "\n";
}

function printfln() {
	$args = func_get_args();
	$pattern = array_shift($args);
	println(vsprintf($pattern, $args));
}

class TestParser extends AbstractXMLParser {

	function handle_p_data($data) {
		if (trim($data))
			printfln('Data in a <p> tag: %s', $data);
	}

	function handle_b_data($data) {
		if (trim($data))
			printfln('Data in a <b> tag: %s', $data);
	}

	function handle_data($data) {
		if (trim($data))
			println($data);
	}

	function process_testpi($data) {
		printfln('Processing instruction testpi: %s', $data);
	}

	function unknown_endtag($name) {
		println('end: ' . $name);
	}

	function handle_comment($data) {
		println($data);
	}

}

$data = '<?xml version="1.0"?>
<div>
	<p>test</p>
	<p>Some more <b>testing</b></p>
	<?testpi This is a processing instruction ?>
	<!-- This is a
	comment -->
	<p>Finally, some more testing.</p>
	Heh.
</div>';

$p = new TestParser();
$p->feed($data);
$p->close();

?>
