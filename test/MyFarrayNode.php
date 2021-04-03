<?php

class MyFarrayValue extends \Infira\Farray\FarrayValue
{
	public function myMethod()
	{
		debug($this->val());
	}
}

class MyFarrayNode extends \Infira\Farray\FarrayNode
{
	public function __construct($array = [], &$List = false)
	{
		parent::__construct($array, $List, 'MyFarrayValue');
	}
}