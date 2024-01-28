<?php

require_once '../../../../wp-load.php';
require_once '../classes/BlkSynchronizer.php';

$bs = new BlkSynchronizer();
$bs->deleteAllProducts();

die();
