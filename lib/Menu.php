<?php

namespace Niwee\Niwhiboutik;

class Menu
{
    public function generate($request = null)
    {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);

        if (is_wp_error($categories))
        {
            $categories = array();
        }

        $menu_id = $this->get_menu();

        sort($categories);
        foreach ($categories as $category)
        {
            $term_id = $category->term_id;

            $args = array(
                'menu-item-object-id' => $term_id,
                'menu-item-object' => 'product_cat',
                'menu-item-type' => 'taxonomy',
                'menu-item-status' => 'publish',
            );

            // also check if current category is a subcategory, and if so,
            // if it's parent category has a menu item - then assign current item
            // as a subitem to that:
            $parent_term_id = get_term_field('parent', $term_id, 'product_cat', 'raw');

            if ($parent_term_id)
            {
                $parent_term_menu_item = get_posts(array(
                    'numberposts' => 1,
                    'post_type' => 'nav_menu_item',
                    'meta_key' => '_menu_item_object_id',
                    'meta_value' => $parent_term_id,
                    'fields' => 'ids'
                ));

                if (!empty($parent_term_menu_item))
                {
                    $args['menu-item-parent-id'] = $parent_term_menu_item[0];
                }
            }

            wp_update_nav_menu_item($menu_id, 0, $args);
        }
    }

    public
    function get_menu()
    {
        $menu_name = 'niwhiboutik-categories';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if (!$menu_exists)
        {
            $menu_id = wp_create_nav_menu($menu_name);
        }
        else
        {
            $menu_id = $menu_exists->term_id;
            $menu_objects = get_objects_in_term($menu_exists->term_id, 'nav_menu');

            if (!empty($menu_objects))
            {
                foreach ($menu_objects as $item)
                {
                    wp_delete_post($item);
                }
            }
        }

        return $menu_id;
    }

    public
    function placeholder()
    {
        $shop_id = get_option('woocommerce_shop_page_id');
        $shop_title = get_the_title($shop_id);
        $shop_url = get_permalink($shop_id);

        $menu_id = $this->get_menu();
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => $shop_title,
            'menu-item-url' => $shop_url,
            'menu-item-status' => 'publish',
        ));
    }
}
