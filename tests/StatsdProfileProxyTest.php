<?php

use AKEB\profiler\StatsdProfile;
use \AKEB\profiler\Profile;

error_reporting(E_ALL);

class StatsdProfileProxyTest extends PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		if (!defined('GRAPHITE_PREFIX')) define('GRAPHITE_PREFIX','prefix');
		if (!defined('SERVER_NAME')) define('SERVER_NAME','local');
	}

	protected function tearDown(): void {
		pf_flush();
	}

	function test_proxy_constrictor()
	{
		$this->assertInstanceOf('AKEB\profiler\Profile', $instance = StatsdProfile::getInstance());
		pf_inc('test1');
		$profile = new StatsdProfile();
		$profile->increment('test2');

		$this->assertEquals(StatsdProfile::getInstance(), $instance);
		$this->assertEquals(StatsdProfile::getInstance(), Profile::getInstance());
	}

	function test_proxy_methods(){
		$statsdProfile = new StatsdProfile();
		$profile = Profile::getInstance();
		$this->assertNotEquals($statsdProfile, $profile);

		$args = ['test2', 1, 1];

		$statsdValue = [];
		$defaultFormatValue = [];
		$profile->increment(...$args);
		$profile->flush($defaultFormatValue);
		$statsdProfile->increment(...$args);
		$statsdProfile->flush($statsdValue);
		$this->assertEquals($defaultFormatValue, $statsdValue);

		$statsdValue = [];
		$defaultFormatValue = [];
		$profile->value(...$args);
		$profile->flush($defaultFormatValue);
		$statsdProfile->value(...$args);
		$statsdProfile->flush($statsdValue);
		$this->assertEquals($defaultFormatValue, $statsdValue);

		$statsdValue = [];
		$defaultFormatValue = [];
		$profile->gauge(...$args);
		$profile->flush($defaultFormatValue);
		$statsdProfile->gauge(...$args);
		$statsdProfile->flush($statsdValue);
		$this->assertEquals($defaultFormatValue, $statsdValue);

		$statsdValue = [];
		$defaultFormatValue = [];
		$profile->timer_set(...$args);
		$profile->flush($defaultFormatValue);
		$statsdProfile->timer_set(...$args);
		$statsdProfile->flush($statsdValue);
		$this->assertEquals($defaultFormatValue, $statsdValue);

		$statsdValue = [];
		$defaultFormatValue = [];
		$profile->timer_start(...$args);
		$profile->flush($defaultFormatValue);
		$statsdProfile->timer_start(...$args);
		$statsdProfile->flush($statsdValue);
		$this->assertEquals($defaultFormatValue, $statsdValue);

		$statsdValue = [];
		$defaultFormatValue = [];
		$profile->timer_stop(...$args);
		$profile->flush($defaultFormatValue);
		$statsdProfile->timer_stop(...$args);
		$statsdProfile->flush($statsdValue);
		$this->assertEquals($defaultFormatValue, $statsdValue);
	}

	function test_inc() {
		$profile = new StatsdProfile();

		pf_inc('test1');
		$data = [];
		$profile->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test1:1|c');

		pf_inc('test2', 4);
		pf_inc('test2', 3);
		$data = [];
		$profile->flush($data);
		$this->assertEquals($data[0], constant('GRAPHITE_PREFIX').'.'.constant('SERVER_NAME').'.test2:7|c');
	}
}