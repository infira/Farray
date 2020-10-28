<?php

namespace Infira\Farray;
class Autoloader
{
	public static function loader($className)
	{
		if (in_array($className, ['FarrayExtendor', 'FarrayExtendor2', 'FarrayNodeExtendor', 'FarrayNodeExtendor2', 'FarrayValueExtendor', 'FarrayValueExtendor2']))
		{
			require_once __DIR__ . '/extendors/' . $className . '.php';
		}
		
		return true;
	}
	
}

?>