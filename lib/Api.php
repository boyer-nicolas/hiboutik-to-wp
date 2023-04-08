<?php

namespace Niwee\Niwhiboutik;

use Exception;

class Api
{
    public string $import_status;
    private Woo $woo;
    private Hiboutik $hiboutik;
    private ByNiWee $byniwee;
    private Menu $menu;
    /**
     * @var array|string|string[]
     */
    private string $nwh_dir;
    private Product $product;

    public function __construct()
    {
        $this->woo = new Woo();
        $this->hiboutik = new Hiboutik();
        $this->byniwee = new ByNiWee();
        $this->menu = new Menu();
        $this->product = new Product();
        $this->nwh_dir = str_replace(
            'lib/',
            '',
            plugin_dir_path(__FILE__)
        );
        $this->api_routes();
    }

    /**
     * Register custom API routes
     */
    public function api_routes()
    {
        $namespace = 'niwhiboutik/v1';
        add_action(
            'rest_api_init',
            function () use (
                $namespace
            ) {
                if (get_option('nwh_license_activated') === 'true') {
                    register_rest_route(
                        $namespace,
                        '/fetch',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'fetch_hiboutik_products'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/import',
                        array(
                            'methods' => 'POST',
                            'callback' => array($this, 'import_woocommerce_products'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/update-hiboutik-params',
                        array(
                            'methods' => 'POST',
                            'callback' => array($this, 'update_hiboutik_params'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/update-byniwee-params',
                        array(
                            'methods' => 'POST',
                            'callback' => array($this, 'update_byniwee_params'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/get-hiboutik-params',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'get_hiboutik_params'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/get-byniwee-params',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'get_byniwee_params'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/get-import-status',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'get_import_status'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/is-importing',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'is_importing'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/search-products',
                        array(
                            'methods' => 'POST',
                            'callback' => array($this, 'search_products'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/stop-import',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'stop_import'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/clear-import-status',
                        array(
                            'methods' => 'GET',
                            'callback' => array($this, 'clear_import_status'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/import-product',
                        array(
                            'methods' => 'POST',
                            'callback' => array($this, 'import_single_product'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );

                    register_rest_route(
                        $namespace,
                        '/regenerate-menu',
                        array(
                            'methods' => 'POST',
                            'callback' => array($this, 'regenerate_menu'),
                            'permission_callback' => array($this, 'security_check'),
                        )
                    );
                }

                register_rest_route(
                    $namespace,
                    '/check-success-notice',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'check_success_notice'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/check-error-notice',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'check_error_notice'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/check-stop-notice',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'check_stop_notice'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/get-cron-schedule',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'get_cron_schedule_message'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/get-byniwee-license',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'get_byniwee_license'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/update-byniwee-license',
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'update_byniwee_license'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/activations-left',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'activations_left'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/activate-license',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'activate_license'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );

                register_rest_route(
                    $namespace,
                    '/is-activated',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'is_activated'),
                        'permission_callback' => array($this, 'security_check'),
                    )
                );
            }
        );
    }

    /**
     * Generate API token
     *
     * @throws Exception
     */
    public static function generate_token(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Write import status
     */
    public static function write_import_status(string $message): bool
    {
        return NWHLogger::write($message);
    }

    public static function cron_import_all_products()
    {
        $woo = new Woo();
        $woo->import_products();
    }

    /**
     * Regenerate the menu
     */
    public function regenerate_menu($request): bool
    {
        try {
            $this->menu->generate($request);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Is license activated
     */
    public function is_activated(): bool
    {
        return get_option('nwh_license_activated') === 'true';
    }

    /**
     * Activate license
     */
    public function activate_license(): array
    {
        return $this->byniwee->activate_license();
    }

    /**
     * Get number of activations left
     */
    public function activations_left(): array
    {
        return $this->byniwee->activations_left();
    }

    /**
     * Get license key
     */
    public function get_byniwee_license(): array
    {
        return $this->byniwee->get_license();
    }

    /**
     * Update license key
     */
    public function update_byniwee_license($request): array
    {
        return $this->byniwee->update_license($request);
    }

    /**
     * Get cron schedule message
     */
    public function get_cron_schedule_message(): string
    {
        return get_option('nwh_cron_schedule');
    }

    /**
     * Check if there is a success notice to display
     */
    public function check_success_notice(): string
    {
        return get_option('nwh_import_success');
    }

    /**
     * Check if there is an error notice to display
     */
    public function check_error_notice(): string
    {
        return get_option('nwh_import_error');
    }

    /**
     * Check if there is an stop notice to display
     */
    public function check_stop_notice(): string
    {
        return get_option('nwh_import_stop');
    }

    /**
     * Check if user is logged in
     *
     * @return boolean
     */
    public function security_check($request): bool
    {
        $token = $request->get_header('Authorization');
        $token = str_replace(
            "Bearer ",
            "",
            $token
        );
        $token = sanitize_text_field($token);

        return $this->verify_token($token);
    }

    /**
     * Check if token is valid
     */
    public function verify_token(string $token): bool
    {
        $token_db = $this->get_token();

        if ($token === $token_db) {
            return true;
        }

        return false;
    }

    /**
     * Get token in db
     */
    public function get_token(): string
    {
        return get_option('nwh_api_token');
    }

    /**
     * Get Hiboutik login info from database
     */
    public function get_hiboutik_params(): array
    {
        return $this->hiboutik->get_params();
    }

    /**
     * Insert Hiboutik login info in database
     */
    public function update_hiboutik_params($request): array
    {
        return $this->hiboutik->update_params($request);
    }

    /**
     * Get import status messages
     */
    public function get_import_status(): string
    {
        return NWHLogger::latest_log();
    }

    /**
     * Remove import statuses in db
     */
    public function clear_import_status(): bool
    {
        return NWHLogger::clear();
    }

    /**
     * Search a product to import
     */
    public function search_products($request): array
    {
        return $this->hiboutik->search_products($request);
    }

    /**
     * Check if an import is already running
     */
    public function is_importing(): bool
    {
        return $this->product->isImporting();
    }

    public function stop_import(): bool
    {
        return $this->product->stopImport();
    }

    /**
     * Just fetch hiboutik products to ensure that the API is working and inform
     * the user on the frontend
     */
    public function fetch_hiboutik_products(): array
    {
        return $this->hiboutik->fetch();
    }

    /**
     * Import one single product
     */
    public function import_single_product($request): array
    {
        $product_id = $request->get_param('product_id');
        $product_id = sanitize_text_field($product_id);

        return $this->product->importSingle(
            $product_id,
            true,
        );
    }

    /**
     * Actually fetch and import all products
     */
    public function import_woocommerce_products($request): array
    {
        return $this->woo->import_products($request);
    }
}
