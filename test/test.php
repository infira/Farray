<?php

use Infira\Farray\FarrayList;

$Far = new FarrayList([['name' => 'gen'], ['name' => 'mikk']]);
$Far->construct();
debug($Far->findByFieldValue('name', 'gen'));