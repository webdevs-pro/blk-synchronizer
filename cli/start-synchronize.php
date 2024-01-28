<?php

require( '../../../../wp-load.php' );
require_once '../classes/BlkSynchronizer.php';

$bs = new BlkSynchronizer();
$bs->startSynchronize();

die();
