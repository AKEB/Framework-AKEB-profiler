<?php

use AKEB\profiler\StatsdProfile;

error_reporting(E_ALL);

class ProfilerTest extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		if (!defined('GRAPHITE_PREFIX')) define('GRAPHITE_PREFIX','prefix');
		if (!defined('SERVER_NAME')) define('SERVER_NAME','local');
	}

	protected function tearDown(): void {
		pf_flush();
	}

	function test_functions() {
		$this->assertTrue(function_exists('pf_inc'));
		$this->assertTrue(function_exists('pf_value'));
		$this->assertTrue(function_exists('pf_gauge'));
		$this->assertTrue(function_exists('pf_timer_start'));
		$this->assertTrue(function_exists('pf_timer_stop'));
		$this->assertTrue(function_exists('pf_timer_set'));
		$this->assertTrue(function_exists('pf_flush'));
		$this->assertTrue(function_exists('pf_db_start'));
		$this->assertTrue(function_exists('pf_db_stop'));
	}

	function test_inc() {
		$this->assertInstanceOf('AKEB\profiler\StatsdProfile', StatsdProfile::getInstance());
		pf_inc('test1');
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test1:1|c');

		pf_inc('test2', 4);
		pf_inc('test2', 3);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test2:7|c');
	}

	function test_val() {
		pf_value('test_val1',1);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test_val1:1|s');

		pf_value('test_val2', 4);
		pf_value('test_val2', 3);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test_val2:3|s');

		$trueCounts = 0;
		for($i=1;$i<100;$i++) {
			pf_value('test_val3', $i, 0.5);
			$data = [];
			StatsdProfile::getInstance()->flush($data);
			if (isset($data[0]) && $data[0]) $trueCounts++;
		}
		if (version_compare(PHP_VERSION, '7.2', '<')) {
			// PHP0443
			$this->assertEquals(50,$trueCounts, 'Кол-во значений', 30);
		} else {
			$this->assertEqualsWithDelta(50,$trueCounts, 30, 'Кол-во значений');
		}
		StatsdProfile::getInstance()->flush($data);
	}

	function test_gauge() {
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		usleep(500);
		pf_gauge('test_gauge1',1);
		$data2 = [];
		StatsdProfile::getInstance()->flush($data2);
		$this->assertEquals($data2[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test_gauge1:1|g',json_encode([$data,$data2]));

		pf_gauge('test_gauge2', 4);
		pf_gauge('test_gauge2', 3);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test_gauge2:3|g');

		pf_gauge('test_gauge2', 3);
		pf_gauge('test_gauge2', '+3');
		pf_gauge('test_gauge2', '-1');
		pf_gauge('test_gauge2', '+abs');
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test_gauge2:5|g');


		$trueCounts = 0;
		for($i=1;$i<=100;$i++) {
			pf_gauge('test_gauge3', $i, 0.5);
			$data = [];
			StatsdProfile::getInstance()->flush($data);
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
		StatsdProfile::getInstance()->flush($data);
		pf_timer_start('test_timer1');
		usleep(5000);
		pf_timer_stop('test_timer1');
		pf_timer_start('test_timer2');
		usleep(50000);
		pf_timer_stop('test_timer2');
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertNotEmpty($data);
		$this->assertGreaterThanOrEqual(2,count($data));
		$t = explode("|", $data[0]);
		$t = explode(':', $t[0]);
		if (version_compare(PHP_VERSION, '7.2', '<')) {
			$this->assertEquals(5,$t[1], '', 3);
		} else {
			$this->assertEqualsWithDelta(5,$t[1], 3);
		}

		$t = explode("|", $data[1]);
		$t = explode(':', $t[0]);
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
		StatsdProfile::getInstance()->flush($data);
		$this->assertGreaterThanOrEqual(2,count($data));
	}

}