<?php

namespace Infira\Farray;

use Infira\Farray\plugins\Farray_Abs;
use Infira\Farray\plugins\Debug;
use ArrayIterator;
use Infira\Utils\Is;
use Infira\Utils\Variable;

class FarrayList extends ArrayIterator
{
	private $TYPE = 'list';
	use \FarrayExtendor;
	use Farray_Abs;
	use Debug;
	
	private $listKeys                = [];
	public  $firstKey                = false;
	public  $lastKey                 = false;
	private $count                   = 0;
	private $keyNr                   = 0;
	public  $registeredFieldValues   = [];
	public  $ListInfo;
	public  $PagesInfo;
	private $nodeClassName;
	private $fieldsThatAreGettedOnce = [];
	private $IDFIeld;
	
	public function __construct(array $array = [], $listNodeClassName = '\Infira\Farray\FarrayNode')
	{
		$this->nodeClassName = $listNodeClassName;
		$this->className     = get_class($this);
		parent::__construct($array);
		$this->ListInfo             = new \stdClass();
		$this->ListInfo->countAll   = 0;
		$this->ListInfo->pagesCount = 0;
		$this->ListInfo->next       = false;
		$this->ListInfo->prev       = false;
		$this->ListInfo->pages      = [];
		$this->ListInfo->last       = false;
	}
	
	public function setIDField(string $field)
	{
		$this->IDFIeld = $field;
	}
	
	public function construct()
	{
		if ($this->checkArray())
		{
			if ($this->IDFIeld)
			{
				$newArray       = [];
				$this->listKeys = [];
				array_map(function ($Row) use (&$newArray)
				{
					$field                  = $this->IDFIeld;
					$this->listKeys[]       = $Row[$field];
					$newArray[$Row[$field]] = $Row;
				}, $this->getArrayCopy());
				$this->setAll($newArray);
			}
			else
			{
				$this->listKeys = array_keys($this->getArrayCopy());
			}
			$this->firstKey = $this->listKeys[0];
			$this->lastKey  = array_key_last($this->listKeys);
		}
		if (method_exists($this, "afterConstruct"))
		{
			$this->afterConstruct();
		}
		
		return $this;
	}
	
	/**
	 * Get ListNode at $index
	 *
	 * @param mixed $index
	 * @return FarrayNode
	 */
	public function offsetGet($index)
	{
		if (isset($this->fieldsThatAreGettedOnce[$index]))
		{
			return parent::offsetGet($index);
		}
		$row = parent::offsetGet($index);
		if (isset($this->rowParser['parser']))
		{
			$row = callback($this->rowParser['parser'], $this->rowParser['scope'], [$row]);
		}
		if (is_array($row) or Is::isClass($row, "stdClass"))
		{
			$row = $this->createListNode($row);
		}
		$this->fieldsThatAreGettedOnce[$index] = true;
		parent::offsetSet($index, $row);
		
		return $row;
	}
	
	protected function createListNode($value)
	{
		return new $this->nodeClassName($value, $this);
	}
	
	/**
	 * @param array $array
	 * @return FarrayList
	 */
	public function createNewList($array = [])
	{
		return new $this($array);
	}
	
	public function checkArray()
	{
		return $this->ok();
	}
	
	public function size()
	{
		return $this->count();
	}
	
	public function ok()
	{
		return ($this->count() > 0) ? true : false;
	}
	
	public function flush()
	{
		$this->setAll([]);
		
		return $this;
	}
	
	/**
	 * Exhcnage current storeage
	 *
	 * @param array $arr
	 */
	public function setAll($arr = [])
	{
		parent::__construct($arr);
	}
	
	/**
	 * Does the item exist
	 *
	 * @param string $index
	 * @return bool
	 */
	public function exists(string $index)
	{
		return $this->offsetExists($index);
	}
	
	/**
	 * Add item to storage
	 *
	 * @param object|array $item
	 * @param string|null  $addFieldValueAsKey
	 * @throws FarrayError
	 * @return number
	 */
	public function add($item, string $addFieldValueAsKey = null)
	{
		$this->count++;
		if (empty($item))
		{
			$this->error("Add item cant be empty");
		}
		if (Is::isClass($item, $this->nodeClassName))
		{
			$Node = $item;
		}
		else
		{
			$Node = $this->createListNode($item);
		}
		if ($addFieldValueAsKey)
		{
			$addKey = $Node->$addFieldValueAsKey->value;
		}
		else
		{
			$addKey = $this->keyNr;
		}
		$this->keyNr++;
		$this->offsetSet($addKey, $Node);
		
		return $addKey;
	}
	
	/**
	 * Alias to add
	 *
	 * @param mixed $value
	 */
	public function append($value)
	{
		$this->add($value);
	}
	
	/**
	 * Add Rows to list
	 *
	 * @param array $data
	 */
	public function addRows($data)
	{
		foreach ($data as $v)
		{
			$this->add($v);
		}
	}
	
	/**
	 * Get first row
	 *
	 * @param mixed $returnOnNotFound
	 * @return mixed|null
	 */
	public function first($returnOnNotFound = null)
	{
		return $this->get($this->firstKey, $returnOnNotFound);
	}
	
	/**
	 * Get last row
	 *
	 * @param mixed $returnOnNotFound
	 * @return mixed|null
	 */
	public function last($returnOnNotFound = null)
	{
		return $this->get($this->lastKey, $returnOnNotFound);
	}
	
	
	/**
	 * get item at $index
	 *
	 * @param string $index
	 * @param mixed  $returnOnNotFound
	 * @return mixed|null
	 */
	public function get(string $index, $returnOnNotFound = null)
	{
		if (!$this->exists($index))
		{
			return $returnOnNotFound;
		}
		
		return $this->offsetGet($index);
	}
	
	public function current()
	{
		return $this->get($this->key(), null);
	}
	
	
	/**
	 * Get random item
	 *
	 * @return mixed
	 */
	public function random()
	{
		return $this->get(array_rand($this->listKeys, 1));
	}
	
	private $__DATA_FILTER_FIELD = false;
	
	private $__DATA_FILTER_VALUE = false;
	
	private function doFilter($v)
	{
		$f = $this->__DATA_FILTER_FIELD;
		$v = $v->$f;
		if (is_object($v))
		{
			$v = $v->val();
		}
		
		return $v == $this->__DATA_FILTER_VALUE;
	}
	
	public function filter(string $index, $value)
	{
		$this->__DATA_FILTER_FIELD = $index;
		$this->__DATA_FILTER_VALUE = $value;
		
		/**
		 * @var FarrayList
		 */
		$newArr  = array_filter($this->getArrayCopy(), [$this, "doFilter"]);
		$newList = $this->createNewList($newArr);
		$newList->construct();
		
		return $newList;
	}
	
	/**
	 * Find value in each row
	 *
	 * @param string $field
	 * @param mixed  $value
	 * @return null|FarrayNode
	 */
	public function findByFieldValue(string $field, $value)
	{
		foreach ($this as $Row)
		{
			if ($Row->$field->is($value))
			{
				return $Row;
			}
		}
		
		return null;
	}
	
	/**
	 * Collect each row field value
	 *
	 * @param string $field
	 * @return array
	 */
	public function getFieldValues(string $field)
	{
		$outout = [];
		if ($this->ok())
		{
			foreach ($this as $Row)
			{
				$outout[] = $Row->$field->value;
			}
		}
		
		return $outout;
	}
	
	/**
	 * Sum each row $field value
	 *
	 * @param $field
	 * @return float|int
	 */
	public function sumFields($field)
	{
		return array_sum($this->getFieldValues($field));
	}
	
	public function count()
	{
		return $this->count;
	}
	
	/**
	 * Set parser during getting value
	 *
	 * @param string      $field
	 * @param callable    $parser
	 * @param object|null $scope - optional
	 */
	public function setFieldValueParser(string $field, callable $parser, object $scope = null)
	{
		$this->_setFieldValueParser($field, $parser, $scope);
	}
	
	private $rowParser = [];
	
	public function setRowParser(callable $parser, $scope = false)
	{
		$this->rowParser['parser'] = $parser;
		$this->rowParser['scope']  = $scope;
	}
	
	//##################### SOF manipulation
	public function orderBy($field, $desc = false)
	{
		$cmp = function ($a, $b) use ($field)
		{
			return strcmp($a->$field->val(), $b->$field->val());
		};
		
		$arr = $this->getArrayCopy();
		usort($arr, $cmp);
		if (!$desc)
		{
			$arr = array_reverse($arr);
		}
		
		return new FarrayList($arr);
	}
	
	public function distinct($field)
	{
		$distincts = [];
		$newList   = $this->createNewList();
		if ($this->checkArray())
		{
			foreach ($this as $key => $Row)
			{
				if ($Row->exists($field))
				{
					$val = $Row->$field->value;
					if (!array_key_exists($val, $distincts))
					{
						$distincts[$val] = $val;
						$newList->add($Row);
					}
				}
			}
			$newList->construct();
		}
		
		return $newList;
	}
	
	public function slice($nr1 = null, $nr2 = null, $preserveKeys = true)
	{
		/**
		 * @var FarrayList
		 */
		$newArr  = array_slice($this->getArrayCopy(), $nr1, $nr2, $preserveKeys);
		$newList = $this->createNewList($newArr);
		$newList->construct();
		
		return $newList;
	}
	
	function partition($p)
	{
		$list    = $this->getArrayCopy();
		$newList = $this->createNewList();
		$listlen = count($list);
		$partlen = floor($listlen / $p);
		$partrem = $listlen % $p;
		$mark    = 0;
		for ($px = 0; $px < $p; $px++)
		{
			$incr = ($px < $partrem) ? $partlen + 1 : $partlen;
			$part = $this->createNewList();
			$part->setAll(array_slice($list, $mark, $incr));
			$newList->offsetSet($px, $part);
			$mark += $incr;
		}
		
		return $newList;
	}
	
	public function reverse()
	{
		return new $this(array_reverse($this->getArrayCopy()));
	}
	
	public function chunk($nr)
	{
		$NewList = $this->createNewList();
		$chunked = array_chunk($this->getArrayCopy(), $nr);
		foreach ($chunked as $key => $chunk)
		{
			$new = $this->createNewList($chunk);
			$new->construct();
			$NewList->offsetSet($key, $new);
		}
		
		return $NewList->construct();
	}
	
	/**
	 * Get grouped list
	 *
	 * @param string $name - group by field name
	 * @return FarrayList
	 */
	public function group($keyField, $nameField = false)
	{
		$newList = $this->createNewList();
		foreach ($this as $addKey => $item)
		{
			$indexKeyValue = $item->$keyField->value;
			$name          = "";
			if ($nameField != false)
			{
				$name = $item->$nameField->value;
			}
			$newList->registerKeyList($indexKeyValue, $name);
			$newList->addToKey($indexKeyValue, $item);
		}
		
		return $newList;
	}
	
	public function registerFieldValues($field, $fieldValue, $val)
	{
		if (!isset($this->registeredFieldValues[$field][$fieldValue]))
		{
			$val->isFirst = true;
		}
		else
		{
			$c                                                            = count($this->registeredFieldValues[$field][$fieldValue]) - 1;
			$this->registeredFieldValues[$field][$fieldValue][$c]->isLast = false;
		}
		$val->isLast                                        = true;
		$this->registeredFieldValues[$field][$fieldValue][] = $val;
	}
	
	public function mergeRegisteredFieldValues($mergeable)
	{
		$this->registeredFieldValues = array_merge($this->registeredFieldValues, $mergeable);
	}
	
	public function constructPages($limit, $perpage, $countAll = 0)
	{
		$this->PagesInfo           = new stdClass();
		$this->PagesInfo->limit    = $limit;
		$this->PagesInfo->perpage  = $perpage;
		$this->PagesInfo->countAll = $countAll;
		$this->PagesInfo->pages    = [];
		for ($i = 1; $i <= $countAll; $i++) //Actually this can be slowed with range function
		{
			$this->PagesInfo->pages[] = $i;
		}
		$this->PagesInfo->pages = array_chunk($this->PagesInfo->pages, $perpage);
		$pages                  = [];
		array_walk($this->PagesInfo->pages, function (&$value, $key) use (&$pages)
		{
			$pages[] = $key + 1;
		});
		$this->PagesInfo->pages = $pages;
		if ($limit == 0)
		{
			$curPage = 1;
		}
		else
		{
			$curPage = ($limit / $perpage) + 1;
		}
		$this->PagesInfo->current = $curPage;
		$count                    = count($pages);
		$this->PagesInfo->count   = $count;
		$this->PagesInfo          = $this->constructPagesInfo($this->PagesInfo, $pages, $count, $curPage, $perpage, $limit);
	}
	//##################### EOF manipulation
}

?>