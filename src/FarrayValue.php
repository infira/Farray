<?php

namespace Infira\Farray;

use Infira\Farray\plugins\Farray_Abs;
use Infira\Utils\Variable;
use Infira\Utils\Is;
use Infira\Utils\Date;
use Infira\Utils\Fix;
use Infira\Utils\Regex;
use Infira\Utils\Vat;
use Infira\Utils\Error;

class FarrayValue
{
	private $TYPE = 'value';
	use \FarrayValueExtendor;
	use Farray_Abs;
	
	public $value; //actual current value
	
	/**
	 * Original value wich was setted by __constructor
	 *
	 * @var mixed
	 */
	protected $origVal = false;
	
	public $takeNewValue = false;
	
	public function __get($name)
	{
		$this->error('FarrayValue->__get : You are tring to get variable <B>"' . $name . '</B>" but it doesn\'t exits in ' . get_class($this) . ' class');
	}
	
	public function __call($method, $args)
	{
		$this->error("FarrayValue method($method) is not callable ");
	}
	
	public function __construct($field, $val, &$ListNode = false)
	{
		if ($ListNode !== false and !is_object($ListNode))
		{
			$this->error("List node should be object");
		}
		if ($this->isFarrayValue($val) or $this->isList($val))
		{
			$this->error('$val cannot be instance of FarrayValue OR FarrayList');
		}
		$this->nodeField   = $field;
		$this->value       = $val;
		$this->origVal     = $val;
		$this->tagsCreated = 0;
		if ($ListNode)
		{
			$this->setNode($ListNode);
		}
	}
	
	public function __toString()
	{
		return (string)$this->val();
	}
	
	public function val()
	{
		return $this->value;
	}
	
	/**
	 * Take new value to self instead of returning $newValue
	 */
	public function take()
	{
		$this->takeNewValue = true;
		
		return $this;
	}
	
	/**
	 * Generates new cloned this value
	 *
	 * @param mixed $newVal
	 * @param bool  $returnThis
	 * @return $this
	 */
	public function newValue($newVal, $returnThis = true)
	{
		if (!$returnThis)
		{
			return $newVal;
		}
		if ($this->takeNewValue)
		{
			$newThis = $this;
		}
		else
		{
			$newThis = clone $this;
		}
		$newThis->set($newVal);
		$newThis->takeNewValue = false;
		
		return $newThis;
	}
	
	/**
	 * Gets the original what was setted during construction
	 *
	 * @return mixed
	 */
	public function getOrigValue()
	{
		return $this->origVal;
	}
	
	/**
	 * What is the current value key at $this->List
	 *
	 * @return string
	 */
	public function getNodeField()
	{
		return $this->nodeField;
	}
	
	/**
	 * @param mixed $newValue
	 * @return $this
	 */
	public function set($newValue)
	{
		$this->value = $newValue;
		
		return $this;
	}
	
	/**
	 * Relatet $this to listNode
	 *
	 * @param $Node
	 */
	public function setNode(&$Node)
	{
		$this->Node = &$Node;
	}
	
	public function debug()
	{
		return debug($this->value);
	}
	
	public function dump()
	{
		return dump($this->value);
	}
	
	/**
	 * Set parser during getting value
	 *
	 * @param callable    $parser
	 * @param object|null $scope - optional
	 */
	public function setFieldValueParser(callable $parser, object $scope = null)
	{
		$this->_setFieldValueParser(null, $parser, $scope);
	}
	
	
	///////////////////////////////////////////////
	
	/**
	 * Is in array
	 *
	 * @param mixed $val
	 * @return bool
	 */
	public function in($val)
	{
		return in_array($this->value, Variable::toArray($val));
	}
	
	/**
	 * Is not in
	 *
	 * @param mixed $val
	 * @return bool
	 */
	public function notIn($val)
	{
		return !$this->in($val);
	}
	
	/**
	 * Is value
	 *
	 * @param mixed $val
	 * @param bool  $compareStrict use === for comparison
	 * @return bool
	 */
	public function is($val, bool $compareStrict = false)
	{
		if ($compareStrict)
		{
			return $this->value === $val;
		}
		addExtraErrorInfo('$this->value', $this->value);
		
		return $this->value == $val;
	}
	
	/**
	 * Is not $val
	 *
	 * @param mixed $val
	 * @return bool
	 */
	public function isnt($val)
	{
		return $this->value != $val;
	}
	
	/**
	 * Alias to inst
	 *
	 * @param mixed $val
	 * @see $this->isnt()
	 * @return bool
	 */
	public function isNot($val)
	{
		return $this->isnt($val);
	}
	
	/**
	 * Alias to inst
	 *
	 * @param mixed $val
	 * @see $this->isnt()
	 * @return bool
	 */
	public function not($val)
	{
		return $this->isnt($val);
	}
	
	/**
	 * Is value between
	 *
	 * @param int|float $from
	 * @param int|float $to
	 * @return bool
	 */
	public function between($from, $to)
	{
		if ($this->value >= $from and $this->value <= $to)
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Check is $value a faile and does it exists
	 *
	 * @return bool
	 */
	public function exists()
	{
		if ($this->ok())
		{
			return file_exists($this->value);
		}
		
		return false;
	}
	
	/**
	 * Is value not empty
	 *
	 * @return bool
	 */
	public function ok(): bool
	{
		return !empty($this->value);
	}
	
	/**
	 * Is value not empty
	 *
	 * @return bool
	 */
	public function notOk(): bool
	{
		return !$this->ok();
	}
	
	/**
	 * Is html empty or not
	 *
	 * @param string $voidTags - in case ohtml tags to void on stripping, see http://php.net/manual/en/function.strip-tags.php
	 * @return bool
	 */
	public function isHTMLOk(string $voidTags = ""): bool
	{
		$val = $this->value;
		if (is_string($this->value))
		{
			$val = trim(Variable::htmlToText($val, $voidTags));
		}
		
		return !empty($val);
	}
	
	/**
	 * Alias to ok()
	 *
	 * @see $this->ok()
	 * @return bool
	 */
	public function notEmpty(): bool
	{
		return $this->ok();
	}
	
	/**
	 * Alias to notOk()
	 *
	 * @see $this->notOk()
	 * @return bool
	 */
	public function empty(): bool
	{
		return $this->notOk();
	}
	
	/**
	 * Is array and has values
	 *
	 * @return bool
	 */
	public function checkArray()
	{
		return (checkArray($this->value));
	}
	
	/**
	 * Is bigger than $to
	 *
	 * @param int|float $to
	 * @return bool
	 */
	public function isBigger($to)
	{
		return ($this->value > $to);
	}
	
	/**
	 * Is bigger or equal to $to
	 *
	 * @param int|float $to
	 * @return bool
	 */
	public function isBiggerEq($to)
	{
		return ($this->value >= $to);
	}
	
	/**
	 * Is smaller than $to
	 *
	 * @param int|float $to
	 * @return bool
	 */
	public function isSmaller($to)
	{
		return ($this->value < $to);
	}
	
	/**
	 * Is smaller or equal to $to
	 *
	 * @param int|float $to
	 * @return bool
	 */
	public function isSmallerEq($to)
	{
		return ($this->value <= $to);
	}
	
	/**
	 * Check if this value contains string
	 *
	 * @param string $str            - what to check
	 * @param bool   $convertToLower - use strtolower on check
	 * @return bool
	 */
	public function contains(string $str, $convertToLower = false): bool
	{
		$val = $this->value;
		if ($convertToLower)
		{
			$str = Variable::toLower($str);
			$val = Variable::toLower($val);
		}
		
		return (strpos($val, $str) === false) ? false : true;
	}
	
	//##################### SOF Properties
	
	/**
	 * Alias to length
	 */
	public function len(): int
	{
		return $this->length();
	}
	
	/**
	 * Returns this class value string length
	 *
	 * @return int
	 */
	public function length(): int
	{
		return strlen($this->value);
	}
	
	/**
	 * Is regular expression match
	 *
	 * @param $pattern
	 * @return bool
	 */
	public function match($pattern): bool
	{
		return Is::match($pattern, $this->value);
	}
	
	/**
	 * count value
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->value);
	}
	
	/**
	 * Get image width
	 *
	 * @return int
	 */
	public function width()
	{
		if (!$this->ok())
		{
			return 0;
		}
		if (!file_exists($this->value))
		{
			return 0;
		}
		$size = getimagesize($this->value);
		
		return $size[0];
	}
	
	/**
	 * Get image height
	 *
	 * @return int
	 */
	public function height()
	{
		if (!$this->ok())
		{
			return 0;
		}
		if (!file_exists($this->value))
		{
			return 0;
		}
		$size = getimagesize($this->value);
		
		return $size[1];
	}
	
	//##################### EOF Properties
	
	//##################### EOF Modifiers
	/**
	 * Round value
	 *
	 * @param int $decimals
	 * @return $this
	 */
	public function round(int $decimals = 2)
	{
		return $this->newValue(round($this->value, $decimals));
	}
	
	/**
	 * Use vsprintf
	 *
	 * @return $this
	 */
	public function sprintf()
	{
		$newVal = vsprintf(str_replace(["%s|", "|%s"], "%s", $this->value), func_get_args());
		
		return $this->newValue($newVal);
	}
	
	/**
	 * Use vsprintf
	 *
	 * @param array $args
	 * @return string
	 */
	public function vsprintf(array $args)
	{
		$newValue = vsprintf($this->value, $args);
		
		return $this->newValue($newValue);
	}
	
	/**
	 * Convert value to timestamp using strtime
	 *
	 * @param string|int|null $time - when $time IS NULL strtotime($this->value) ELSE strtotime($time,$this->value)
	 * @return $this
	 */
	public function toTime($time = null)
	{
		if ($time)
		{
			return $this->newValue(Date::toTime($time, $this->value));
		}
		else
		{
			return $this->newValue(Date::toTime($this->value));
		}
		
	}
	
	public function format($text, $arg1 = null)
	{
		if ($this->contains("%val%") or $this->contains("%value%"))
		{
			$newVal = Variable::assign(["value" => $text, "val" => $text], $this->value);
		}
		else
		{
			$newVal = Variable::assign(["value" => $this->value, "val" => $this->value], $text);
		}
		if ($arg1 !== null)
		{
			$newVal = vsprintf($newVal, array_slice(func_get_args(), 1));
		}
		
		return $this->newValue($newVal);
	}
	
	/**
	 * Format price
	 *
	 * @param string $currency
	 * @param bool   $removeZeros same as str_replace(".00",""
	 * @param bool   $removeTenth
	 * @return $this
	 */
	public function formatPrice($currency = "", $removeZeros = false, $removeTenth = false)
	{
		if (defined("ARRAYLISTNOTEVAL_FORMAT_PRICE_REMOVE_ZEROZ"))
		{
			$removeZeros = ARRAYLISTNOTEVAL_FORMAT_PRICE_REMOVE_ZEROZ;
		}
		if (defined("ARRAYLISTNOTEVAL_FORMAT_PRICE_REMOVE_TENTH"))
		{
			$removeTenth = ARRAYLISTNOTEVAL_FORMAT_PRICE_REMOVE_TENTH;
		}
		
		return $this->newValue(Fix::price(Variable::toNumber($this->value), $removeTenth, $removeZeros) . $currency);
	}
	
	/**
	 * Format as eur
	 *
	 * @param string $unit
	 * @return $this
	 */
	public function eur($unit = "€")
	{
		return $this->formatPrice($unit);
	}
	
	
	/**
	 * Format file size
	 *
	 * @return $this
	 */
	public function formatSize()
	{
		$bytes     = intval($this->value);
		$units     = ['B', 'KB', 'MB', 'GB'];
		$converted = $bytes . ' ' . $units[0];
		for ($i = 0; $i < count($units); $i++)
		{
			if (($bytes / pow(1024, $i)) >= 1)
			{
				$converted = round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
			}
		}
		
		return $this->newValue($converted);
	}
	
	/**
	 * Format date value
	 *
	 * @param string $format - defaults to d.m.Y
	 * @return $this
	 */
	public function formatDate($format = "d.m.Y")
	{
		return $this->newValue(Date::toDate($this->value, $format));
	}
	
	/**
	 * Formate to SQL date(Y-m-d)
	 *
	 * @see https://www.php.net/manual/en/function.date.php
	 * @return $this
	 */
	public function formatSqlDate()
	{
		return $this->formatDate("Y-m-d");
	}
	
	
	/**
	 * Formate date using format = d.m.Y H:i:s
	 *
	 * @see https://www.php.net/manual/en/function.date.php
	 * @return $this
	 */
	public function formatDateTime()
	{
		return $this->formatDate("d.m.Y H:i:s");
	}
	
	/**
	 * Formate date using format = j. F Y
	 *
	 * @see https://www.php.net/manual/en/function.date.php
	 * @return $this
	 */
	public function formatDateNice()
	{
		return $this->formatDate('j. F Y');
	}
	
	/**
	 * Formate date using format = j. F Y H:i:s
	 *
	 * @see https://www.php.net/manual/en/function.date.php
	 * @return $this
	 */
	public function formatDateTimeNice()
	{
		return $this->formatDate('j. F Y H:i:s');
	}
	
	/**
	 * Formate date using format = H:i
	 *
	 * @see https://www.php.net/manual/en/function.date.php
	 * @return $this
	 */
	public function formatTimeNice()
	{
		return $this->formatDate('H:i');
	}
	
	/**
	 * Format phone
	 *
	 * @param string $prefix
	 * @return $this
	 */
	public function formatPhone($prefix = "")
	{
		return $this->newValue(Fix::phone($this->value, $prefix));
	}
	
	/**
	 * Slice value to first $char
	 *
	 * @param string $char
	 * @return mixed
	 */
	public function sliceToChar(string $char)
	{
		return $this->newValue(substr($this->value, 0, strpos($this->value, $char) + 1));
	}
	
	/**
	 * Fix comma and spaces, comma in text is textText, textText
	 *
	 * @return $this
	 */
	public function fixCommaSpaces()
	{
		$val = str_replace(" , ", ",", $this->value);
		$val = str_replace(", ", ",", $val);
		$val = str_replace(" ,", ",", $val);
		$val = str_replace(",", ", ", $val);
		
		return $this->newValue($val);
	}
	
	/**
	 * Convert encoding to utf8
	 *
	 * @return $this
	 */
	public function toUTF8()
	{
		return $this->newValue(Variable::toUTF8($this->value));
	}
	
	/**
	 * Use parseStr to convert value to array
	 *
	 * @return $this
	 */
	public function parseStr()
	{
		return $this->newValue(parseStr($this->value));
	}
	
	/**
	 * Concat value
	 *
	 * @param $val
	 * @return $this
	 */
	public function concat($val)
	{
		return $this->newValue($this->value . $val);
	}
	
	/**
	 * Transfrom value to uppercae
	 *
	 * @return $this
	 */
	public function ucFirst()
	{
		return $this->newValue(Variable::ucFirst($this->value));
	}
	
	/**
	 * Transfrom value to uppercae
	 *
	 * @return $this
	 */
	public function urlEncode()
	{
		return $this->newValue(urlencode($this->value));
	}
	
	/**
	 * Fix url name
	 *
	 * @return $this
	 */
	public function urlName()
	{
		return $this->newValue(Fix::urlName($this->value));
	}
	
	/**
	 * Add string to end of the value
	 *
	 * @param mixed $value
	 * @return $this
	 */
	public function prefix($value)
	{
		return $this->newValue($value . $this->value);
	}
	
	/**
	 * Add string to begining of the value
	 *
	 * @param mixed $value
	 * @return $this
	 */
	public function suffix($value)
	{
		return $this->newValue($this->value . $value);
	}
	
	/**
	 * allias to php substr
	 *
	 * @param null|string|int $start
	 * @param null|string|int $end
	 * @throws FarrayError
	 * @return $this
	 */
	public function substr($start = null, $end = null)
	{
		if (is_string($start) and !is_numeric($start))
		{
			$start = strpos($this->value, $start) + 1;
		}
		if (is_string($end) and !is_numeric($end))
		{
			$end = strpos($this->value, $end);
		}
		if ($start !== null and $end !== null)
		{
			$val = substr($this->value, $start, $end);
		}
		elseif ($start === null and $end !== null)
		{
			$val = substr($this->value, 0, $end);
		}
		elseif ($start !== null and $end === null)
		{
			$val = substr($this->value, $start);
		}
		else
		{
			$this->error('$start and $null is not defined');
		}
		
		return $this->newValue($val);
	}
	
	/**
	 * Add,substract, multiply or devide value,
	 *
	 * @param string $op
	 * @param mixed  $val
	 * @throws FarrayError
	 * @return $this
	 * @example ->math('+',10) or ->math('+10')
	 */
	public function math(string $op, $val = null)
	{
		if ($op and $val === null)
		{
			$op  = trim($op);
			$val = substr(str_replace(" ", "", $op), 1);
		}
		else
		{
			$val = str_replace(" ", "", $val);
		}
		$op        = $op{0};
		$mathValue = Variable::toNumber($val);
		$newValue  = $this->value;
		if ($op == "+")
		{
			$newValue = $this->value + $mathValue;
		}
		elseif ($op == "*")
		{
			$newValue = $this->value * $mathValue;
		}
		elseif ($op == "-")
		{
			$newValue = $this->value - $mathValue;
		}
		elseif ($op == "/" or $op == ":")
		{
			$newValue = $this->value / $mathValue;
		}
		else
		{
			$this->error("operation not implemented");
		}
		
		return $this->newValue($newValue);
	}
	
	/**
	 * Increment value by
	 *
	 * @param int|float $by
	 * @return $this
	 */
	public function increment($by = 1)
	{
		$newValue = $this->value;
		$newValue += $by;
		
		return $this->newValue($newValue);
	}
	
	/**
	 * Use ceil
	 *
	 * @return $this
	 */
	public function ceil()
	{
		return $this->newValue(ceil($this->value));
	}
	
	/**
	 * Use floor
	 *
	 * @return $this
	 */
	public function floor()
	{
		return $this->newValue(floor($this->value));
	}
	
	/**
	 * Alias to split
	 *
	 * @param string $delimiter
	 * @see $this->split()
	 * @return $this
	 */
	public function explode(string $delimiter = ",")
	{
		return $this->split($delimiter);
	}
	
	/**
	 * Convert value to array using explode
	 *
	 * @param string $delimiter
	 * @return $this
	 */
	public function split(string $delimiter = ",")
	{
		$ex = explode($delimiter, $this->value);
		
		return $this->newValue($ex);
	}
	
	/**
	 * use htmlspecialchars
	 *
	 * @return $this
	 */
	public function htmlspecialchars()
	{
		return $this->newValue(htmlspecialchars($this->value));
	}
	
	/**
	 * Convert nl to <br>
	 *
	 * @return $this
	 */
	public function nl2br()
	{
		return $this->newValue(Fix::nl2br($this->value));
	}
	
	/**
	 * Replace <br> to nl
	 *
	 * @return $this
	 */
	public function br2nl()
	{
		return $this->newValue(str_replace("<br />", "\n", Fix::nl2br($this->value, true)));
	}
	
	/**
	 * Convert current value to <img src="$value"..
	 *
	 * @param string $title
	 * @return string
	 */
	public function img($title = "")
	{
		return vsprintf('<img src="%s" alt="%s" />', [$this->value, $title]);
	}
	
	/**
	 * Get file base name
	 *
	 * @return $this
	 */
	public function basename()
	{
		return $this->newValue(basename($this->value));
	}
	
	/**
	 * Fix file name
	 *
	 * @return $this
	 */
	public function fixFileName()
	{
		return $this->newValue(Fix::fileName($this->value));
	}
	
	/**
	 * Get as youtube embed link
	 *
	 * @param string $urlParams
	 * @return $this
	 */
	public function youtubeEmbed($urlParams = "")
	{
		if (!Is::match('%http://www\.youtube\.com/embed/%i', $this->value))
		{
			$val = str_replace("https://www.youtube.com/watch?v=", "", $this->value);
			$val = str_replace("http://youtu.be/", "", $val);
			if (strpos($val, "?") !== false)
			{
				$sp = explode("?", $val);
				if (array_key_exists(1, $sp))
				{
					$val = $sp[1];
					$sp  = explode("&", $val);
					for ($i = 0; $i < count($sp); $i++)
					{
						if (substr($sp[$i], 0, 2) == "v=")
						{
							$val = substr($sp[$i], 2);
							break;
						}
					}
				}
			}
			if ($urlParams)
			{
				$val .= "?" . $urlParams;
			}
			
			return $this->newValue("https://www.youtube.com/embed/$val");
		}
		
		return $this;
	}
	
	/**
	 * Makes current value int
	 *
	 * @return $this
	 */
	public function int()
	{
		return $this->newValue($this->toInt());
	}
	
	/**
	 * Convert to int
	 *
	 * @return int
	 */
	public function toInt(): int
	{
		return intval($this->value);
	}
	
	/**
	 * Convert to bool
	 *
	 * @param bool $parseAlsoString - if set to true, then (string)"true" is converted to (bool)true, and (string)"false" ==> (bool)false
	 * @return $this
	 */
	public function bool(bool $parseAlsoString)
	{
		return $this->newValue(Variable::toBool($this->value, $parseAlsoString));
	}
	
	/**
	 * Convert to float
	 *
	 * @return $this
	 */
	public function float()
	{
		return $this->newValue($this->toFloat());
	}
	
	/**
	 * Convert to float
	 *
	 * @return float
	 */
	public function toFloat(): float
	{
		return floatval($this->value);
	}
	
	
	/**
	 * Convert value to number
	 *
	 * @return $this
	 */
	public function toNumber()
	{
		return $this->newValue(Variable::toNumber($this->value));
	}
	
	/**
	 * Convert value to negative number
	 *
	 * @return $this
	 */
	public function toNegative()
	{
		return $this->newValue(Variable::toNegative($this->value));
	}
	
	/**
	 * Convert value to positive number
	 *
	 * @return $this
	 */
	public function toPositive()
	{
		return $this->newValue(Variable::toPositive($this->value));
	}
	
	/**
	 * Assign variables to value
	 *
	 * @param array $vars
	 * @return $this
	 */
	public function assignVars(array $vars)
	{
		return $this->newValue(Variable::assign($vars, $this->value));
	}
	
	/**
	 * Assign variable
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return $this|mixed|string
	 */
	public function assignVar(string $name, $value)
	{
		return $this->assignVars([$name => $value]);
	}
	
	/**
	 * Strip html tags
	 *
	 * @param null|string|array $voidTags - html tags to void on stripping, see http://php.net/manual/en/function.strip-tags.php-
	 * @return $this
	 */
	public function clean($voidTags = null)
	{
		return $this->newValue(Variable::htmlToText($this->value, $voidTags));
	}
	
	/**
	 * Convert value to md5
	 *
	 * @return $this
	 */
	public function md5()
	{
		return $this->newValue(md5($this->value));
	}
	
	/**
	 * Same as str_replace(",00","",$value)
	 *
	 * @return $this
	 */
	public function removeCommaNull()
	{
		return $this->newValue(str_replace([",00", ".00"], "", $this->value));
	}
	
	/**
	 * Round up 5 cents
	 *
	 * @return $this
	 */
	public function roundUpTo5Cents()
	{
		return $this->newValue(Variable::roundUpTo5Cents($this->value));
	}
	
	/**
	 * truncate number
	 *
	 * @param $decmals
	 * @return $this
	 */
	public function truncateNumber($decmals)
	{
		return $this->newValue(Variable::truncateNumber($this->value, $decmals));
	}
	
	/**
	 * Replace part of string
	 *
	 * @param string $search
	 * @param string $replace
	 * @return $this
	 */
	public function replace(string $search, $replace = "")
	{
		return $this->newValue(str_replace($search, $replace, $this->value));
	}
	
	/**
	 * @param string $pattern
	 * @param string $replace
	 * @return $this
	 */
	public function pregReplace(string $pattern, $replace = "")
	{
		return $this->newValue(preg_replace($pattern, $replace, $this->value));
	}
	
	/**
	 * Take parts of text
	 *
	 * @param string - $regex
	 * @return $this
	 */
	public function matches($regex)
	{
		return $this->newValue(Regex::getMatch($regex, $this->value));
	}
	
	/**
	 * Trims value
	 *
	 * @return $this
	 */
	public function trim()
	{
		return $this->newValue(trim($this->value));
	}
	
	/**
	 * Convert to upper case
	 *
	 * @return $this
	 */
	public function toUpper()
	{
		return $this->newValue(Variable::toUpper($this->value));
	}
	
	/**
	 * Convert to lower case
	 *
	 * @return $this
	 */
	public function toLower()
	{
		return $this->newValue(Variable::toLower($this->value));
	}
	
	/**
	 * Converts value to array using explode(",")
	 *
	 * @return $this
	 */
	public function toArray()
	{
		return $this->newValue(Variable::toArray($this->value));
	}
	
	/**
	 * Get random item from array
	 *
	 * @return $this
	 */
	public function randomItem()
	{
		$items = $this->value;
		
		return $this->newValue($items[array_rand($items)]);
	}
	
	/**
	 * Converts value to FarrayList
	 *
	 * @return $this
	 */
	public function toList()
	{
		$List = new FarrayList($this->toArray()->val());
		$List->construct();
		
		return $this->newValue($List);
	}
	
	/**
	 * Remove VAT from value
	 *
	 * @param int|float|null $vatPercent
	 * @return $this
	 */
	public function removeVat($vatPercent = null)
	{
		return $this->newValue(Vat::remove($this->value, $vatPercent));
	}
	
	/**
	 * Add VAT to value
	 *
	 * @param int|float|null $vatPercent
	 * @return $this
	 */
	public function addVat($vatPercent = null)
	{
		return $this->newValue(Vat::add($this->value, $vatPercent));
	}
	
	/**
	 * vat value
	 *
	 * @param bool           $priceContainsVat
	 * @param null|int|float $vatPercent
	 * @return $this
	 */
	public function vat(bool $priceContainsVat, $vatPercent = null)
	{
		return $this->newValue(Vat::get($this->value, $priceContainsVat, $vatPercent));
	}
	
	/**
	 * Add markup
	 *
	 * @param int|float $perecent
	 * @return $this
	 */
	public function addMarkup($perecent)
	{
		return $this->newValue($this->value + ($this->value * ($perecent / 100)));
	}
	
	/**
	 * Get amount of $value by $percent
	 *
	 * @param int|float $percent
	 * @return $this
	 */
	public function getAmmountByPercent($percent)
	{
		$sum     = $this->toFloat();
		$percent = floatval($percent);
		$newVal  = ($sum * $percent) / 100;
		
		return $this->newValue($newVal);
	}
	
	/**
	 * Convert value to array and gets a random item fro it
	 *
	 * @return $this
	 */
	public function random()
	{
		$items = $this->toArray()->val();
		
		return $this->newValue($items[array_rand($items)]);
	}
	
	/**
	 * Convert value to array and calls $callback from each item
	 *
	 * @param callable $callback
	 * @param null     $scope
	 * @throws Error
	 * @return array
	 */
	public function each(callable $callback, $scope = null)
	{
		$r = [];
		foreach ($this->toArray()->val() as $key => $v)
		{
			$v       = callback($callback, $scope, [$v]);
			$r[$key] = new $this($key, $v);
		}
		
		return $r;
	}
	
	//##################### EOF Modifiers
}

?>