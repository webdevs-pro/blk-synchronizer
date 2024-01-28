<?php
/**
 * @package       BaseLinker Synchronizer
 * @author        Alex Shram
 *
 * @wordpress-plugin
 * Plugin Name:   BaseLinker Synchronizer Extended
 * Plugin URI:    https://github.com/webdevs-pro/blk-synchronizer
 * Description:   Synchronization of BaseLinker and WooCommerce
 * Version:       2.4.2
 * Author:        Alex Shram & Alex Ishchenko
 * Author URI:    https://afisza.com/
 */

define( 'BLK_SYNCHRONIZER_PATH', plugin_dir_path( __FILE__ ) );
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
 * Checks if the import lock file exists.
 *
 * @return bool True if the lock file exists, false otherwise.
 */
function blk_is_import_locked() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-lock';

    return file_exists($lock_file);
}

/**
 * Creates the import lock file.
 *
 * @return void
 */
function blk_create_import_lock() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-lock';
    file_put_contents($lock_file, 'locked');
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
 * Checks if the import stop file exists.
 *
 * @return bool True if the lock file exists, false otherwise.
 */
function blk_is_stop_import() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-stop';

    return file_exists($lock_file);
}

/**
 * Creates the import stop file.
 *
 * @return void
 */
function blk_create_import_stop() {
    $lock_file = plugin_dir_path(__FILE__) . '.import-stop';
    file_put_contents($lock_file, 'locked');
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