<?php

use AKEB\profiler\Profile;

error_reporting(E_ALL);

class GraphiteFormatterTest extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		if (!defined('GRAPHITE_PREFIX')) define('GRAPHITE_PREFIX','prefix');
		if (!defined('SERVER_NAME')) define('SERVER_NAME','local');
		if (!defined('PROFILER_FORMATTER')) define('PROFILER_FORMATTER',\AKEB\profiler\GraphiteFormatter::class);
		if (!defined('PROFILER_FORMATTER_OPTIONS')) define('PROFILER_FORMATTER_OPTIONS',true);
	}

	protected function tearDown(): void {
		pf_flush();
	}
	function test_inc() {
		$this->assertInstanceOf('AKEB\profiler\Profile', Profile::getInstance());
		pf_inc('test1');
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.test1'.';server='.constant('SERVER_NAME').';type=c 1 ' . time());

		pf_inc('test2', 4);
		pf_inc('test2', 3);
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.test2'.';server='.constant('SERVER_NAME').';type=c 7 ' . time());
	}

	public function test_class_formatter() {
		$profile = Profile::getInstance();

		$class = new ReflectionClass($profile);
		$ReflectionProfilePropertyFormatter = $class->getProperty('formatter');
		$ReflectionProfilePropertyFormatter->setAccessible(true);
		$profileFormatter = $ReflectionProfilePropertyFormatter->getValue($profile);
		$this->assertEquals(PROFILER_FORMATTER, get_class($profileFormatter));
	}
}