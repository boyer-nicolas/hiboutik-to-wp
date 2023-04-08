<?php

namespace Niwee\Niwhiboutik;

use \Niwee\Niwhiboutik\Niwhiboutik;

class Ui
{
    public function __construct()
    {
        add_action('admin_menu', array(__CLASS__, 'adminMenu'));
    }

    public static function render()
    {
        $container = <<<HTML
            <div id="app"></div>
HTML;
        $container .= "<div data-cookie='" . Niwhiboutik::set_token() . "'></div>";

        echo $container;
        wp_enqueue_script('niwhiboutik_js');
    }

    public static function adminMenu()
    {
        if (get_option('nwh_license_activated') === 'true')
        {
            add_menu_page(
                __('Control Panel', 'niwhiboutik'),
                'NiwHiboutik',
                'administrator',
                'niwhiboutik-dashboard',
                array(__CLASS__, 'render'),
                'dashicons-admin-plugins',
                12
            );

            add_submenu_page(
                'niwhiboutik-dashboard',
                __('Control Panel', 'niwhiboutik'),
                __('Control Panel', 'niwhiboutik'),
                'administrator',
                'niwhiboutik-dashboard',
                array(__CLASS__, 'render')
            );

            add_submenu_page(
                'niwhiboutik-dashboard',
                __('Search', 'niwhiboutik'),
                __('Search', 'niwhiboutik'),
                'administrator',
                'niwhiboutik-search',
                array(__CLASS__, 'render')
            );
            add_submenu_page(
                'niwhiboutik-dashboard',
                __('Settings', 'niwhiboutik'),
                __('Settings', 'niwhiboutik'),
                'administrator',
                'niwhiboutik-settings',
                array(__CLASS__, 'render')
            );
        }
        else
        {
            add_menu_page(
                __('Settings', 'niwhiboutik'),
                'NiwHiboutik',
                'administrator',
                'niwhiboutik-settings',
                array(__CLASS__, 'render'),
                'dashicons-admin-plugins',
                12
            );
        }
    }
}
