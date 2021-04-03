<?php

use Infira\Farray\FarrayList;

require_once "../vendor/autoload.php";
require_once "../src/init.php";
require_once "../src/plugins/Farray_Abs.php";
require_once "../src/plugins/Debug.php";
require_once "../src/FarrayObject.php";
require_once "../src/FarrayValue.php";
require_once "../src/FarrayNode.php";
require_once "../src/FarrayList.php";
require_once "MyFarrayNode.php";


define("ERROR_LEVEL", -1);
$config  = ["errorLevel" => ERROR_LEVEL,//-1 means all erors, see https://www.php.net/manual/en/function.error-reporting.php
            "env"        => "dev", //dev,stable (stable env does not display full errors erros
            "email"      => false, /*
			 * Provide a calllable argument for loging, use your own logger
			 * For example https://github.com/markrogoyski/simplelog-php/blob/master/README.md
			 */
            "log"        => false, "debugBacktraceOption" => 2];
$Handler = new Infira\Error\Handler();

try
{
	require_once __DIR__ . '/test.php';
}
catch (\Infira\Error\Error $e)
{
	cleanOutput(true);
	$msg = $e->getMessage();
	echo $msg;
}
catch (Error $e)
{
	$msg = $Handler->catch($e);
	echo $msg;
}
catch (Exception $e)
{
	$msg = $Handler->catch($e);
	echo $msg;
}