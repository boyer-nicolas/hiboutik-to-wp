<?php

namespace Niwee\Niwhiboutik;

use Niwee\Niwhiboutik\Ui;
use Niwee\Niwhiboutik\Api;
use Niwee\Niwhiboutik\Brand;

class NiwHiboutik
{
    public function __construct()
    {
        new Ui();
        new Api();
        add_action(
            'init',
            array($this, 'init_brands'),
            20
        );
    }

    public static function activate()
    {
        add_option('nwh_hiboutik_url', '');
        add_option('nwh_hiboutik_login', '');
        add_option('nwh_hiboutik_key', '');
        add_option('nwh_api_token', '');
        add_option('nwh_license_key', '');
        add_option('nwh_license_activated', 'false');

        // If W3 is activated, the API routes can return 404, so flush the cache to avoid issues
        if (class_exists('W3_Plugin_TotalCacheAdmin'))
        {
            $plugin_totalcacheadmin = &w3_instance('W3_Plugin_TotalCacheAdmin');

            $plugin_totalcacheadmin->flush_all();
        }
    }

    public static function set_token()
    {
        if (!get_option('nwh_api_token'))
        {
            $token = Api::generate_token();
            add_option('nwh_api_token', $token);
        }

        if (get_option('nwh_api_token') === '' || get_option('nwh_api_token') === NULL)
        {
            $token = Api::generate_token();
            update_option('nwh_api_token', $token);
        }

        setcookie('nwh_api_token', get_option('nwh_api_token'), time() + 7 * 24 * 60 * 60, '/', false, false);

        return get_option('nwh_api_token');
    }

    public static function deactivate()
    {
        delete_option('nwh_hiboutik_url');
        delete_option('nwh_hiboutik_login');
        delete_option('nwh_hiboutik_key');
        delete_option('nwh_api_token');
        delete_option('nwh_license_key');
        delete_option('nwh_license_activated');
        wp_clear_scheduled_hook('nwh_import_cron');
    }

    public function init_brands()
    {
        new Brand();
    }
}
