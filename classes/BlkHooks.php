<?php

class BlkHooks
{
    public BlkSynchronizer $bs;

    public function __construct($bs)
    {
        $this->bs = $bs;
        add_filter('cron_schedules', [$this, 'addBlkCronInterval']);
        add_action('blkSynchronizer', [$this, 'startBlkSynchronize']);
        if (!wp_next_scheduled('blkSynchronizer', [])) {
            wp_schedule_event(time(), 'every_30_min', 'blkSynchronizer', []);
        }
    }

    public function addBlkCronInterval($schedules)
    {
        $schedules['every_30_min'] = [
            'interval' => 1800,
            'display' => 'Every 30 minutes'
        ];
        $schedules['every_5_min'] = [
            'interval' => 300,
            'display' => 'Every 5 minutes'
        ];
        return $schedules;
    }

    public function startBlkSynchronize(): void
    {
        $this->bs->startSynchronize();
    }
}
