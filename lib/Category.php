<?php

namespace Niwee\Niwhiboutik;

use Exception;

class Category
{
    private $hiboutik = null;
    private Menu $menu;
    /**
     * @var array|string
     */
    private $categories;

    public function __construct()
    {
        $this->hiboutik = new Hiboutik();
        $this->menu = new Menu();
    }

    public function getAll()
    {
        return $this->categories;
    }

    public function importAll()
    {
        $this->menu->placeholder();
        $this->clearAll();
        $this->setAll();
    }

    public function clearAll(): bool
    {
        try {
            $terms = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);

            foreach ($terms as $term) {
                wp_delete_term(
                    $term->term_id,
                    'product_cat'
                );
            }
            return true;
        } catch (Exception $e) {
            return false;
        }

    }

    public function setAll()
    {
        Api::write_import_status(
            __(
                'Updating categories',
                'niwhiboutik'
            )
        );
        $this->categories = $this->hiboutik->get_categories();

        $this->organize();
    }

    public function organize()
    {
        $categories_parent = array_column(
            $this->categories,
            'category_id_parent'
        );
        array_multisort(
            $categories_parent,
            SORT_ASC,
            $this->categories
        );

        foreach ($this->categories as $category) {
            if ($category['category_enabled_www'] === 1) {
                $this->setSingle($category);
            }
        }
    }

    public function setSingle($category)
    {
        try {
            if ($category['category_id_parent'] === 0) {
                $parent = 0;
            } else {
                $parent = $this->getCategoryId($category['category_id_parent']);
            }

            if ($parent === false) {
                return;
            } else {
                return $this->insertSingle(
                    $category,
                    $parent
                );
            }
        } catch (Exception $e) {
            error_log('Error while setting category #' . $category['category_id'] . ': ' . $e->getMessage());
        }
    }

    public function getCategoryId($category_id)
    {
        try {
            $terms = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);

            foreach ($terms as $term) {
                if (
                    get_term_meta(
                        $term->term_id,
                        'hiboutik_id',
                        true
                    ) == $category_id
                ) {
                    return $term->term_id;
                }
            }

            return false;
        } catch (Exception $e) {
            error_log('Error while getting category #' . $category_id . ': ' . $e->getMessage());
        }
    }

    public function insertSingle(
        array $category,
              $parent
    )
    {
        try {
            $term = wp_insert_term(
                $category['category_name'],
                'product_cat',
                [
                    'parent' => $parent
                ]
            );

            if (is_wp_error($term)) {
                throw new Exception($term->get_error_message());
            }

            update_term_meta(
                $term['term_id'],
                'hiboutik_id',
                $category['category_id']
            );

            return $term;
        } catch (Exception $e) {
            error_log('Error while inserting category #' . $category['category_id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Get categories recursively
     */
    public function getExisting(array $product)
    {
        try {
            $category_id = $product['product_category'];
            $woo_category_id = $this->getCategoryId($category_id);
            $main_category = get_term_by(
                'id',
                $woo_category_id,
                'product_cat'
            );

            if ($main_category === false) {
                return [];
            }

            if ($main_category->parent === 0) {
                return [$main_category->term_id];
            } else {
                $categories = [$main_category->term_id];

                $parent_category = get_term_by(
                    'term_id',
                    $main_category->parent,
                    'product_cat'
                );

                while ($parent_category->parent != 0) {
                    $categories[] = $parent_category->term_id;
                    $parent_category = get_term_by(
                        'term_id',
                        $parent_category->parent,
                        'product_cat'
                    );
                }

                $categories[] = $parent_category->term_id;

                return $categories;
            }
        } catch (Exception $e) {
            error_log('Error while getting product categories: ' . $e->getMessage());
        }
    }
}