<?php

use AKEB\profiler\Profile;

error_reporting(E_ALL);

class GraphiteFormatterNoTagsTest extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		if (!defined('GRAPHITE_PREFIX')) define('GRAPHITE_PREFIX','prefix');
		if (!defined('SERVER_NAME')) define('SERVER_NAME','local');
		if (!defined('PROFILER_FORMATTER')) define('PROFILER_FORMATTER',\AKEB\profiler\GraphiteFormatter::class);
		if (!defined('PROFILER_FORMATTER_OPTIONS')) define('PROFILER_FORMATTER_OPTIONS',false);
	}

	protected function tearDown(): void {
		pf_flush();
	}
	function test_inc() {
		$this->assertInstanceOf('AKEB\profiler\Profile', Profile::getInstance());
		pf_inc('test1');
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test1'.' 1 ' . time());

		pf_inc('test2', 4);
		pf_inc('test2', 3);
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test2'.' 7 ' . time());
	}

	function test_val() {
		pf_value('test_val1',1);
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test_val1'.' 1 ' . time());

		pf_value('test_val2', 4);
		pf_value('test_val2', 3);
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test_val2'.' 3 ' . time());

		$trueCounts = 0;
		for($i=1;$i<100;$i++) {
			pf_value('test_val3', $i, 0.5);
			$data = [];
			Profile::getInstance()->flush($data);
			if (isset($data[0]) && $data[0]) $trueCounts++;
		}
		if (version_compare(PHP_VERSION, '7.2', '<')) {
			// PHP0443
			$this->assertEquals(50,$trueCounts, 'Кол-во значений', 30);
		} else {
			$this->assertEqualsWithDelta(50,$trueCounts, 30, 'Кол-во значений');
		}
		Profile::getInstance()->flush($data);
	}

	function test_gauge() {
		$data = [];
		Profile::getInstance()->flush($data);
		usleep(500);
		pf_gauge('test_gauge1',1);
		$data2 = [];
		Profile::getInstance()->flush($data2);
		$this->assertEquals($data2[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test_gauge1'.' 1 ' . time(), json_encode([$data,$data2]));

		pf_gauge('test_gauge2', 4);
		pf_gauge('test_gauge2', 3);
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test_gauge2'.' 3 ' . time());

		pf_gauge('test_gauge2', 3);
		pf_gauge('test_gauge2', '+3');
		pf_gauge('test_gauge2', '-1');
		pf_gauge('test_gauge2', '+abs');
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME') . '.test_gauge2'.' 5 ' . time());


		$trueCounts = 0;
		for($i=1;$i<=100;$i++) {
			pf_gauge('test_gauge3', $i, 0.5);
			$data = [];
			Profile::getInstance()->flush($data);
			if (isset($data[0]) && $data[0]) $trueCounts++;
		}
		if (version_compare(PHP_VERSION, '7.2', '<')) {
			$this->assertEquals(50,$trueCounts, 'Кол-во значений', 30);
		} else {
			$this->assertEqualsWithDelta(50,$trueCounts, 30, 'Кол-во значений');
		}
	}

	function test_pf_timer_start() {
		$data = [];
		Profile::getInstance()->flush($data);
		pf_timer_start('test_timer1');
		usleep(5000);
		pf_timer_stop('test_timer1');
		pf_timer_start('test_timer2');
		usleep(50000);
		pf_timer_stop('test_timer2');
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertNotEmpty($data);
		$this->assertGreaterThanOrEqual(2,count($data));
		$t = explode(" ", $data[0]);
		if (version_compare(PHP_VERSION, '7.2', '<')) {
			$this->assertEquals(5,$t[1], '', 3);
		} else {
			$this->assertEqualsWithDelta(5,$t[1], 3);
		}

		$t = explode(" ", $data[1]);
		if (version_compare(PHP_VERSION, '7.2', '<')) {
			$this->assertEquals(50,$t[1], '', 5);
		} else {
			$this->assertEqualsWithDelta(50,$t[1], 5);
		}

		pf_timer_set('test_timer3',50, 0.5);

	}

	function test_pf_db() {
		pf_db_start('testTable');
		usleep(5000);
		pf_db_stop('testTable');
		$data = [];
		Profile::getInstance()->flush($data);
		$this->assertGreaterThanOrEqual(2,count($data));
	}

}