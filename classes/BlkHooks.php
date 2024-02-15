<?php

class BlkHooks {
    /**
     * Holds an instance of the BlkSynchronizer class.
     *
     * @var BlkSynchronizer
     */
    public $bs;

    /**
     * Constructor.
     *
     * @param BlkSynchronizer $bs Instance of BlkSynchronizer.
     */
    public function __construct( $bs ) {
        $this->bs = $bs;

        add_action( 'init', array( $this, 'initialize_hooks' ) );
    }

    /**
     * Initializes WordPress hooks.
     */
    public function initialize_hooks() {
        add_filter( 'cron_schedules', array( $this, 'add_custom_cron_intervals' ) );
        add_action( 'blkSynchronizer', array( $this, 'startBlkSynchronize' ) );
        add_action( 'update_option_blk_settings', array( $this, 'change_cron_interval' ), 10, 2 );

        if ( ! wp_next_scheduled( 'blkSynchronizer' ) ) {
            wp_schedule_event( time(), $this->get_schedule_name(), 'blkSynchronizer' );
        }
    }

    /**
     * Adds or updates custom cron interval based on the saved option.
     *
     * @param array $schedules Current list of schedules.
     * @return array Modified list of schedules with custom interval.
     */
    public function add_custom_cron_intervals( $schedules ) {
        $blk_settings = get_option( 'blk_settings' );
        $interval_minutes = $blk_settings['blk_cron_interval'] ?? 60; // Default to 60 minutes if not set.

        $schedules['blk_custom_interval'] = array(
            'interval' => $interval_minutes * 60, // Convert minutes to seconds.
            'display'  => sprintf( __( 'Every %d Minutes', 'your-text-domain' ), $interval_minutes ),
        );

        return $schedules;
    }

    /**
     * Initiates synchronization.
     */
    public function startBlkSynchronize() {
        $this->delete_old_logs();
        $this->bs->startSynchronize();
    }

    /**
     * Handles changes in the cron interval when the option is updated.
     *
     * @param mixed $old_value Previous option value.
     * @param mixed $new_value New option value.
     */
    public function change_cron_interval( $old_value, $new_value ) {
        if ( isset( $old_value['blk_cron_interval'], $new_value['blk_cron_interval'] ) && $old_value['blk_cron_interval'] !== $new_value['blk_cron_interval'] ) {
            wp_clear_scheduled_hook( 'blkSynchronizer' );
            wp_schedule_event( time(), $this->get_schedule_name(), 'blkSynchronizer' );
        }
    }

    /**
     * Retrieves the schedule name, dynamically setting it based on the current option.
     *
     * @return string The schedule name.
     */
    private function get_schedule_name() {
        return 'blk_custom_interval';
    }


    /**
     * Deletes log files older than 5 days in the BLK_SYNCHRONIZER_LOGS_PATH directory.
     */
    public function delete_old_logs() {
        // Ensure the constant is defined before proceeding.
        if ( ! defined( 'BLK_SYNCHRONIZER_LOGS_PATH' ) ) {
            return;
        }

        $log_files_path = trailingslashit( BLK_SYNCHRONIZER_LOGS_PATH ) . '*.log';
        $log_files      = glob( $log_files_path );
        if ( false === $log_files ) {
            // Glob may return false on error.
            return;
        }

        $age_threshold = 5 * DAY_IN_SECONDS; // WordPress constant for time calculations.
        $now           = time();
        $deleted_files = array();

        foreach ( $log_files as $log_file ) {
            // Verify the file exists before attempting to get its modification time.
            if ( ! file_exists( $log_file ) ) {
                continue;
            }

            $file_mod_time = filemtime( $log_file );
            if ( false !== $file_mod_time && ( $now - $file_mod_time ) > $age_threshold ) {
                // Attempt to delete the file and check for success.
                if ( ! unlink( $log_file ) ) {
                    // Log error or take necessary action if file deletion fails.
                } else {
                    $deleted_files[] = basename( $log_file );
                }
            }
        }

        if ( ! empty( $deleted_files ) ) {
            blk_debug_log( 'Old log files deleted: ' . print_r( $deleted_files, true ) );
        }
    }
}

