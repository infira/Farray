<?php

namespace Infira\Farray;

use ArrayIterator;
use Error;

class Callback extends ArrayIterator
{
	
	private $callback;
	private $callbackExtraParams = [];
	
	public function __construct($value = [])
	{
		parent::__construct($value);
	}
	
	public function setCallback(callable $callback, array $callbackExtraParams = [])
	{
		$this->callback            = $callback;
		$this->callbackExtraParams = $callbackExtraParams;
	}
	
	public function each($callback, array $extraParams = []): Callback
	{
		foreach ($this as $key => $row)
		{
			$res = $this->rowCallback($row, $key, $callback, $extraParams);
			if ($res == '_______BREAK_______')
			{
				break;
			}
		}
		
		return $this;
	}
	
	public function current()
	{
		return $this->rowCallback(parent::current(), parent::key(), null);
	}
	
	private function rowCallback($row, $key, $callback, array $extraParams = [])
	{
		$row = (object)$row;
		if ($this->callback)
		{
			$row = call_user_func_array($this->callback, array_merge([$row, $key], $this->callbackExtraParams));
		}
		if ($callback)
		{
			$row = call_user_func_array($callback, array_merge([$row, $key], $extraParams));
		}
		if (is_null($row))
		{
			throw new Error("callback reulst cannot be NULL");
		}
		
		return $row;
	}
	
	/**
	 * Alias to append
	 *
	 * @param $value
	 */
	public function add($value)
	{
		$this->append($value);
	}
	
	public function checkArray()
	{
		return ($this->count() > 0);
	}
	
	/**
	 * Alias to offsetGet
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key)
	{
		return $this->offsetGet($key);
	}
	
	/**
	 * Get first item of array
	 *
	 * @param bool $returnOnNotFound
	 * @return bool|mixed
	 */
	public function first($returnOnNotFound = false)
	{
		$k = $this->count() - 1;
		if ($this->exists($k))
		{
			return $this->offsetGet($k);
		}
		
		return $returnOnNotFound;
	}
	
	/**
	 * Alias to offsetSet
	 *
	 * @param string $key
	 * @param mixed  $newVal
	 */
	public function set(string $key, $newVal)
	{
		$this->offsetSet($key, $newVal);
	}
	
	/**
	 * Alias to offsetUnset
	 *
	 * @param string $key
	 */
	public function delete($key)
	{
		$this->offsetUnset($key);
	}
	
	/**
	 * Alias to offsetExists
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function exists(string $key)
	{
		return $this->offsetExists($key);
	}
	
	/**
	 * Debug values
	 */
	public function debug()
	{
		debug($this->getArrayCopy());
	}
	
}

?>