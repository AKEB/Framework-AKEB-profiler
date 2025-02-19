<?php

namespace AKEB\profiler;

/**
 * @deprecated use AKEB\profiler\Profile
 */
class StatsdProfile
{
	private static $profile;

	public static function getInstance()
	{
		if (!self::$profile) self::$profile = Profile::getInstance();
		return self::$profile;
	}

	public function __construct()
	{
		self::$profile = Profile::getInstance();
	}

	public function increment($key, $value = 1, $accuracy = 1)
	{
		self::$profile->increment($key, $value, $accuracy);
	}

	public function gauge($key, $value, $accuracy = 1)
	{
		self::$profile->gauge($key, $value, $accuracy);
	}

	public function value($key, $value, $accuracy = 1)
	{
		self::$profile->value($key, $value, $accuracy);
	}

	public function timer_start($key, $accuracy = 1)
	{
		self::$profile->timer_start($key, $accuracy);
	}

	public function timer_stop($key, $accuracy = 1)
	{
		self::$profile->timer_stop($key, $accuracy);
	}

	public function timer_set($key, $value, $accuracy = 1)
	{
		self::$profile->timer_set($key, $value, $accuracy);
	}

	private function add($key, $value, $type)
	{
		self::$profile->add($key, $value, $type);
	}

	public function setDebug($debug = false)
	{
		self::$profile->setDebug($debug);
	}

	public function flush(&$data)
	{
		self::$profile->flush($data);
	}
}