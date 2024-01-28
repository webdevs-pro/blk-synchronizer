=== BaseLinker Synchronizer ===

Синхронизацию можно запускать по http-запросу
?action=blkSynchronizer&method=synchronize

либо через shell
cd wp-content/plugins/blk-synchronizer/cli
php -f start-synchronize.php


удаление всех продутов только через shell
cd wp-content/plugins/blk-synchronizer/cli
php -f delete-all-products.php
