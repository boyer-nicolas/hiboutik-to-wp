<?php

/**
 * Plugin Name: Hiboutik byNiWee
 * Plugin URI: https://plugins.byniwee.io/shop/wordpress/licence-niwhiboutik/
 * Description: Retrieve all of your Hiboutik products easily.
 * Version: 1.1.9
 * Author: NiWee Productions
 * Author URI: https://agence.niwee.fr/
 * Requires at least: 6.x
 **/

use Niwee\Niwhiboutik\Api;
use Niwee\Niwhiboutik\NiwHiboutik;


defined('ABSPATH') || die();
// Get autoload
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// If is admin and on plugin page
if (is_admin() && isset($_GET['page']) && str_contains($_GET['page'], 'niwhiboutik')) {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
    $whoops->register();
}

if (!class_exists('NiwHiboutik')) {
    new NiwHiboutik();
}

// Register React
add_action('admin_enqueue_scripts', 'niwhiboutik_assets');
function niwhiboutik_assets()
{
    wp_register_script('niwhiboutik_js', plugins_url('/build/index.js', __FILE__));
    wp_localize_script('niwhiboutik_js', 'niwhiboutik_po', niwhiboutik_get_translations());
}

function niwhiboutik_get_translations()
{
    return array(
        'control_panel' => __('Control Panel', 'niwhiboutik'),
        'import_status' => __('Import status', 'niwhiboutik'),
        'import_catalog' => [
            'title' => __('Import catalog', 'niwhiboutik'),
            'description' => __("When you click the below button, the plugins connects to both Hiboutik and Woocommerce so they can communicate, determine the changes needed and import all of the products except the archived ones.", 'niwhiboutik'),
        ],
        'status_messages' => [
            'check_in_progress' => __('Check in progress', 'niwhiboutik'),
            'importing' => __('Importing', 'niwhiboutik'),
            'loading' => __('Loading', 'niwhiboutik'),
            'stopping' => __('Stopping', 'niwhiboutik'),
            'error' => __('Error', 'niwhiboutik'),
            'retry' => __('Retry', 'niwhiboutik'),
            'import_stopped' => __('Import stopped', 'niwhiboutik'),
            'import_finished' => __('Import finished', 'niwhiboutik'),
            'import_error' => __('Import error', 'niwhiboutik'),
            'cannot_retrieve_products' => __('Cannot retrieve products', 'niwhiboutik'),
            'cannot_stop_import' => __('Cannot stop import', 'niwhiboutik'),
            'stopping_import' => __('Stopping import', 'niwhiboutik'),
            'import_done' => __('Import done', 'niwhiboutik'),
            'import_in_progress' => __('An import is already in progress. Please wait for it to finish before searching products', 'niwhiboutik'),
            'save' => __('Save', 'niwhiboutik'),
            'settings_import_in_progress' => __('An import is in progress. Please wait for it to finish before changing settings', 'niwhiboutik'),
            'success' => __('Success', 'niwhiboutik'),
            'cannot_save_while_importing' => __('Cannot save while importing', 'niwhiboutik'),
            'update' => __('Update', 'niwhiboutik'),
            'activate' => __('Activate', 'niwhiboutik'),
            'activating' => __('Activating', 'niwhiboutik'),
            'activation_error' => __('Activation error', 'niwhiboutik'),
            'activated' => __('The license was activated successfully', 'niwhiboutik'),
            'deactivate' => __('Deactivate', 'niwhiboutik'),
            'error_saving_license' => __('Error saving license', 'niwhiboutik'),
            'unknown_error' => __('Unknown error, please contact your server administrator or support.', 'niwhiboutik'),
            'updating_license' => __('Updating license', 'niwhiboutik'),
            'checking_license_validity' => __('Checking license validity', 'niwhiboutik'),
            'please_activate' => __('Your license is valid, but you need to activate it for the plugin to work.', 'niwhiboutik'),
            'activations_left' => __('activations left', 'niwhiboutik'),
            'activation_left' => __('activation left', 'niwhiboutik'),
            'regenerating' => __('Regenerating', 'niwhiboutik'),
            'regenerated' => __('The menu was successfully regenerated.', 'niwhiboutik'),
        ],
        'actions' => [
            'stop_import' => __('Stop import', 'niwhiboutik'),
        ],
        'progress' => __('Progress', 'niwhiboutik'),
        'alright' => __('Alright', 'niwhiboutik'),
        'import_starting_background' => __('The import is starting in the background', 'niwhiboutik'),
        'import_canceled' => __('Import canceled', 'niwhiboutik'),
        'remaining_time' => __('Remaining time', 'niwhiboutik'),
        'menu' => [
            'control_panel' => __('Control Panel', 'niwhiboutik'),
            'search' => __('Search', 'niwhiboutik'),
            'settings' => __('Settings', 'niwhiboutik'),
        ],
        'notifications' => [
            'title' => __('Notifications', 'niwhiboutik'),
            'unread' => __('Unread', 'niwhiboutik'),
            'no_import_registered' => __('No import registered', 'niwhiboutik'),
            'loading' => __('Loading notification', 'niwhiboutik'),
        ],
        'update_catalog' => [
            'title' => __('Update catalog', 'niwhiboutik'),
            'description' => __("You are about to import all of the products. This may take some time. If you have a large number of references on Hiboutik and you have updated some of them, it may be more interesting to search the references via the form and import them manually. The import will run in the background and download the remote images.", 'niwhiboutik'),
        ],
        'modify' => __('Modify', 'niwhiboutik'),
        'save' => __('Save', 'niwhiboutik'),
        'search' => __('Search', 'niwhiboutik'),
        'search_products_to_import' => [
            'title' => __('Search products to import', 'niwhiboutik'),
            'description' => __("Here you can search for products and import them manually. This method is recommended when you have updated a small amount of products in Hiboutik to avoid running a full import catalogue that may take time and resources.

            You only need to start writing in the search bar to find products to import.", 'niwhiboutik'),
        ],
        'search_by_id' => __('Search by ID', 'niwhiboutik'),
        'search_by_name' => __('Search by name', 'niwhiboutik'),
        'no_results' => __('No results', 'niwhiboutik'),
        'search_in_progress' => __('Search in progress', 'niwhiboutik'),
        'please' => [
            'three_characters' => __('Please enter at least three characters', 'niwhiboutik'),
            'login_info' => __('Please enter your login info', 'niwhiboutik'),
        ],
        'search_products' => __('Search products', 'niwhiboutik'),
        'search_error' => __('Search error', 'niwhiboutik'),
        'product_found' => __('Product found', 'niwhiboutik'),
        'products_found' => __('Products found', 'niwhiboutik'),
        'by_name' => __('by name', 'niwhiboutik'),
        'by_id' => __('by ID', 'niwhiboutik'),
        'error_importing_product' => __('Error importing product', 'niwhiboutik'),
        'product' => __('Product', 'niwhiboutik'),
        'the_product' => __('The product', 'niwhiboutik'),
        'was_not_imported' => __('was not imported', 'niwhiboutik'),
        'was_imported' => __('was imported', 'niwhiboutik'),
        'import' => __('Import', 'niwhiboutik'),
        'importing_product' => __('Importing product', 'niwhiboutik'),
        'import_status' => __('Import status', 'niwhiboutik'),
        'error_retrieving_information' => __('Error retrieving information', 'niwhiboutik'),
        'login_info_saved' => __('Login info saved', 'niwhiboutik'),
        'error_saving_login_info' => __('Error saving login info, please contact your server administrator or support.', 'niwhiboutik'),
        'auth_error' => __('Authentication error', 'niwhiboutik'),
        'auth_success' => __('Authentication success', 'niwhiboutik'),
        'auth_error_messages' => [
            'unauthorized' => __('Unauthorized', 'niwhiboutik'),
            'invalid_credentials' => __('Invalid credentials', 'niwhiboutik'),
            'internal_error' => __('Internal error', 'niwhiboutik'),
            'page_not_found' => __('Page not found', 'niwhiboutik'),
            'cannot_find_link' => __('Cannot find link', 'niwhiboutik'),
        ],
        'hiboutik_connection_status' => __('Hiboutik connection status', 'niwhiboutik'),
        'hiboutik_login_data' => __('Hiboutik login data', 'niwhiboutik'),
        'settings' => [
            'title' => __('Settings', 'niwhiboutik'),
            'hiboutik_link' => __('Hiboutik link', 'niwhiboutik'),
            'hiboutik_link_help' => __('Enter the link to your Hiboutik account followed by "/api" (example: https://mycompany.hiboutik.com/api)', 'niwhiboutik'),
            'hiboutik_login' => __('Hiboutik login info', 'niwhiboutik'),
            'hiboutik_login_help' => __('Enter your Hiboutik login (generally an email)', 'niwhiboutik'),
            'hiboutik_token' => __('Hiboutik token', 'niwhiboutik'),
            'hiboutik_token_help' => __('Enter your Hiboutik token', 'niwhiboutik'),
            'hiboutik_doc' => __('Official Hiboutik documentation', 'niwhiboutik'),
            'plugin_activation' => __('Plugin activation', 'niwhiboutik'),
            'byniwee_key' => __('Hiboutik byNiWee key', 'niwhiboutik'),
            'byniwee_key_help' => __('Enter your Hiboutik byNiWee key', 'niwhiboutik'),
            'byniwee_doc' => __('Official Byniwee documentation', 'niwhiboutik'),
        ],
        'import_images' => __('Import images', 'niwhiboutik'),
        'refresh' => __('Refresh', 'niwhiboutik'),
        'orders' => [
            'title' => __('Orders', 'niwhiboutik'),
            'description' => __('Here you can see the orders that have been imported from Hiboutik.', 'niwhiboutik'),
            'no_orders' => __('No orders', 'niwhiboutik'),
            'loading' => __('Loading orders', 'niwhiboutik'),
            'order' => __('Order', 'niwhiboutik'),
            'order_number' => __('Order number', 'niwhiboutik'),
            'order_date' => __('Order date', 'niwhiboutik'),
            'order_status' => __('Order status', 'niwhiboutik'),
            'order_total' => __('Order total', 'niwhiboutik'),
            'order_customer' => __('Order customer', 'niwhiboutik'),
            'order_customer_email' => __('Order customer email', 'niwhiboutik'),
            'order_customer_phone' => __('Order customer phone', 'niwhiboutik'),
            'order_customer_address' => __('Order customer address', 'niwhiboutik'),
            'order_customer_city' => __('Order customer city', 'niwhiboutik'),
            'order_customer_postcode' => __('Order customer postcode', 'niwhiboutik'),
            'order_customer_country' => __('Order customer country', 'niwhiboutik'),
            'order_customer_note' => __('Order customer note', 'niwhiboutik'),
            'order_items' => __('Order items', 'niwhiboutik'),
            'order_item' => __('Order item', 'niwhiboutik'),
            'order_item_name' => __('Order item name', 'niwhiboutik'),
            'order_item_quantity' => __('Order item quantity', 'niwhiboutik'),
            'order_item_price' => __('Order item price', 'niwhiboutik'),
            'order_item_total' => __('Order item total', 'niwhiboutik'),
            'order_item_sku' => __('Order item SKU', 'niwhiboutik'),
            'order_item_variation' => __('Order item variation', 'niwhiboutik'),
            'order_item_variation_id' => __('Order item variation ID', 'niwhiboutik'),
            'order_item_variation_attributes' => __('Order item variation attributes', 'niwhiboutik'),
        ],
        'menu_options' => [
            'title' => __('Menu Options', 'niwhiboutik'),
            'description' => __('You can choose which options you would like to use on the custom generated menu. Disabled it, regenerate it, customize it, do what you want with it !', 'niwhiboutik'),
            'regenerate' => __('Regenerate', 'niwhiboutik'),
            'include_shop' => __('Include shop', 'niwhiboutik'),
        ],
    );
}

// Register activation hook
register_activation_hook(__FILE__, 'niwhiboutik_activate');
function niwhiboutik_activate()
{
    NiwHiboutik::activate();
    if (!wp_next_scheduled('nwh_import_daily')) {
        wp_schedule_event(strtotime('3am tomorrow'), 'daily', 'nwh_import_daily');
    }
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'niwhiboutik_deactivate');
function niwhiboutik_deactivate()
{
    NiwHiboutik::deactivate();
    wp_clear_scheduled_hook('nwh_import_daily');
}

// Add links to the plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'niwhiboutik_settings_link');
function niwhiboutik_settings_link($links)
{
    // Build and escape the URL.
    $url = esc_url(add_query_arg(
        'page',
        'dashboard',
        get_admin_url() . 'admin.php'
    ));
    // Create the link.
    $settings_link = "<a href='$url'>" . __('Paramètres') . '</a>';
    // Adds the link to the end of the array.
    array_push(
        $links,
        $settings_link
    );
    return $links;
}

// Set token on login
add_action('wp_login', 'nwh_set_api_token');
function nwh_set_api_token()
{
    if (is_admin()) {
        NiwHiboutik::set_token();
    }
}

// Check token on site load
add_action('init', 'check_hiboutik_token');
function check_hiboutik_token()
{
    if (is_user_logged_in() && is_admin()) {
        NiwHiboutik::set_token();
    }
}

// Destroy token on logout
add_action('wp_logout', 'nwh_remove_api_token');
function nwh_remove_api_token()
{
    setcookie('wp_api_token', '', -1000000);
}

/**
 * Update or create the cron schedule info
 */
function cron_schedule_info($date)
{
    $dt = new DateTime($date);
    $dt->setTimezone(new DateTimeZone(wp_timezone_string()));

    $at = __('at', 'niwhiboutik');
    $datetime = $dt->format("d/m/Y");
    $time = $dt->format("H:i:s");
    $message = __('The next automatic import will occur on the ') . $datetime . " " . $at . " " . $time;

    if (!get_option('nwh_cron_schedule')) {
        add_option('nwh_cron_schedule', $message);
    } else {
        update_option('nwh_cron_schedule', $message);
    }
}

if (get_option('nwh_license_activated') === 'true') {

    if (wp_next_scheduled('nwh_import_daily')) {
        cron_schedule_info('3am tomorrow');
    }

    // Set cron hook
    add_action('nwh_import_daily', 'nwh_import_cron_run');
    function nwh_import_cron_run()
    {
        error_log('Import cron');
        Api::cron_import_all_products();
    }
} else {
    if (get_option('nwh_cron_schedule')) {
        delete_option('nwh_cron_schedule');
    }
}

use Whoops\Handler\JsonResponseHandler;
use Whoops\Run;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

define('NIWHIBOUTIK_VERSION', '1.0.0');
define('NIWHIBOUTIK_PLUGIN_DIR', plugin_dir_path(__FILE__) . 'niwhiboutik/');

require __DIR__ . '/lib/updater/plugin-update-checker.php';


$updater = PucFactory::buildUpdateChecker(
    'https://github.com/niwee-productions/niwhiboutik/',
    __FILE__,
    'niwhiboutik.zip'
);

$updater->setBranch('main');

$updater->setAuthentication('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');
