<?php

namespace Infira\Farray;

use ArrayObject;
use Infira\Utils\Is;

class FarrayObject extends ArrayObject
{
	
	public function __construct($array = [], $isRecuresive = false)
	{
		if ($isRecuresive)
		{
			foreach ($array as $field => $val)
			{
				if (is_array($val) or is_object($val))
				{
					if (is_array($array))
					{
						$array[$field] = new $this($val, true);
					}
					elseif (is_object($array))
					{
						$array->$field = new $this($val, true);
					}
				}
			}
		}
		if (is_null($array))
		{
			$array = [];
		}
		//addExtraErrorInfo('FarrayObjectData', $array);
		parent::__construct($array, ArrayObject::ARRAY_AS_PROPS);
	}
	
	public function offsetGet($field)
	{
		if (!$this->exists($field))
		{
			throw new FarrayError('Field "' . $field . '" not found');
		}
		
		return parent::offsetGet($field);
	}
	
	/**
	 * Get item
	 *
	 * @param string $field
	 * @param mixed  $returnOnNotFound
	 * @return mixed|null
	 */
	public function get(string $field, $returnOnNotFound = null)
	{
		$field = trim($field);
		if (empty($field))
		{
			return $returnOnNotFound;
		}
		if (!$this->exists($field))
		{
			return $returnOnNotFound;
		}
		
		return $this->offsetGet($field);
	}
	
	/**
	 * Set item
	 *
	 * @param string $field
	 * @param mixed  $newVal
	 * @return $this
	 */
	public function set(string $field, $newVal): FarrayObject
	{
		parent::offsetSet($field, $newVal);
		
		return $this;
	}
	
	/**
	 * Set value via reference
	 *
	 * @param string $field
	 * @param mixed  $value
	 * @return $this
	 */
	public function setRef(string $field, &$value): FarrayObject
	{
		parent::offsetSet($field, $value);
		
		return $this;
	}
	
	/**
	 * @param string $path - s1>s2>s3 will result array as [s1=>[s2=>[s3=>$value]]]
	 * @param mixed  $value
	 * @return $this
	 */
	public function setPath(string $path, $value): FarrayObject
	{
		$r = $value;
		foreach (array_reverse(explode(">", $path)) as $k => $field)
		{
			$r = [$field => $r];
		}
		
		$this->set($field, $r[$field]);
		
		return $this;
	}
	
	/**
	 * Append value to existing array
	 *
	 * @param string - $field where to add new value
	 * @param mixed $newVal
	 * @return $this
	 */
	public function addTo(string $field, $newVal): FarrayObject
	{
		$val   = $this->get($field, [], false);
		$val[] = $newVal;
		
		parent::offsetSet($field, $val);
		
		return $this;
	}
	
	/**
	 * Alias to append
	 *
	 * @param $value
	 * @return $this
	 */
	public function add($value): FarrayObject
	{
		$this->append($value);
		
		return $this;
	}
	
	/**
	 * Change multiple values to array
	 *
	 * @param array|object $data
	 * @return $this
	 */
	public function setValues($data): FarrayObject
	{
		if (!Is::isClass($data, "FarrayObject"))
		{
			$d = new FarrayObject($data);
		}
		else
		{
			$d = $data;
		}
		foreach ($d->getIterator() as $k => $v)
		{
			$this->set($k, $v);
		}
		
		return $this;
	}
	
	/**
	 * Flush current array
	 *
	 * @return $this
	 */
	public function flush(): FarrayObject
	{
		$this->exchangeArray([]);
		
		return $this;
	}
	
	/**
	 * Copy $toKey value from $sourceField value
	 *
	 * @param string $toKey
	 * @param string $sourceKey
	 * @return $this'
	 */
	public function copy(string $toKey, string $sourceKey): FarrayObject
	{
		$this->set($toKey, $this->get($sourceKey));
		
		return $this;
	}
	
	/**
	 * Rename field
	 *
	 * @param string $toKey
	 * @param string $sourceKey
	 * @return $this
	 */
	public function rename(string $toKey, string $sourceKey): FarrayObject
	{
		if ($this->exists($sourceKey))
		{
			$this->copy($toKey, $sourceKey);
			$this->delete($sourceKey);
		}
		
		return $this;
	}
	
	/**
	 * Delete item
	 *
	 * @param $field
	 * @return $this
	 */
	public function delete(string $field): FarrayObject
	{
		if ($this->exists($field))
		{
			parent::offsetUnset($field);
		}
		
		return $this;
	}
	
	/**
	 * Does item exist
	 *
	 * @param string $field
	 * @return bool
	 */
	public function exists(string $field): bool
	{
		return parent::offsetExists($field);
	}
	
	
	/**
	 * Returns true when item value is not empy
	 *
	 * @param string $field
	 * @return bool
	 */
	public function notEmpty(string $field): bool
	{
		return !$this->isEmpty($field);
	}
	
	/**
	 * Returns true when is empty
	 *
	 * @param string $field
	 * @return bool
	 */
	public function isEmpty(string $field): bool
	{
		if (!$this->exists($field))
		{
			return true;
		}
		
		return empty($this->offsetGet($field));
	}
	
	/**
	 * Checks is the item is array and has values
	 *
	 * @param string $field
	 * @return bool
	 */
	public function checkArray(string $field)
	{
		if ($this->exists($field) and checkArray($this->get($field)))
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get all
	 *
	 * @param bool $getAsStdClass - get all as stcClass
	 * @return array|\stdClass
	 */
	public function getAll($getAsStdClass = false)
	{
		if ($getAsStdClass)
		{
			return (object)$this->getArrayCopy();
		}
		
		return $this->getArrayCopy();
	}
	
	/**
	 * Get multiple key values
	 *
	 * @param array $keys
	 * @return array
	 */
	public function getMulti(array $keys = []): array
	{
		$keys = array_flip($keys);
		foreach ($keys as $field => $v)
		{
			$keys[$field] = $this->get($field, null);
		}
		
		return $keys;
	}
	
	/**
	 * Does have any values
	 *
	 * @return bool
	 */
	public function ok(): bool
	{
		return ($this->count() > 0) ? true : false;
	}
	
	/**
	 * Get number of elements
	 *
	 * @return int
	 */
	public function size(): int
	{
		return $this->count();
	}
	
	/**
	 * Debug current value
	 *
	 * @return void
	 */
	public function debug()
	{
		debug($this->getArrayCopy());
	}
	
	/**
	 * build http query string from current values or parseStr and then set values
	 *
	 * @param mixed|null $value - if value is NULL then string will be returned
	 * @return $this|string
	 */
	public function parseStr($value = null)
	{
		if ($value === null)
		{
			return http_build_query($this->getArrayCopy());
		}
		else
		{
			$data = parseStr(urldecode($value));
			if (is_object($data) or is_array($data))
			{
				$this->setValues($data);
			}
			
			return $this;
		}
	}
	
	/**
	 * Iterate items with $callback
	 *
	 * @param callable $callback
	 * @param object   $scope
	 */
	public function each(callable $callback, $scope = null)
	{
		foreach ($this->getIterator() as $field => $val)
		{
			callback($callback, $scope, [$val, $field]);
		}
	}
	
	/**
	 * Implode field values with $glue
	 *
	 * @param string $fieldNames
	 * @param string $glue
	 * @return string
	 */
	public function implode(string $fieldNames, string $glue = ",")
	{
		$newValue = [];
		foreach (Variable::toArray($fieldNames) as $field)
		{
			$v = $this->get($field)->val();
			if ($v)
			{
				$newValue[] = $v;
			}
		}
		
		return join($glue, $newValue);
	}
	
	/**
	 * Alias to implode
	 *
	 * @param string $fieldNames
	 * @param string $glue
	 * @return string
	 */
	public function join(string $fieldNames, string $glue = ",")
	{
		return $this->implode($fieldNames, $glue);
	}
}

?>