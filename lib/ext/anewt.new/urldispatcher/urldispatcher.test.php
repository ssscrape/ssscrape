<?php


require_once(dirname(__FILE__) . '/../anewt.lib.php');

anewt_include('urldispatcher');


class TestDispatcher extends AnewtURLDispatcher
{
	function __construct()
	{
		parent::__construct();

		$this->add_route_regex('test', '#^regex$#');
		$this->add_route_regex('test', '#^regex/(\d)$#');
		$this->add_route_regex('test', '#^regex/(?P<first>\d+)/(?P<second>\d+)$#');

		$this->constraints = array(
			'first' => '/^1$/',
			'second' => '/^\d+$/',
		);

		$this->add_route_url_parts('test', 'parts/foo/:bar');
		$this->add_route_url_parts('test', '/parts/:first/:second/');
		$this->add_route_url_parts('test', array('parts', ':first', ':second', ':third'), array('third' => '#^three$#'));

		$this->add_route_url_parts(array($this, 'command_test'), '/');

		$this->add_route_url_parts(array('TestDispatcherCommand', 'external_test'), '/external');
	}

	function command_test($parameters)
	{
		print_r($parameters);
	}
}

class TestDispatcherCommand
{
	static public function external_test($parameters)
	{
		echo 'external command', NL;
		print_r($parameters);
	}
}


$d = new TestDispatcher();
$d->dispatch();

?>
