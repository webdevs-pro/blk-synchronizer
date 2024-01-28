<?php

function blk_synk_log( $value, $date = '' ) {
	if ( ! file_exists( BLK_SYNCHRONIZER_LOGS_PATH ) ) {
		 mkdir( BLK_SYNCHRONIZER_LOGS_PATH, 0755, true );
	}
	$log_file = 'synchronizer-' . ( empty( $date ) ? date_i18n('Y-m-d-H-i-s') : $date ) . '.log';
	file_put_contents( BLK_SYNCHRONIZER_LOGS_PATH . $log_file, date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n", FILE_APPEND );
}

function blk_debug_log( $value ) {
	if ( ! file_exists( BLK_SYNCHRONIZER_LOGS_PATH ) ) {
		 mkdir( BLK_SYNCHRONIZER_LOGS_PATH, 0755, true );
	}
	$log_file = 'debug-' . date_i18n('Y-m-d') . '.log';
	file_put_contents( BLK_SYNCHRONIZER_LOGS_PATH . $log_file, date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n", FILE_APPEND );
}

function blk_error_log( $value ) {
	if ( ! file_exists( BLK_SYNCHRONIZER_LOGS_PATH ) ) {
		 mkdir( BLK_SYNCHRONIZER_LOGS_PATH, 0755, true );
	}
	$log_file = 'errors-' . date_i18n('Y-m-d') . '.log';
	file_put_contents( BLK_SYNCHRONIZER_LOGS_PATH . $log_file, date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n", FILE_APPEND );
}
