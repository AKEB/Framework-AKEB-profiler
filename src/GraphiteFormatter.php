<?php

namespace AKEB\profiler;
class GraphiteFormatter implements FormatterInterface
{
	/**
	 * @var string
	 */
	private $tagsString = '';
	/**
	 * @var string
	 */
	private $prefix = '';
	private $tagOn;
	public function __construct(bool $tagOn=true)
	{
		$this->tagOn = $tagOn;
		if (defined('GRAPHITE_PREFIX')) {
			$this->prefix .= constant('GRAPHITE_PREFIX').'.';
		}

		if (!defined('SERVER_NAME')) {
			return;
		}
		if ($tagOn) {
			$this->tagsString .= ';server=' . constant('SERVER_NAME');
		}
		else{
			$this->prefix .=  constant('SERVER_NAME') . '.';
		}
	}

	public function format($key, $value, $type, $accuracy = 1): ?string{
		$newKey = $this->prefix;
		$newKey .= str_replace(':','',$key);
		// Не отсылать сообщения которые заканчиваются точкой!
		if ($newKey !== trim($newKey, '.')) {
			return null;
		}
		$tags = $this->tagsString;
		if($this->tagOn){
			$tags .= $type ? ";type=$type" : '';
			$tags .= $accuracy ? ";accuracy=$accuracy" : '';
		}
		return sprintf("%s%s %s %d", $newKey, $tags, $value, time());
	}
}