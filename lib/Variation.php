<?php

namespace Niwee\Niwhiboutik;

class Variation
{
    /**
     * Create a product variation for a defined variable product ID.
     *
     * @param int $product | Post ID of the product parent variable product.
     * @param array $variation_data | The data to insert in the product.
     * @since 3.0.0
     */

    public function create(object $product, array $variation_data)
    {
        try
        {
            $product_id = $product->get_id();
            $variation_post = array(
                'post_title' => $product->get_name(),
                'post_name' => 'product-' . $product_id . '-variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type' => 'product_variation',
                'guid' => $product->get_permalink()
            );

            // Creating the product variation
            $variation_id = wp_insert_post($variation_post);

            // Get an instance of the WC_Product_Variation object
            $variation = new WC_Product_Variation($variation_id);

            // Iterating through the variations attributes
            foreach ($variation_data as $attribute)
            {
                $taxonomy = 'pa_' . $attribute['size_name']; // The attribute taxonomy

                // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
                if (!taxonomy_exists($taxonomy))
                {
                    register_taxonomy(
                        $taxonomy,
                        'product_variation',
                        array(
                            'hierarchical' => false,
                            'label' => ucfirst($attribute['size_name']),
                            'query_var' => true,
                            'rewrite' => array('slug' => sanitize_title($attribute['size_name'])), // The base slug
                        ),
                    );
                }

                // Check if the Term name exist and if not we create it.
                if (!term_exists($attribute['size_name'], $taxonomy))
                {
                    wp_insert_term($attribute['size_name'], $taxonomy);
                } // Create the term

                $term_slug = get_term_by('name', $attribute['size_name'], $taxonomy)->slug; // Get the term slug

                // Get the post Terms names from the parent variable product.
                $post_term_names = wp_get_post_terms($product_id, $taxonomy, array('fields' => 'names'));

                // Check if the post term exist and if not we set it in the parent variable product.
                if (!in_array($attribute['size_name'], $post_term_names))
                {
                    wp_set_post_terms($product_id, $attribute['size_name'], $taxonomy, true);
                }

                // Set/save the attribute data in the product variation
                update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
            }

            ## Set/save all other data

            // SKU
            if (!empty($variation_data['barcode']))
            {
                $variation->set_sku($variation_data['barcode']);
            }

            // Stock
            $variation_product_id = $variation->get_id();
            $variation_stock = $this->hiboutik->get_stock_by_id($variation_product_id);
            foreach ($variation_stock as $stock)
            {
                if ($stock['product_size'] === $variation_data['size_id'])
                {
                    $stock_quantity = $stock['stock_available'];
                    if ($stock_quantity === 0)
                    {
                        $variation->set_stock_status('outofstock');
                    }
                    else
                    {
                        $variation->set_stock_status('instock');
                    }

                    $variation->set_stock_quantity($stock_quantity);
                    $variation->set_manage_stock(true);
                }
            }

            $variation->set_weight(''); // weight (reseting)

            $variation->save(); // Save the data
        }
        catch (Exception $e)
        {
            error_log("Unable to create the product variation for product " . $product_id . ". Error: " . $e->getMessage());
        }
    }
}