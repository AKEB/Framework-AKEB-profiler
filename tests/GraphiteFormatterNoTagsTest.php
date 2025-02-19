<?php

use AKEB\profiler\Profile;

require_once __DIR__ . '/GraphiteFormatterTest.php';

error_reporting(E_ALL);

class GraphiteFormatterNoTagsTest extends GraphiteFormatterTest
{

	protected function setUp(): void
	{
		if (!defined('PROFILER_FORMATTER_OPTIONS')) define('PROFILER_FORMATTER_OPTIONS', false);
		parent::setUp();
	}

	function test_inc()
	{
		$this->assertInstanceOf('AKEB\profiler\Profile', Profile::getInstance());
		pf_inc('test1');
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX') . '.' . constant('SERVER_NAME') . '.test1' . ' 1 ' . time());

		pf_inc('test2', 4);
		pf_inc('test2', 3);
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX') . '.' . constant('SERVER_NAME') . '.test2' . ' 7 ' . time());
	}
}