<?php
/**
 * You need define next constants:
 *
 * GRAPHITE_PREFIX
 * SERVER_NAME
 * STATSD_HOST
 * STATSD_PORT
 *
 */
namespace AKEB\profiler;


class StatsdProfile {
	private $data = [];
	private $counters = [];
	private $timings = [];
	private $gauges = [];
	private $values = [];
	private $accuracies = [];
	private $debug = false;

	private static $instance;

	public static function getInstance() {
		if (!self::$instance) self::$instance = new static();
		return self::$instance;
	}

	public function increment($key, $value = 1, $accuracy=1) {
		if (!isset($this->counters[$key])) $this->counters[$key] = intval($value);
		else $this->counters[$key] += intval($value);
		if ($accuracy < 1) $this->accuracies[$key] = $accuracy;
		else unset($this->accuracies[$key]);
	}

	public function gauge($key, $value, $accuracy=1) {
		if (is_string($value)) {
			$sign = substr($value,0,1);
			$val = intval(substr($value,1));
			if ($sign == '+') {
				$this->gauges[$key] += $val;
			} elseif ($sign == '-') {
				$this->gauges[$key] -= $val;
			}
		} else $this->gauges[$key] = $value;
		if ($accuracy < 1) $this->accuracies[$key] = $accuracy;
		else unset($this->accuracies[$key]);
	}

	public function value($key, $value, $accuracy=1) {
		$this->values[$key] = intval($value);
		if ($accuracy < 1) $this->accuracies[$key] = $accuracy;
		else unset($this->accuracies[$key]);
	}

	public function timer_start($key, $accuracy=1) {
		if ($accuracy < 1) $this->accuracies[$key] = $accuracy;
		$this->timings[$key] = gettimeofday(true);
	}

	public function timer_stop($key, $accuracy=1) {
		if (isset($this->timings[$key])) {
			if ($accuracy < 1) $this->accuracies[$key] = $accuracy;
			$this->add($key, intval(1000*(gettimeofday(true) - $this->timings[$key])), 'ms');
			unset($this->timings[$key]);
		}
	}

	public function timer_set($key, $value, $accuracy=1) {
		if ($accuracy < 1) $this->accuracies[$key] = $accuracy;
		$this->add($key, intval(1000 * $value), 'ms');
	}

	private function add($key, $value, $type) {
		$accuracy = isset($this->accuracies[$key]) ? $this->accuracies[$key] : 0;
		$newKey = '';
		if (defined('GRAPHITE_PREFIX')) {
			$newKey .= constant('GRAPHITE_PREFIX').'.';
		}
		if (defined('SERVER_NAME')) {
			$newKey .= constant('SERVER_NAME').'.';
		}
		$newKey .= str_replace(':','',$key);
		// Не отсылать сообщения которые заканчиваются точкой!
		if ($newKey !== trim($newKey, '.')) {
			return;
		}
		if ($accuracy <=0 || $accuracy >= 1 || (mt_rand(0, mt_getrandmax()) / mt_getrandmax()) <= $accuracy) {
			$this->data[]= sprintf("%s:%s|%s%s", $newKey, strval($value), $type, $accuracy ? '|@'.$accuracy : '');
		}
	}

	public function setDebug($debug=false) {
		$this->debug = $debug;
	}

	/**
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function flush(&$data) {
		foreach ($this->timings  as $key => $_)	$this->timer_stop($key);
		foreach ($this->counters as $key => $value) $this->add($key, $value, 'c');
		foreach ($this->values 	 as $key => $value) $this->add($key, $value, 's');
		foreach ($this->gauges 	 as $key => $value) $this->add($key, $value, 'g');

		if (!$this->data) {
			$this->data = $this->counters = $this->values = $this->accuracies = $this->gauges = [];
			return;
		}
		$data = $this->data;
		if (defined('STATSD_HOST') && constant('STATSD_HOST')) {
			try {
				$fp = fsockopen("udp://" . constant('STATSD_HOST'), constant('STATSD_PORT'), $errno, $errstr);
				if (!$fp) {
					foreach ($this->data as $message) {
						$res = fwrite($fp, $message);
						if ($res <= 0 || $res !== strlen($message)) {
							error_log(sprintf('Error sending %s to the statsd socket', trim($message)));
						}
					}
					fclose($fp);
				}
			} catch (\Exception $e) {
				error_log(sprintf('Error sending to the statsd socket [%s]', $e->getMessage()));
			}
		}
		// очищаем
		$this->data = $this->counters = $this->values = $this->accuracies = $this->gauges = [];
	}
}
