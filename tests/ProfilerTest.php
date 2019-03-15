<?php //$Id:$

use AKEB\profiler\StatsdProfile;

error_reporting(E_ALL);

class ProfilerTest extends PHPUnit\Framework\TestCase {

	function setUp() {
		if (!defined('GRAPHITE_PREFIX')) define('GRAPHITE_PREFIX','prefix');
		if (!defined('SERVER_NAME')) define('SERVER_NAME','local');
	}

	function tearDown() {
		pf_flush();
	}

	function test_functions() {
		$this->assertTrue(function_exists('pf_inc'));
		$this->assertTrue(function_exists('pf_value'));
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
		$this->assertEquals($data[0], GRAPHITE_PREFIX.'.'.SERVER_NAME.'.test1:1|c');

		pf_inc('test2', 4);
		pf_inc('test2', 3);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], GRAPHITE_PREFIX.'.'.SERVER_NAME.'.test2:7|c');

		$trueCounts = 0;
		$summCounts = 0;
		for($i=1;$i<=100;$i++) {
			if ($i == 100) pf_inc('test3', 1);
			else pf_inc('test3', 1, 0.5);

			$data = [];
			StatsdProfile::getInstance()->flush($data);
			if (isset($data[0]) && $data[0]) {
				$trueCounts++;
				$t = explode("|", $data[0]);
				$t = explode(':', $t[0]);
				$summCounts += intval($t[1]);
			}
			//echo $i.' '.(isset($data[0]) ? $data[0] : '').' '.$summCounts.PHP_EOL;
		}
		$this->assertEquals(100,$summCounts);
		//var_dump($summCounts);
		$this->assertEquals(50,$trueCounts, 'Кол-во значений', 30);
	}

	function test_val() {
		$this->assertInstanceOf(StatsdProfile::class, StatsdProfile::getInstance());
		pf_value('test_val1',1);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], GRAPHITE_PREFIX.'.'.SERVER_NAME.'.test_val1:1|g');

		pf_value('test_val2', 4);
		pf_value('test_val2', 3);
		$data = [];
		StatsdProfile::getInstance()->flush($data);
		$this->assertEquals($data[0], GRAPHITE_PREFIX.'.'.SERVER_NAME.'.test_val2:3|g');

		$TrueCounts = 0;
		for($i=1;$i<=100;$i++) {
			pf_value('test_val3', $i, 0.5);
			$data = [];
			StatsdProfile::getInstance()->flush($data);
			if (isset($data[0]) && $data[0]) $TrueCounts++;
		}
		$this->assertEquals(50,$TrueCounts, 'Кол-во значений', 30);
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
		$this->assertEquals(5,$t[1], '', 3);
		$t = explode("|", $data[1]);
		$t = explode(':', $t[0]);
		$this->assertEquals(50,$t[1], '', 5);

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

	function test_send() {
		define('STATSD_HOST','127.0.0.1');
		define('STATSD_PORT','8126');
		pf_inc('test1');
		pf_flush();
	}

	function test_send2() {
		pf_inc('test1');
		pf_flush();
	}
}