<?php

namespace AKEB\profiler;

class StatsdFormatter implements FormatterInterface
{
	/**
	 * @var string
	 */
	private $prefix;

	public function __construct()
	{
		$prefix = '';
		if (defined('GRAPHITE_PREFIX')) {
			$prefix .= constant('GRAPHITE_PREFIX') . '.';
		}
		if (defined('SERVER_NAME')) {
			$prefix .= constant('SERVER_NAME') . '.';
		}
		$this->prefix = $prefix;
	}

	public function format($key, $value, $type, $accuracy = 1): ?string
	{
		$newKey = $this->prefix;
		$newKey .= str_replace(':', '', $key);
		// Не отсылать сообщения которые заканчиваются точкой!
		if ($newKey !== trim($newKey, '.')) {
			return null;
		}
		return sprintf("%s:%s|%s%s", $newKey, strval($value), $type, $accuracy ? '|@' . $accuracy : '');
	}
}