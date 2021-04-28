<?php

namespace Infira\Farray;

use Infira\Farray\plugins\Farray_Abs;
use Infira\Farray\plugins\Debug;
use Infira\Utils\Variable;
use ArrayObject;

class FarrayNode extends FarrayObject
{
	use \FarrayNodeExtendor;
	use Farray_Abs;
	use Debug;
	
	private $TYPE                    = "node";
	private $fieldsThatAreGettedOnce = [];
	private $__setFieldValueIsCalled = [];
	private $valueClassName          = '\Infira\Farray\FarrayValue';
	
	/**
	 * @param array $array
	 * @param bool  $List
	 */
	public function __construct($array = [], &$List = false, $valueClassName = '\Infira\Farray\FarrayValue')
	{
		$this->List           = &$List;
		$this->valueClassName = $valueClassName;
		parent::__construct(Variable::toArray($array), false);
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
	public function __gdet(string $field)
	{
		debug("asdadaadasd");
	}
	
	public function __get($name)
	{
		debug("asdadaadasd");
	}
	
	/**
	 * @param string $valueClassName
	 */
	public function setValueClassName(string $valueClassName): void
	{
		$this->valueClassName = $valueClassName;
	}
	
	private function isSettedAsNodeVal($name)
	{
		return $this->isFarrayValue(parent::offsetGet($name));
	}
	
	private function isSettedAsNode($name)
	{
		return $this->isFarrayNode(parent::offsetGet($name));
	}
	
	public function getValue(string $field)
	{
		return $this->$field->val();
	}
	
	/**
	 * Fired when $Node->$field is accessed
	 *
	 * @param mixed $field
	 * @return FarrayValue|mixed
	 */
	public function offsetGet($field)
	{
		if (isset($this->fieldsThatAreGettedOnce[$field]))
		{
			return parent::offsetGet($field);
		}
		if ($this->isRawField($field) and $this->exists($field))
		{
			$this->fieldsThatAreGettedOnce[$field] = true;
			
			return parent::offsetGet($field);
		}
		if (method_exists($this, '__setFieldValue') and !isset($this->__setFieldValueIsCalled[$field]))
		{
			$this->__setFieldValueIsCalled[$field] = true;
			$this->__setFieldValue($field);
		}
		if (!$this->exists($field))
		{
			$this->error("Field $field does not exists");
		}
		$value = parent::offsetGet($field);
		if ($this->_hasFieldValueParser($field))
		{
			$value = call_user_func_array($this->_getFieldValueParser($field), [$value]);
		}
		
		if (!$this->isFarrayValue($value) and !$this->isRawField($field))
		{
			$value = $this->createFarrayValue($field, $value);
		}
		$this->fieldsThatAreGettedOnce[$field] = true;
		parent::offsetSet($field, $value);
		
		return $value;
	}
	
	/**
	 * get value as FarrayValue
	 *
	 * @param string     $field
	 * @param mixed|null $returnOnNotFound - return this value when $field does not exists
	 * @return FarrayValue
	 */
	public function Val(string $field, $returnOnNotFound = null)
	{
		if (!$this->exists($field))
		{
			$value = $returnOnNotFound;
		}
		else
		{
			$value = $this->$field;
		}
		if ($field == 'hideSubMenu')
		{
			addExtraErrorInfo('hideSubMenuyuuuuu', $this->isFarrayValue($value));
		}
		if ($this->isFarrayValue($value))
		{
			return $value;
		}
		
		return $this->createFarrayValue($field, $value);
	}
	
	/**
	 * set Raw field
	 *
	 * @param string $field
	 * @param mixed  $newVal
	 * @return $this
	 */
	public function setRaw(string $field, $newVal): FarrayObject
	{
		$this->addRawField($field);
		$this->set($field, $newVal);
		
		return $this;
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
		return $this->createFarrayValue(false, parent::implode($fieldNames));
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
	
	private function createFarrayValue(string $field, $value)
	{
		$cn = $this->valueClassName;
		
		return new $cn($field, $value, $this);
	}
}

?>