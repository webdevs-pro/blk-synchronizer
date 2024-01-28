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
                // ?action=blkSynchronizer&method=getProducts
                case 'getProducts':
                    $this->bs->getProducts();
                    break;
                // ?action=blkSynchronizer&method=downloadProducts
                case 'downloadProducts':
                    $this->bs->downloadProducts();
                    break;
                // ?action=blkSynchronizer&method=updateProducts
                case 'updateProducts':
                    $maxExecutionTime = ini_get('max_execution_time');
                    ini_set('max_execution_time', '1200');
                    $this->bs->updateProducts();
                    ini_set('max_execution_time', $maxExecutionTime);
                    break;
                // ?action=blkSynchronizer&method=createProducts
                case 'createProducts':
                    $maxExecutionTime = ini_get('max_execution_time');
                    ini_set('max_execution_time', '1200');
                    ini_set('ignore_user_abort', true);
                    $this->bs->createProducts();
                    ini_set('max_execution_time', $maxExecutionTime);
                    break;
                // ?action=blkSynchronizer&method=moveProducts
                case 'moveProducts':
                    $this->bs->moveProducts();
                    break;
                // ?action=blkSynchronizer&method=deleteAllProducts
                case 'deleteAllProducts':
                    $this->bs->deleteAllProducts();
                    break;
            }
        }
    }
}


