<?php

use AKEB\profiler\StatsdProfile;

// PHP-часть метрик, отправляется для агрегации в statsd
function pf_inc($key, $value = 1, $accuracy=1) {
	StatsdProfile::getInstance()->increment($key, $value, $accuracy);
}

function pf_value($key, $value, $accuracy=1) {
	StatsdProfile::getInstance()->value($key, $value, $accuracy);
}

function pf_timer_start($key, $accuracy=1) {
	StatsdProfile::getInstance()->timer_start($key, $accuracy);
}

function pf_timer_stop($key, $accuracy=1) {
	StatsdProfile::getInstance()->timer_stop($key, $accuracy);
}

function pf_timer_set($key, $value, $accuracy=1) {
	StatsdProfile::getInstance()->timer_set($key, $value, $accuracy);
}

function pf_flush() {
	$data = [];
	StatsdProfile::getInstance()->flush($data);
	$data = null;
}

function pf_db_start($table_name) {
	pf_inc('db_cnt.'.$table_name);
	pf_timer_start('db_time.'.$table_name);
}
function pf_db_stop($table_name) {
	pf_timer_stop('db_time.'.$table_name);
}

register_shutdown_function(function() {
	$data = [];
	StatsdProfile::getInstance()->flush($data);
	$data = null;
});
