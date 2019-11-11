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
		$accuracy = isset($this->accuracies[$key]) ? $this->accuracies[$key] : null;
		$newKey = '';
		if (defined('GRAPHITE_PREFIX')) {
			$newKey .= GRAPHITE_PREFIX.'.';
		}
		if (defined('SERVER_NAME')) {
			$newKey .= SERVER_NAME.'.';
		}
		$newKey .= str_replace(':','',$key);
		// Не отсылать сообщения которые заканчиваются точкой!
		if ($newKey !== trim($newKey, '.')) {
			return;
		}
		if ($accuracy <=0 || $accuracy >= 1 || (mt_rand(0, mt_getrandmax()) / mt_getrandmax()) <= $accuracy) {
			$this->data[]= sprintf("%s:%s|%s%s", $newKey, $value, $type, $accuracy ? '|@'.$accuracy : '');
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
		foreach ($this->values 	 as $key => $value) $this->add($key, $value, 'g');

		if (!$this->data) return;
		$data = $this->data;
		if (defined('STATSD_HOST') && STATSD_HOST) {
			try {
				$fp = fsockopen("udp://" . STATSD_HOST, STATSD_PORT, $errno, $errstr);
				if (!$fp) {
					error_log(sprintf('Connecting to the statsd socket failed %s %s', $errno, $errstr));
					return;
				}
				foreach ($this->data as $message) {
					$res = fwrite($fp, $message);
					if ($res <= 0 || $res !== strlen($message)) {
						error_log(sprintf('Error sending %s to the statsd socket', trim($message)));
					}
				}
				fclose($fp);
			} catch (\Exception $e) {
				error_log(sprintf('Error sending to the statsd socket [%s]', $e->getMessage()));
			}
		}
		if (defined('STATSD_HOST2') && STATSD_HOST2) {
			try {
				$fp = fsockopen("udp://" . STATSD_HOST2, STATSD_PORT2, $errno, $errstr);
				if (!$fp) {
					error_log(sprintf('Connecting to the statsd2 socket failed %s %s', $errno, $errstr));
					return;
				}
				foreach ($this->data as $message) {
					if ($this->debug) {
						error_log('write '.$message);
					}
					$res = fwrite($fp, $message);
					if ($res <= 0 || $res !== strlen($message)) {
						error_log(sprintf('Error sending %s to the statsd2 socket', trim($message)));
					}
				}
				fclose($fp);
			} catch (\Exception $e) {
				error_log(sprintf('Error sending to the statsd2 socket [%s]', $e->getMessage()));
			}
		}

		// очищаем
		$this->data = $this->counters = $this->values = $this->accuracies = [];
	}
}
