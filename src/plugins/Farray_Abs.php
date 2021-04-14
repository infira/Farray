<?php

namespace Infira\Farray\plugins;

use Infira\Farray\FarrayError;
use Infira\Farray\FarrayList;
use Infira\Farray\FarrayNode;
use Infira\Utils\Is;
use Infira\Utils\Variable;

trait Farray_Abs
{
	/**
	 * @var FarrayNode
	 */
	public    $Node      = null; //related Node
	protected $nodeField = "";
	/**
	 * @var FarrayList
	 */
	protected $List = null; //related List
	
	private $valueparsers = [];
	
	public function _setFieldValueParser(string $index, callable $parser)
	{
		if ($this->isTypeValue())
		{
			if ($this->Node and $this->nodeField)
			{
				return $this->Node->_setFieldValueParser($this->nodeField, $parser);
			}
			else
			{
				$index = '_value_parser_';
			}
		}
		
		if ($this->isTypeNode())
		{
			if ($this->List)
			{
				return $this->List->_setFieldValueParser($index, $parser);
			}
		}
		
		$this->valueparsers[$index] = $parser;
	}
	
	public function _hasFieldValueParser(string $index): bool
	{
		if ($this->isTypeValue())
		{
			if ($this->Node and $this->nodeField)
			{
				return $this->Node->_hasFieldValueParser($this->nodeField);
			}
		}
		
		if ($this->isTypeNode())
		{
			if ($this->List)
			{
				return $this->List->_hasFieldValueParser($index);
			}
		}
		
		return (isset($this->valueparsers[$index]));
	}
	
	public function _getFieldValueParser(string $index): callable
	{
		if ($this->isTypeValue())
		{
			if ($this->Node and $this->nodeField)
			{
				return $this->Node->_getFieldValueParser($this->nodeField);
			}
		}
		
		if ($this->isTypeNode())
		{
			if ($this->List)
			{
				return $this->List->_getFieldValueParser($index);
			}
		}
		
		return $this->valueparsers[$index];
	}
	
	private $rawFields = [];
	
	/**
	 * @param array|string $field
	 */
	public function addRawField($field)
	{
		foreach (Variable::toArray($field) as $field)
		{
			$this->rawFields[$field] = $field;
		}
	}
	
	public function isRawField($field)
	{
		return isset($this->rawFields[$field]);
	}
	
	private function isList($val)
	{
		return Is::isClass($val, "FarrayList");
	}
	
	private function isFarrayValue($val)
	{
		return Is::isClass($val, "\Infira\Farray\FarrayValue");
	}
	
	private function isFarrayNode($val)
	{
		return Is::isClass($val, "\Infira\Farray\FarrayNode");
	}
	
	private function isTypeNode()
	{
		return $this->TYPE == 'node';
	}
	
	private function isTypeValue(): bool
	{
		return $this->TYPE == 'value';
	}
	
	private function isTypeList(): bool
	{
		return $this->TYPE == 'lisst';
	}
	
	private function error(string $msg)
	{
		throw new FarrayError($msg);
	}
}

?>