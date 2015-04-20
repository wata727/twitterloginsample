<?php

namespace Model;

class BaseModel extends \Model
{
	protected static $model = array();

	public static function set($key = NULL, $value)
	{
		if (empty($key)) {
			throw new \Exception('Empty Key');
		} else {
			self::$model[$key] = $value;
		}
	}

	public static function get($key = NULL)
	{
		if (array_key_exists($key, self::$model)) {
			return self::$model[$key];
		} else {
			throw new \Exception('Not Found');
		}
	}

	public static function exists($key = NULL)
	{
		if (array_key_exists($key, self::$model)) {
			return true;
		} else {
			return false;
		}
	}
}