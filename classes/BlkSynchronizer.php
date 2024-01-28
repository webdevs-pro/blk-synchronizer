<?php

class BlkSynchronizer {

    private $synk_log_file_date;

    /**
     * @return void
     * @throws WC_Data_Exception
     * @throws Exception
     */
    public function startSynchronize() {

        $this->synk_log_file_date = date_i18n('Y-m-d-H-i-s');

        blk_debug_log( 'Start.' );
        blk_remove_import_stop();

        if ( blk_is_import_locked() ) {
            blk_debug_log( 'Already in process.' );
            return;
        }

        blk_create_import_lock();

        $start_time = microtime( true );

        $this->products_to_json();

        $this->downloadProducts();


        $old_json = file_get_contents( BLK_SYNCHRONIZER_PATH . 'old-products.json' );
        $old_products = json_decode( $old_json, true );

        $new_json = file_get_contents( BLK_SYNCHRONIZER_PATH . 'new-products.json' );
        $new_products = json_decode( $new_json, true );

        // $products_to_add = $this->products_added( $old_products, $new_products );
        // $products_to_delete = $this->products_removed( $old_products, $new_products );
        // $products_to_update = $this->products_changed( $old_products, $new_products );

        $products_to_add = $this->find_added_products( $old_products, $new_products );
        $products_to_delete = $this->find_removed_products( $old_products, $new_products );
        $products_to_update = $this->find_updated_products( $old_products, $new_products );

        // $end_time_1 = microtime( true );
        // $time_1 = number_format( ( $end_time_1 - $start_time ), 5 );
        // error_log( "time 1\n" . print_r( $time_1, true ) . "\n" );

        blk_debug_log( 'Products to add: ' . count( $products_to_add ) );
        blk_debug_log( 'Products to remove: ' . count( $products_to_delete ) );
        blk_debug_log( 'Products to update: ' . count( $products_to_update ) );

        // error_log( "products_to_add\n" . print_r( $products_to_add, true ) . "\n" );
        // error_log( "products_to_delete\n" . print_r( $products_to_delete, true ) . "\n" );
        // error_log( "products_to_update\n" . print_r( $products_to_update, true ) . "\n" );

        $this->remove_products( $products_to_delete );
        $this->update_products( $products_to_update );
        $this->add_new_products( $products_to_add );

        $end_time_2 = microtime( true );
        $time_2 = number_format( ( $end_time_2 - $start_time ), 5 );
        // error_log( "time 2\n" . print_r( $time_2, true ) . "\n" );
        

        blk_remove_import_lock();

        blk_debug_log( "Done in " . $time_2 . "s.\n" );

    }









        /**
     * @return void
     * @throws Exception
     */
    public function downloadProducts() {
        $blk = new BaseLinkerHelper();
        // $categories = $blk->getCategories();
        // die();
        $products = $blk->getProductsList();

        error_log( "categories\n" . print_r( $categories, true ) . "\n" );

        $productIds = array_column( $products, 'product_id' );

        $new_roducts = $blk->getProducts( $productIds );
        $new_roducts = json_encode( $new_roducts );

        if ( ! empty( $new_roducts ) && $new_roducts !== '[]') {
            // Write to new-products.json
            file_put_contents( BLK_SYNCHRONIZER_PATH . 'new-products.json', $new_roducts );
        } else {
            $error_message = 
            blk_error_log( 'Error getting products via API' );
            blk_debug_log( 'Error getting products via API' );
            die( 'BlkSynchronizer - Error getting products via API' );
        }
    }


    // function products_added( $old_array, $new_array ) {
    //     $old_ids = array_column( $old_array, 'product_id' );
    //     $added = array_filter( $new_array, function ( $product ) use ( $old_ids ) {
    //         return ! in_array( $product['product_id'], $old_ids );
    //     } );

    //     foreach ( $added as $index => $blk_product ) {
    //         if ( $this->check_for_skip( $blk_product ) ) {
    //             unset( $added[ $index ] );
    //         }
    //     }
    
    //     return array_values( $added ); // Re-indexing array
    // }

    function products_added( $old_array, $new_array ) {
        $old_ids = array_column( $old_array, 'product_id' );
    
        $added_products = array_filter( $new_array, function ( $blk_product ) use ( $old_ids ) {
            return !in_array( $blk_product['product_id'], $old_ids ) && !$this->check_for_skip( $blk_product );
        } );
    
        return array_values( $added_products ); // Re-indexing array
    }
    
    function products_removed( $old_array, $new_array ) {
        $new_ids = array_column( $new_array, 'product_id' );
        $removed_products = array_filter( $old_array, function ( $blk_product ) use ( $new_ids ) {
            return !in_array( $blk_product['product_id'], $new_ids );
        });
    
        return array_values( $removed_products ); // Re-indexing array
    }










    /**
     * @param $blkProduct
     * @param $catId
     * @return void
     */
    public function createProduct($blk_product, $catId) {
        $wcProduct = new WC_Product_Simple();

        $wcProduct->set_name( $blk_product['name'] );
        $wcProduct->set_regular_price( $blk_product['price_brutto'] );
        $wcProduct->set_weight( $blk_product['weight'] );
        $wcProduct->set_manage_stock( true );
        $wcProduct->set_stock_quantity( $blk_product['quantity'] );
        // $wcProduct->set_category_ids( [$catId] );
        $wcProduct->set_description( $blk_product['description'] );

        try {
            if ( ! empty($blk_product['tax_rate'] ) ) {
                $blk_productTaxRate = $blk_product['tax_rate'];

                if ( $blk_productTaxRate == 23 ) {
                    $wcProduct->set_tax_status( 'taxable' );
                    $wcProduct->set_tax_class( '23' );
                } elseif ( $blk_productTaxRate == 8 ) {
                    $wcProduct->set_tax_status( 'taxable' );
                    $wcProduct->set_tax_class( '8' );
                } elseif ( $blk_productTaxRate == 5 ) {
                    $wcProduct->set_tax_status( 'taxable' );
                    $wcProduct->set_tax_class( '5' );
                }
            }

        } catch ( WC_Data_Exception $e ) {
            ob_start();
                echo '<p>ERROR: ' . $e->getMessage() . '</p>' . PHP_EOL;
                echo $blk_product['sku'] . ' - ' . $blk_product['name'] . PHP_EOL;
            $error_message = ob_get_clean();
            blk_error_log( $error_message );
        }

        $wcProduct->set_sku( $blk_product['sku'] );
        $wcProduct->save();

        $wc_product_id = $wcProduct->get_id();
        update_post_meta(  $wc_product_id, 'blk_images', $blk_product['images'] );
        update_post_meta(  $wc_product_id, 'blk_product_id', $blk_product['product_id'] );
        update_post_meta(  $wc_product_id, 'jet_product_title', $blk_product['name'] );

        $logMessage = 'Created: ' . $wcProduct->get_sku() . ' - ' . $wcProduct->get_name();
        blk_synk_log( $logMessage, $this->synk_log_file_date );


        // Set featured image and gallery from URLs
        if ( isset( $blk_product['images'] ) && ! empty( $blk_product['images'] ) ) {
            fifu_dev_set_image_list( $wc_product_id, $this->array_to_string_with_delimiter( $blk_product['images'] ) );
        }
    }

    public function array_to_string_with_delimiter( $blkProductImagesUrlArray ) {
        if ( is_array( $blkProductImagesUrlArray ) ) {
            return implode( '|', $blkProductImagesUrlArray );
        } 

        return $blkProductImagesUrlArray;
    }


    

    // function arrays_are_different_by_keys( $array1, $array2, $keys ) {
    //     foreach ( $keys as $key ) {
    //         if ( ( isset( $array1[ $key ] ) || isset( $array2[ $key ] ) ) && $array1[ $key ] !== $array2[ $key ] ) {
    //             error_log( "sku\n" . print_r( $array2['sku'], true ) . "\n" );
    //             error_log( "key\n" . print_r( $key, true ) . "\n" );
    //             error_log( "old\n" . print_r( $array1[$key], true ) . "\n" );
    //             error_log( "new\n" . print_r( $array2[$key], true ) . "\n\n\n" );
    //             return true;
    //         }
    //     }
    //     return false;
    // }
    
    // function products_changed( $old_array, $new_array ) {
    //     $keys = array(
    //         // 'ean',
    //         'sku',
    //         'name',
    //         'quantity',
    //         // 'price_netto',
    //         'price_brutto',
    //         // 'price_wholesale_netto',
    //         // 'tax_rate',
    //         'weight',
    //         'images',
    //         // 'description',
    //     );

    //     $changed_products = [];
    //     foreach ( $new_array as $new_product ) {
    //         foreach ( $old_array as $old_product ) {
    //             if ( $new_product['product_id'] == $old_product['product_id'] && $this->arrays_are_different_by_keys( $new_product, $old_product, $keys ) ) {
    //                 $changed_products[] = $new_product;
    //                 break;
    //             }
    //         }
    //     }
    
    //     return $changed_products;
    // }





























    function find_added_products( $old_products, $new_products ) {
        $old_skus = array_column( $old_products, 'sku' );
        return array_filter( $new_products, function( $product ) use ( $old_skus ) {
            return ! in_array( $product['sku'], $old_skus, true );
        } );
    }
    
    function find_removed_products( $old_products, $new_products ) {
        $new_skus = array_column( $new_products, 'sku' );
        return array_filter( $old_products, function( $product ) use ( $new_skus ) {
            return ! in_array( $product['sku'], $new_skus, true );
        } );
    }
    
    function find_updated_products( $old_products, $new_products ) {
        $keys = array(
            // 'ean',
            'sku',
            'name',
            'quantity',
            // 'price_netto',
            'price_brutto',
            // 'price_wholesale_netto',
            // 'tax_rate',
            'weight',
            'images',
            // 'description',
        );
        $updated_products = [];
        foreach ( $new_products as $new_product ) {
            foreach ( $old_products as $old_product ) {
                if ( $new_product['sku'] === $old_product['sku'] ) {
                    foreach ( $keys as $key ) {
                        if ( $new_product[$key] !== $old_product[$key] ) {
                            $updated_products[] = $new_product;
                            error_log( "sku: " . print_r( $new_product['sku'], true ) );
                            error_log( "key: " . print_r( $key, true ) );
                            error_log( "old: " . print_r( $old_product[$key], true ) );
                            error_log( "new: " . print_r( $new_product[$key], true ) . "\n\n" );
                            break;
                        }
                    }
                }
            }
        }
        return $updated_products;
    }

































    private function check_for_skip( $blk_product ) {
        $options = get_option( 'blk_settings' );
        $categories_to_skip_string = $options['blk_categories_to_ignore'] ?? '';
        $ids_of_categories_to_skip = explode( ',', $categories_to_skip_string );

        $skus_to_skip_string = $options['blk_skus_to_ignore'] ?? '';
        $skus_to_skip = explode( ',', $skus_to_skip_string );

        if ( in_array( $blk_product['category_id'], $ids_of_categories_to_skip ) ) {
            // blk_synk_log( ' Product "' . $blk_product['name'] . '" ignored by BaseLinker category ID (' . $blk_product['category_id'] . ')', $this->synk_log_file_date );
            return true;
        }

        if ( in_array( $blk_product['sku'], $skus_to_skip ) ) {
            // blk_synk_log( ' Product "' . $blk_product['name'] . '" ignored by SKU (' . $blk_product['sku'] . ')', $this->synk_log_file_date );
            return true;
        }

        return false;
    }





    private function add_new_products( $added_products ) {
        foreach ( $added_products as $blk_product ) {
            if ( blk_is_stop_import() ) {
                blk_debug_log( 'Import canceled by user.' );
                blk_remove_import_stop();
                blk_remove_import_lock();
                die();
            }


            $this->createProduct( $blk_product, 0 );
        }
    }



    private function remove_products( $removed_products ) {
        foreach ( $removed_products as $blk_product) {
            if ( blk_is_stop_import() ) {
                blk_debug_log( 'Import canceled by user.' );
                blk_remove_import_stop();
                blk_remove_import_lock();
                die();
            }

            $product_id = wc_get_product_id_by_sku( $blk_product['sku'] );
            $wcProduct = wc_get_product( $product_id );
            $logMessage = 'Deleted: ' . $wcProduct->get_sku() . ' - ' . $wcProduct->get_name();
            blk_synk_log( $logMessage, $this->synk_log_file_date );

            wp_delete_post( $product_id, true );
        }
    }



    private function update_products( $changed_products ) {
        foreach ( $changed_products as $blk_product ) {

            if ( blk_is_stop_import() ) {
                blk_debug_log( 'Import canceled by user.' );
                blk_remove_import_stop();
                blk_remove_import_lock();
                die();
            }
            
            $blk_product_sku = $blk_product['sku'];
            $wcProductId = wc_get_product_id_by_sku( $blk_product_sku );
            $wcProduct = wc_get_product( $wcProductId );
            if ( ! $wcProduct ) {
                blk_error_log( ' Error getting WC Product: ' . $wcProduct );
                continue;
            }
            
            $wc_product_id = $wcProduct->get_id();
            
            if ( $this->check_for_skip( $blk_product ) ) {
                file_put_contents( BLK_SYNCHRONIZER_PATH . 'logs/debug.log', date_i18n('Y-m-d H:i:s') . " Product $blk_product_sku deleted. It was in ignore list..\n", FILE_APPEND );

                wp_delete_post( $wc_product_id, true );
                blk_debug_log( 'Product ' . $blk_product_sku . ' deleted. It was in ignore list.' );
                continue;
            }

            $wcProduct->set_regular_price( round( $blk_product['price_brutto'], 2 ) );
            $wcProduct->set_weight( $blk_product['weight'] );
            $wcProduct->set_name( $blk_product['name'] );
            $wcProduct->set_manage_stock( true );
            $wcProduct->set_stock_quantity( $blk_product['quantity'] );
            $wcProduct->set_description( $blk_product['description'] );

            if ( ! empty( $blk_product['tax_rate'] ) ) {
                if ( $blk_product['tax_rate'] == 23 ) {
                    $wcProduct->set_tax_status( 'taxable' );
                    $wcProduct->set_tax_class( '23' );
                } elseif ( $blk_product['tax_rate'] == 8 ) {
                    $wcProduct->set_tax_status( 'taxable' );
                    $wcProduct->set_tax_class( '8' );
                } elseif ( $blk_product['tax_rate'] == 5 ) {
                    $wcProduct->set_tax_status( 'taxable' );
                    $wcProduct->set_tax_class( '5' );
                }
            }

            $wcProduct->save();

            update_post_meta(  $wc_product_id, 'blk_images', $blk_product['images'] );
            update_post_meta(  $wc_product_id, 'blk_product_id', $blk_product['product_id'] );
            update_post_meta(  $wc_product_id, 'jet_product_title', $blk_product['name'] );


            fifu_dev_set_image_list( $wc_product_id, $this->array_to_string_with_delimiter( $blk_product['images'] ) );
                
            $logMessage = 'Update: ' . $blk_product_sku . ' - ' . $blk_product['name'] . ' - Price: ' . round( $blk_product['price_brutto'], 2 ) . ' Q:' . $blk_product['quantity'] . ' W:' . $blk_product['weight'];

            blk_synk_log( $logMessage, $this->synk_log_file_date );
        }
    }


    private function products_to_json() {
        $args = [
            'limit' => -1, // Retrieve all products
            'status' => 'publish', // Get only published products
        ];
    
        $query = new WC_Product_Query($args);
        $products = $query->get_products();
        $formatted_products = [];
    
        foreach ($products as $product) {
            $formatted_product = [
                'product_id' => intval( $product->get_meta('blk_product_id') ),
                'ean' => $product->get_sku(),
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'quantity' => $product->get_stock_quantity(),
                'price_netto' => wc_get_price_excluding_tax($product),
                'price_brutto' => wc_get_price_including_tax($product),
                'price_wholesale_netto' => 0, // Modify as needed
                'tax_rate' => $product->get_tax_class(),
                'weight' => floatval( $product->get_weight() ),
                'man_name' => '', // Modify as needed
                'man_image' => null, // Modify as needed
                'category_id' => current($product->get_category_ids()),
                'images' => get_post_meta( $product->get_id(), 'blk_images', true ),
                'features' => [], // Modify as needed
                'variants' => [], // Modify as needed
                'description' => $this->convert_to_html_entities($product->get_description()),
                'description_extra1' => null, // Modify as needed
                'description_extra2' => null, // Modify as needed
                'description_extra3' => null, // Modify as needed
                'description_extra4' => null, // Modify as needed
            ];
    
            $formatted_products[] = $formatted_product;
        }
    
        // Sort the products array by product_id
        usort($formatted_products, function($a, $b) {
            return $a['product_id'] - $b['product_id'];
        });
    
        $json_data = json_encode($formatted_products);
        file_put_contents( BLK_SYNCHRONIZER_PATH . 'old-products.json', $json_data );
    }

    function convert_to_html_entities( $text ) {
        return htmlentities( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

    }

}
