<?php

namespace AKEB\profiler;

interface FormatterInterface
{
	public function format($key, $value, $type, $accuracy=0): ?string;
}