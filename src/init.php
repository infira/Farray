<?php
require_once __DIR__ . '/Autoloader.php';
\Infira\Farray\Autoloader::setFarrayExtendorPath(__DIR__ . '/extendors/FarrayExtendor.php');
\Infira\Farray\Autoloader::setFarrayNodeExtendorPath(__DIR__ . '/extendors/FarrayNodeExtendor.php');
\Infira\Farray\Autoloader::setFarrayValueExtendorPath(__DIR__ . '/extendors/FarrayValueExtendor.php');
spl_autoload_register(['Infira\Farray\Autoloader', 'loader'], true);