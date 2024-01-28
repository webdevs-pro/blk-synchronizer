<?php


function blk_synk_log( $value, $date = '' ) {
	file_put_contents( BLK_SYNCHRONIZER_PATH . 'logs/synchronizer-' . $date . '.log', date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n" , FILE_APPEND );
}

function blk_debug_log( $value ) {
	file_put_contents( BLK_SYNCHRONIZER_PATH . 'logs/debug-' . date_i18n('Y-m-d') . '.log', date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n" , FILE_APPEND );
}

function blk_error_log( $value ) {
	file_put_contents( BLK_SYNCHRONIZER_PATH . 'logs/errors-' . date_i18n('Y-m-d') . '.log', date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n" , FILE_APPEND );
}
