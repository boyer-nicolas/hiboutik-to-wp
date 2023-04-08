<?php

namespace Niwee\Niwhiboutik;

use Exception;

class Brand
{
    private $brands;
    private $hiboutik;

    public function __construct()
    {
        $this->hiboutik = new Hiboutik();
        try
        {
            if (function_exists('wc_get_page_id'))
            {
                $shop_page_id = wc_get_page_id('shop');
            }
            else
            {
                return false;
            }

            $base_slug = $shop_page_id > 0 && get_page($shop_page_id) ? get_page_uri($shop_page_id) : 'shop';
            $category_base = get_option('woocommerce_prepend_shop_page_to_urls') == "yes" ? trailingslashit(
                $base_slug
            ) : '';

            register_taxonomy(
                'product_brand',
                array('product'),
                array(
                    'hierarchical' => true,
                    'update_count_callback' => '_update_post_term_count',
                    "label" => __(
                        'Brands',
                        'niwhiboutik'
                    ),
                    'labels' => array(
                        'name' => __(
                            'Brands',
                            'niwhiboutik'
                        ),
                        'back_to_items' => __(
                            'Back to Brands',
                            'niwhiboutik'
                        ),
                        'not_found' => __(
                            'No brands found.',
                            'niwhiboutik'
                        ),
                        'singular_name' => __(
                            'Brand',
                            'niwhiboutik'
                        ),
                        'search_items' => __(
                            'Search Brands',
                            'niwhiboutik'
                        ),
                        'all_items' => __(
                            'All Brands',
                            'niwhiboutik'
                        ),
                        'parent_item' => __(
                            'Parent Brand',
                            'niwhiboutik'
                        ),
                        'parent_item_colon' => __(
                            'Parent Brand:',
                            'niwhiboutik'
                        ),
                        'edit_item' => __(
                            'Edit Brand',
                            'niwhiboutik'
                        ),
                        'update_item' => __(
                            'Update Brand',
                            'niwhiboutik'
                        ),
                        'add_new_item' => __(
                            'Add New Brand',
                            'niwhiboutik'
                        ),
                        'new_item_name' => __(
                            'New Brand Name',
                            'niwhiboutik'
                        )
                    ),
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'show_admin_column' => true,
                    'show_in_nav_menus' => true,
                    'show_in_quick_edit' => true,
                    'meta_box_cb' => 'post_categories_meta_box',
                    'capabilities' => array(
                        'manage_terms' => 'manage_product_terms',
                        'edit_terms' => 'edit_product_terms',
                        'delete_terms' => 'delete_product_terms',
                        'assign_terms' => 'assign_product_terms'
                    ),

                    'rewrite' => array(
                        'slug' => $category_base . (empty($permalink_option) ? __(
                                'brands',
                                'niwhiboutik'
                            ) : $permalink_option),
                        'with_front' => true,
                        'hierarchical' => true
                    )
                )
            );

            return true;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    public function importAll()
    {
        Api::write_import_status(
            __(
                'Updating brands',
                'niwhiboutik'
            )
        );

        $this->clearAll();
        $this->setAll();
        $this->dispatchAll();
    }

    public function clearAll(): bool
    {
        try
        {
            $terms = get_terms([
                                   'taxonomy' => 'product_brand',
                                   'hide_empty' => false,
                               ]);

            foreach ($terms as $term)
            {
                $deleted_term = wp_delete_term(
                    $term->term_id,
                    'product_brand'
                );

                if (is_wp_error($deleted_term))
                {
                    throw new Exception($deleted_term->get_error_message());
                }
            }
            return true;
        }
        catch (Exception $e)
        {
            error_log('Error while removing all brands: ' . $e->getMessage());

            return false;
        }

    }

    public function setAll()
    {
        $this->brands = $this->hiboutik->get_brands();
    }

    public function dispatchAll(): bool
    {
        sort($this->brands);

        foreach ($this->brands as $brand)
        {
            if ($brand['brand_enabled_www'] === 1)
            {
                $this->insertSingle($brand);
            }
        }

        return true;
    }

    public function insertSingle(array $brand): bool
    {
        try
        {
            $term = wp_insert_term(
                $brand['brand_name'],
                'product_brand',
                [
                    'parent' => 0
                ]
            );

            if (is_wp_error($term))
            {
                throw new Exception($term->get_error_message());
            }

            update_term_meta(
                $term['term_id'],
                'hiboutik_id',
                $brand['brand_id']
            );
            return false;
        }
        catch (Exception $e)
        {
            error_log('Error while inserting brand #' . $brand['brand_id'] . ': ' . $e->getMessage());
            return false;
        }

    }

    /**
     * @param $brand_id
     * @return false|int|void
     */
    public function getId($brand_id)
    {
        try
        {
            $terms = get_terms([
                                   'taxonomy' => 'product_brand',
                                   'hide_empty' => false,
                               ]);

            foreach ($terms as $term)
            {
                if (
                    get_term_meta(
                        $term->term_id,
                        'hiboutik_id',
                        true
                    ) == $brand_id
                )
                {
                    return $term->term_id;
                }
            }

            return false;
        }
        catch (Exception $e)
        {
            error_log('Error while getting brand #' . $brand_id . ': ' . $e->getMessage());
        }
    }

    public function getAllExisting()
    {
        return get_terms([
                             'taxonomy' => 'product_brand',
                             'hide_empty' => false,
                         ]);
    }

    public function getExisting(array $product)
    {
        try
        {
            $brand_id = $product['product_brand'];
            $woo_brand_id = $this->getId($brand_id);
            $brand = get_term_by(
                'id',
                $woo_brand_id,
                'product_brand'
            );

            if ($brand === false)
            {
                return [];
            }

            return [$brand->term_id];
        }
        catch (Exception $e)
        {
            error_log('Error while getting product brands: ' . $e->getMessage());
            return [];
        }
    }
}