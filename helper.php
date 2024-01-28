<?php


function blk_synk_log( $value, $date = '' ) {
	$log_directory = BLK_SYNCHRONIZER_PATH . 'logs/';
	if ( ! file_exists( $log_directory ) ) {
		 mkdir( $log_directory, 0755, true );
	}
	file_put_contents( $log_directory . 'synchronizer-' . $date . '.log', date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n", FILE_APPEND );
}

function blk_debug_log( $value ) {
	$log_directory = BLK_SYNCHRONIZER_PATH . 'logs/';
	if ( ! file_exists( $log_directory ) ) {
		 mkdir( $log_directory, 0755, true );
	}
	file_put_contents( $log_directory . 'debug-' . date_i18n('Y-m-d') . '.log', date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n", FILE_APPEND );
}

function blk_error_log( $value ) {
	$log_directory = BLK_SYNCHRONIZER_PATH . 'logs/';
	if ( ! file_exists( $log_directory ) ) {
		 mkdir( $log_directory, 0755, true );
	}
	file_put_contents( $log_directory . 'errors-' . date_i18n('Y-m-d') . '.log', date_i18n('Y-m-d H:i:s') . ' ' . $value . "\n", FILE_APPEND );
}

