<?php

class BlkEndpoints
{
    public BlkSynchronizer $bs;

    public function __construct($bs)
    {
        $this->bs = $bs;
        add_action('init', [$this, 'blkSynchronizerEndpoints']);
    }

    public function blkSynchronizerEndpoints(): void
    {
        $action = !empty($_GET['action']) ? $_GET['action'] : '';
        $method = !empty($_GET['method']) ? $_GET['method'] : '';

        if ($action === 'blkSynchronizer') {
            switch ($method) {
                // ?action=blkSynchronizer&method=synchronize
                case 'synchronize':
                    ini_set('ignore_user_abort', true);
                    $this->bs->startSynchronize();
                    break;

            }
        }
    }
}


