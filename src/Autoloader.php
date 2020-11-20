<?php


namespace Infira\Farray;
class Autoloader
{
	private static $paths = [];
	
	public static function loader($className)
	{
		if (in_array($className, array_keys(self::$paths)))
		{
			require_once(self::$paths[$className]);
		}
		
		return true;
	}
	
	public static function setFarrayExtendorPath(string $path)
	{
		self::setPath('FarrayExtendor', $path);
	}
	
	public static function setFarrayNodeExtendorPath(string $path)
	{
		self::setPath('FarrayNodeExtendor', $path);
	}
	
	public static function setFarrayValueExtendorPath(string $path)
	{
		self::setPath('FarrayValueExtendor', $path);
	}
	
	private static function setPath(string $className, string $path)
	{
		if (!file_exists($path))
		{
			throw new \Exception("Farray autoloader class($className) file($path) does not exists");
		}
		self::$paths[$className] = $path;
	}
	
}

?>