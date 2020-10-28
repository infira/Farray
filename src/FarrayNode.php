<?php

namespace Infira\Farray;

use Infira\Farray\plugins\Farray_Abs;
use Infira\Utils\Is;
use FarrayNodeExtendor;
use FarrayNodeExtendor2;
use Infira\Farray\plugins\Debug;
use Infira\Utils\Variable;
use ArrayObject;

class FarrayNode extends FarrayObject
{
	private $TYPE = "node";
	use FarrayNodeExtendor;
	use FarrayNodeExtendor2;
	
	use Farray_Abs;
	use Debug;
	
	/**
	 * @param array $array
	 * @param bool  $List
	 */
	public function __construct($array = [], &$List = false)
	{
		$this->List = &$List;
		parent::__construct(Variable::toArray($array), ArrayObject::ARRAY_AS_PROPS);
	}
	
	/**
	 * Get parentList
	 * Returns null when list doest not exist
	 *
	 * @return FarrayList|null
	 */
	public function getList()
	{
		return $this->List;
	}
	
	/**
	 * Setter
	 *
	 * @param string $field
	 * @throws FarrayError
	 * @return void
	 */
	public function __get(string $field)
	{
		return $this->error("asdad");
	}
	
	private function isSettedAsNodeVal($name)
	{
		return $this->isFarrayValue(parent::offsetGet($name));
	}
	
	private function isSettedAsNode($name)
	{
		return $this->isFarrayNode(parent::offsetGet($name));
	}
	
	/**
	 * Setter
	 *
	 * @param string $field
	 * @param string $value
	 * @return FarrayNode
	 */
	public function __set(string $field, $value = '')
	{
		if ($this->exists($field))
		{
			if ($this->isRawField($field))
			{
				parent::offsetSet($field, $value);
			}
			elseif ($this->isSettedAsNodeVal($field) or $this->isSettedAsNode($field))
			{
				$this->get($field)->set($value);
			}
			else
			{
				parent::offsetSet($field, $value);
			}
		}
		else
		{
			parent::offsetSet($field, $value);
		}
		
		return $this;
	}
	
	/**
	 * Get value as FarrayValue
	 *
	 * @param string     $field
	 * @param mixed|null $returnOnNotFound - return this value when $field does not exists
	 * @return mixed|object|null
	 */
	public function Val(string $field, $returnOnNotFound = null)
	{
		if (!$this->exists($field))
		{
			return new FarrayValue($field, $returnOnNotFound, $this);
		}
		if ($this->isRawField($field))
		{
			return new FarrayValue($field, $this->_getStoredValue($field), $this);
		}
		
		return $this->offsetGet($field);
	}
	
	public function offsetGet($field)
	{
		$value = $this->_getStoredValue($field);
		if ($this->isRawField($field))
		{
			return $value;
		}
		if (!$this->isFarrayValue($value))
		{
			return new FarrayValue($field, $value, $this);
		}
		
		return $value;
	}
	
	
	/**
	 * get field value
	 *
	 * @param string     $field
	 * @param mixed|null $returnOnNotFound - return this value when $field does not exists
	 * @return mixed|object|null
	 */
	public function get(string $field, $returnOnNotFound = null)
	{
		if (!$this->exists($field))
		{
			return $returnOnNotFound;
		}
		
		return $this->offsetGet($field);
	}
	
	/**
	 * Implode field values with $glue
	 *
	 * @param string $fieldNames
	 * @param string $glue
	 * @return FarrayValue
	 */
	public function implode(string $fieldNames, string $glue = ",")
	{
		return new FarrayValue(false, parent::implode($fieldNames));
	}
	
	/**
	 * Alias to implode
	 *
	 * @param string $fieldNames
	 * @param string $glue
	 * @return FarrayValue
	 */
	public function join(string $fieldNames, string $glue = ",")
	{
		return $this->implode($fieldNames, $glue);
	}
	
	/**
	 * Set sub listNode
	 *
	 * @param string $field
	 * @param mixed  $data
	 * @return FarrayNode
	 */
	public function setAsList(string $field, $data = [])
	{
		$this->addRawField($field);
		$List = new FarrayList($data);
		$List->construct();
		parent::offsetSet($field, $List);
		
		return $this;
	}
	
	/**
	 * Set variable as setAsListNode
	 *
	 * @param string $field
	 * @param mixed  $data
	 * @return FarrayNode
	 */
	public function setAsListNode(string $field, $data)
	{
		$this->addRawField($field);
		$data = ($data) ? $data : [];
		parent::offsetSet($field, new FarrayNode($data));
		
		return $this;
	}
}

?>