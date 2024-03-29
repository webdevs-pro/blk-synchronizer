<?php

class BlkSynchronizer {

    private $synk_log_file_date;
    private $ignored_products = 0;
    private $deleted_ignored = 0;

    /**
     * @return void
     * @throws WC_Data_Exception
     * @throws Exception
     */
    public function startSynchronize() {

        // $max_execution_time = ini_get('max_execution_time');
        // blk_error_log( $max_execution_time );


        set_time_limit( 0 );

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


        $products_to_add = $this->find_added_products( $old_products, $new_products );
        $products_to_delete = $this->find_removed_products( $old_products, $new_products );
        $products_to_update = $this->find_updated_products( $old_products, $new_products );

        // $end_time_1 = microtime( true );
        // $time_1 = number_format( ( $end_time_1 - $start_time ), 5 );
        // error_log( "time 1\n" . print_r( $time_1, true ) . "\n" );

        blk_debug_log( 'Products to add: ' . count( $products_to_add ) );
        blk_debug_log( 'Products to remove: ' . count( $products_to_delete ) );
        blk_debug_log( 'Products to update: ' . count( $products_to_update ) );


        $this->remove_products( $products_to_delete );
        $this->update_products( $products_to_update );
        $this->add_new_products( $products_to_add );

        blk_debug_log( 'Ignored products: ' . $this->ignored_products );
        blk_debug_log( 'Ignored products deleted: ' . $this->deleted_ignored );


        $end_time_2 = microtime( true );
        $time_2 = number_format( ( $end_time_2 - $start_time ), 1 );
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

        // error_log( "categories\n" . print_r( $categories, true ) . "\n" );

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




    /**
     * @param $blkProduct
     * @param $catId
     * @return void
     */
    public function createProduct( $blk_product, $catId ) {
        $wcProduct = new WC_Product_Simple();

        $existing_id = wc_get_product_id_by_sku( $blk_product['sku'] );
        if ( $existing_id ) {
            $logMessage = 'An attempt was made to add a product with an existing SKU ' . $blk_product['sku'];
            blk_synk_log( $logMessage, $this->synk_log_file_date );
            wp_delete_post( $existing_id, true );
        }

        $wcProduct->set_sku( $blk_product['sku'] );
        $wcProduct->set_name( $blk_product['name'] );
        $wcProduct->set_regular_price( $blk_product['price_brutto'] );
        $wcProduct->set_weight( $blk_product['weight'] );
        $wcProduct->set_manage_stock( true );
        $wcProduct->set_stock_quantity( $blk_product['quantity'] );
        // $wcProduct->set_category_ids( [$catId] );
        $wcProduct->set_description( isset( $blk_product['description'] ) ? $blk_product['description'] : '' );

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




    // function find_added_products( $old_products, $new_products ) {
    //     $old_skus = array_column( $old_products, 'sku' );
    //     return array_filter( $new_products, function( $product ) use ( $old_skus ) {
    //         return ! in_array( $product['sku'], $old_skus, true );
    //     } );
    // }


    function find_added_products( $old_products, $new_products ) {
        // Filter out products that should be skipped.
        $filtered_new_products = array_filter( $new_products, function( $product ) {
            // Assuming $this->check_for_skip( $product ) is accessible in this context.
            if ( $this->check_for_skip( $product ) ) {
                // We check whether there is a product on the website that should be ignored, and if so, we remove it.
                $product_id = wc_get_product_id_by_sku( $product['sku'] );
                if ( $product_id ) {
                    wp_delete_post( $product_id, true );
                    blk_synk_log( 'Product ' . $product['sku'] . ' skipped and deleted, it is on the ignore list.', $this->synk_log_file_date );
                    $this->deleted_ignored++;
                } else {
                    blk_synk_log( 'Product ' . $product['sku'] . ' skipped, it is on the ignore list.', $this->synk_log_file_date );
                }
                $this->ignored_products++;
                return false; // Skip this product.
            }
            return true; // Keep this product.
        });
    
        // Get SKUs from old products to compare.
        $old_skus = array_column( $old_products, 'sku' );
    
        // Filter out products that are not in the old products list.
        return array_filter( $filtered_new_products, function( $product ) use ( $old_skus ) {
            return ! in_array( $product['sku'], $old_skus, true );
        });
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
            'description',
        );
        $updated_products = [];
        foreach ( $new_products as $new_product ) {
            foreach ( $old_products as $old_product ) {
                if ( $new_product['sku'] === $old_product['sku'] ) {
                    foreach ( $keys as $key ) {
                        // Fix for backslashes
                        if ( $key == 'description' || $key == 'name' ) {
                            if ( $old_product[ $key ] !== null ) {
                                $old_product[ $key ] = str_replace('\\', '', $old_product[ $key ] );
                            }

                            if ( $new_product[ $key ] !== null ) {
                                $new_product[ $key ] = str_replace('\\', '', $new_product[ $key ] );
                            }
                        }
                        if ( $new_product[ $key ] !== $old_product[ $key ] ) {
                            $updated_products[] = $new_product;
                            // error_log( "sku: " . print_r( $new_product['sku'], true ) );
                            // error_log( "key: " . print_r( $key, true ) );
                            // error_log( "old: " . print_r( $old_product[$key], true ) );
                            // error_log( "new: " . print_r( $new_product[$key], true ) . "\n\n" );
                            blk_synk_log( "sku: " . print_r( $new_product['sku'], true ), $this->synk_log_file_date );
                            blk_synk_log( "key: " . print_r( $key, true ), $this->synk_log_file_date );
                            blk_synk_log( "old: " . print_r( $old_product[$key], true ), $this->synk_log_file_date );
                            blk_synk_log( "new: " . print_r( $new_product[$key], true ) . "\n", $this->synk_log_file_date );

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

        // Remove any spaces and trailing commas before exploding
        $categories_to_skip_string = isset( $options['blk_categories_to_ignore'] ) ? trim( $options['blk_categories_to_ignore'], " ,\t\n\r\0\x0B" ) : '';
        $ids_of_categories_to_skip = explode( ',', $categories_to_skip_string );
        
        $skus_to_skip_string = isset( $options['blk_skus_to_ignore'] ) ? trim( $options['blk_skus_to_ignore'], " ,\t\n\r\0\x0B" ) : '';
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

            // if ( $this->check_for_skip( $blk_product ) ) {
            //     blk_debug_log( 'Product ' . $blk_product['sku'] . ' skipped, it is on the ignore list.' );
            //     continue;
            // }

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
                wp_delete_post( $wc_product_id, true );
                blk_debug_log( 'Product ' . $blk_product_sku . ' deleted. It is on the ignore list.' );
                $this->deleted_ignored++;
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

		$blk_settings = get_option( 'blk_settings' );
		$query_type = isset( $blk_settings['blk_query_type'] ) ? $blk_settings['blk_query_type'] : 'all';

        if ( $query_type == 'all' ) {
            $args = [
                'limit' => -1, // Retrieve all products
                'status' => 'publish', // Get only published products
            ];
        
            $query = new WC_Product_Query($args);
            $products = $query->get_products();
        } elseif ( $query_type == 'chunks' ) {
            $per_page = 100;
            $page = 1; 
            $products = [];
            
            do {
                $args = [
                    'limit'  => $per_page,
                    'status' => 'publish',
                    'page'   => $page,
                ];
            
                $query = new WC_Product_Query($args);
                $current_iteraction_products = $query->get_products();
            
                if ( ! empty( $current_iteraction_products ) ) {
                    $products = array_merge( $products, $current_iteraction_products );
                    $page++;
                } else {
                    break; // Exit loop if no products are found
                }
            } while ( true );
        }


        $formatted_products = [];

        // error_log( "products count\n" . print_r( count( $products ), true ) . "\n" );
    
        foreach ($products as $product) {
            $formatted_product = [
                // 'product_id' => intval( $product->get_meta('blk_product_id') ),
                // 'ean' => $product->get_sku(),
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'quantity' => $product->get_stock_quantity(),
                // 'price_netto' => wc_get_price_excluding_tax($product),
                'price_brutto' => wc_get_price_including_tax($product),
                // 'price_wholesale_netto' => 0, // Modify as needed
                // 'tax_rate' => $product->get_tax_class(),
                'weight' => floatval( $product->get_weight() ),
                // 'man_name' => '', // Modify as needed
                // 'man_image' => null, // Modify as needed
                // 'category_id' => current($product->get_category_ids()),
                'images' => get_post_meta( $product->get_id(), 'blk_images', true ),
                // 'features' => [], // Modify as needed
                // 'variants' => [], // Modify as needed
                'description' => html_entity_decode( $product->get_description(), ENT_HTML5, 'UTF-8' ),
                // 'description_extra1' => null, // Modify as needed
                // 'description_extra2' => null, // Modify as needed
                // 'description_extra3' => null, // Modify as needed
                // 'description_extra4' => null, // Modify as needed
            ];



            $description = $product->get_description(); // Get the description from your product
            if ( ! $description ) {
                // Fix "" for description. If empty it should be null
                $formatted_product['description'] = null;
            } else {
                $formatted_product['description'] = preg_replace_callback( '/&#x[a-fA-F0-9]+;/u', function( $matches ) {
                    return html_entity_decode($matches[0], ENT_HTML5, 'UTF-8');
                }, $description );
            }
    
            $formatted_products[] = $formatted_product;
        }
    
        // // Sort the products array by product_id
        // usort( $formatted_products, function( $a, $b ) {
        //     return $a['product_id'] - $b['product_id'];
        // } );
    
        $json_data = json_encode( $formatted_products, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
        file_put_contents( BLK_SYNCHRONIZER_PATH . 'old-products.json', $json_data );
    }



}
