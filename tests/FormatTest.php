<?php


if (!defined('GRAPHITE_PREFIX')) 	define('GRAPHITE_PREFIX','prefix');
if (!defined('SERVER_NAME')) 		define('SERVER_NAME','local');

use AKEB\profiler\GraphiteFormatter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use AKEB\profiler\StatsdFormatter;

error_reporting(E_ALL);

class FormatTest extends TestCase
{
	protected function setUp(): void {

		parent::setUp();
	}

	/**
	 * @dataProvider dataSet
	 */
	#[DataProvider('dataSet')]
	public function testStatsdFormat($key, $value, $type, $accuracy, array $expectations, $message){
		$expected = $expectations['statsd'];
		$formatter = new StatsdFormatter();
		$formedString = $formatter->format($key, $value, $type, $accuracy);
		$this->assertEquals($expected, $formedString, $message);
	}


	/**
	 * @dataProvider dataSet()
	 */
	#[DataProvider('dataSet')]
	public function testGraphiteFormat($key, $value, $type, $accuracy, array $expectations, $message){
		$expected = $expectations['graphite'];
		$formatter = new GraphiteFormatter(false);
		$formedString = $formatter->format($key, $value, $type, $accuracy);
		[$keys, $value, $timestamp] = explode(' ', $formedString);
		$this->assertEqualsWithDelta(time(), $timestamp, 2);
		$this->assertEquals($expected . ' '. $timestamp, $formedString, $message);
	}
	/**
	 * @dataProvider dataSet()
	 */
	#[DataProvider('dataSet')]
	public function testGraphiteOnTagsFormat($key, $value, $type, $accuracy, array $expectations, $message){
		$expected = $expectations['graphiteOnTags'];
		$formatter = new GraphiteFormatter(true);
		$formedString = $formatter->format($key, $value, $type, $accuracy);
		[$keysAndTags, $value, $timestamp] = explode(' ', $formedString);
		$this->assertEqualsWithDelta(time(), $timestamp, 2);
		$this->assertEquals($expected . ' '. $timestamp, $formedString, $message);
	}

	/**
	 * @return array{key:string, value:string|int|float, type:string, accuracy:null|float, expectations:array{statsd:string, graphite:string, graphiteOnTags:string}, message:string}
	 */
	public static function dataSet():array {
		return [
			[
				'test1',
				'10',
				'ms',
				null,
				[
					'statsd' 			=> implode('.', [GRAPHITE_PREFIX, SERVER_NAME, 'test1']) .':10|ms',
					'graphite' 			=> implode('.', [GRAPHITE_PREFIX, SERVER_NAME, 'test1']) .' 10',
					'graphiteOnTags' 	=> implode('.', [GRAPHITE_PREFIX, 'test1'])
						.';server=' . SERVER_NAME . ';type=ms 10' ,
				],
				'',
			],
			[
				'test2',
				20,
				'ms',
				0.2,
				[
					'statsd' 			=> implode('.', [GRAPHITE_PREFIX, SERVER_NAME, 'test2']) .':20|ms|@0.2',
					'graphite' 			=> implode('.', [GRAPHITE_PREFIX, SERVER_NAME, 'test2']) .' 20',
					'graphiteOnTags' 	=> implode('.', [GRAPHITE_PREFIX, 'test2'])
						.';server=' . SERVER_NAME . ';type=ms;accuracy=0.2 20' ,
				],
				'',
			],
		];
	}
}
