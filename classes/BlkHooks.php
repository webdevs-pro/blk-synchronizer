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
}

