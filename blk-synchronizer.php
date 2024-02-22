<?php
/**
 * @package       BaseLinker Synchronizer
 * @author        Alex Shram
 *
 * @wordpress-plugin
 * Plugin Name:   BaseLinker Synchronizer Extended
 * Plugin URI:    https://github.com/webdevs-pro/blk-synchronizer
 * Description:   Synchronization of BaseLinker and WooCommerce
 * Version:       2.12
 * Author:        Alex Shram & Alex Ishchenko
 * Author URI:    https://afisza.com/
 */

define( 'BLK_SYNCHRONIZER_PATH', plugin_dir_path( __FILE__ ) );
define( 'BLK_SYNCHRONIZER_DIR_URL', plugin_dir_url( __FILE__ ) );
if ( ! function_exists( 'get_plugin_data' ) ) {
    require_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
}
define( 'BLK_PLUGIN_VERSION', get_plugin_data( __FILE__ )['Version'] );

define( 'BLK_SYNCHRONIZER_LOGS_PATH', WP_CONTENT_DIR . '/uploads/blk-logs/' );
define( 'BLK_SYNCHRONIZER_LOGS_URL', content_url( '/uploads/blk-logs/' ) );

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

require_once BLK_SYNCHRONIZER_PATH . 'classes/BlkSynchronizer.php';
require_once BLK_SYNCHRONIZER_PATH . 'classes/BaseLinkerHelper.php';
require_once BLK_SYNCHRONIZER_PATH . 'classes/BlkHooks.php';
require_once BLK_SYNCHRONIZER_PATH . 'classes/BlkEndpoints.php';
require_once BLK_SYNCHRONIZER_PATH . 'helper.php';
require_once BLK_SYNCHRONIZER_PATH . 'admin.php';

if (class_exists(BlkSynchronizer::class)) {
    $bs        = new BlkSynchronizer();
    $endpoints = new BlkEndpoints( $bs );
    $hooks     = new BlkHooks( $bs );
}

/**
 * Checks if the import lock file exists and removes it if older than 30 minutes.
 *
 * @return bool True if the lock file exists and is not older than 30 minutes, false otherwise.
 */
function blk_is_import_locked() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-lock';

    if (file_exists($lock_file)) {
        // Check if the file is older than 30 minutes
        $file_time = filemtime($lock_file);
        $current_time = time();
        $time_diff = $current_time - $file_time;

        if ($time_diff > 600) { // 1800 seconds = 30 minutes
            // Remove the lock file if it's older than 30 minutes
            unlink($lock_file);
            return false; // The lock file was too old and has been removed
        }

        return true; // The lock file exists and is not too old
    }

    return false; // The lock file does not exist
}

/**
 * Creates the import lock file.
 *
 * @return void
 */
function blk_create_import_lock() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-lock';
    file_put_contents( $lock_file, date_i18n('Y-m-d H:i:s') );
}

/**
 * Removes the import lock file.
 *
 * @return void
 */
function blk_remove_import_lock() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-lock';
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}

/**
 * Checks if the import stop file exists and is not older than 30 minutes.
 * If the file is older, it removes the stop file.
 *
 * @return bool True if the stop file exists and is within the time limit, false otherwise.
 */
function blk_is_stop_import() {
    $stop_file = plugin_dir_path(__FILE__) . '.import-stop';

    if (file_exists($stop_file)) {
        // Check if the file is older than 30 minutes
        $file_time = filemtime($stop_file);
        $current_time = time();
        if (($current_time - $file_time) > 600) { // 1800 seconds = 30 minutes
            // Remove the stop file if it's older than 30 minutes
            unlink($stop_file);
            return false;
        }
        return true;
    }

    return false;
}

/**
 * Creates the import stop file.
 *
 * @return void
 */
function blk_create_import_stop() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-stop';
    file_put_contents( $lock_file, date_i18n('Y-m-d H:i:s') );
}

/**
 * Removes the import stop file.
 *
 * @return void
 */
function blk_remove_import_stop() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-stop';
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}


add_action( 'wp_ajax_blk_stop_import', function() {
    blk_create_import_stop();
    echo 'Stop import file created.';
    wp_die();
} );


require_once ( BLK_SYNCHRONIZER_PATH . 'vendor/autoload.php' );
$UpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/webdevs-pro/blk-synchronizer',
    __FILE__,
    'blk-synchronizer'
);

//Set the branch that contains the stable release.
$UpdateChecker->setBranch( 'main' );