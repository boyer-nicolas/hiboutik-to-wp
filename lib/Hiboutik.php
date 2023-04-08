<?php

namespace Niwee\Niwhiboutik;

use \Hiboutik\HiboutikAPI;

class Hiboutik
{
    public $hiboutik_link;
    public $hiboutik_login;
    public $hiboutik_key;

    public function __construct()
    {
        $hiboutik_params = $this->get_params();
        $hiboutik_data = $hiboutik_params['data'];

        if (!isset($hiboutik_data['hiboutik_url']) || !isset($hiboutik_data['hiboutik_login']) || !isset($hiboutik_data['hiboutik_key']))
        {
            return;
        }

        $this->hiboutik_link = str_replace("https://", "", $hiboutik_data['hiboutik_url']);
        $this->hiboutik_link = str_replace(".hiboutik.com/api", "", $this->hiboutik_link);
        $this->hiboutik_login = $hiboutik_data['hiboutik_login'];
        $this->hiboutik_key = $hiboutik_data['hiboutik_key'];

        $this->api = new HiboutikAPI($this->hiboutik_link, $this->hiboutik_login, $this->hiboutik_key);
    }

    /**
     * Get Hiboutik login info from database
     */
    public function get_params()
    {
        $hiboutik_url = get_option('nwh_hiboutik_url');
        $hiboutik_login = get_option('nwh_hiboutik_login');
        $hiboutik_key = get_option('nwh_hiboutik_key');

        $data = [
            'hiboutik_url' => $hiboutik_url,
            'hiboutik_login' => $hiboutik_login,
            'hiboutik_key' => $hiboutik_key,
        ];

        return [
            'data' => $data,
        ];
    }

    public function fetch()
    {
        $retrieved_products = $this->api->get("/products/?p=1");

        $page = 1;
        while (count($retrieved_products) % 250 === 0 && count($retrieved_products) !== 0)
        {
            $page++;
            $productsListIteration = $this->api->get("/products/?p=" . $page);
            if (count($productsListIteration) === 0) break;
            $retrieved_products = array_merge($retrieved_products, $productsListIteration);
        }

        add_option('nwh_products_to_import', count($retrieved_products));
        sort($retrieved_products);

        return $retrieved_products;
    }

    /**
     * Search a product to import
     */
    public function search_products($request)
    {
        $search_query = $request->get_param('search_query');
        $search_type = $request->get_param('search_type');
        $search_query = sanitize_text_field($search_query);
        $search_type = sanitize_text_field($search_type);

        if ($search_type === "via-name")
        {
            $url = "/products/search/name/" . rawurlencode($search_query);
        }
        else if ($search_type === "via-id")
        {
            $url = "/products/" . $search_query;
        }
        else
        {
            return new \Exception('Please provide a valid search type.');
        }

        $retrieved_products = $this->api->get($url . '?p=1');

        if (isset($retrieved_products['error']))
        {
            if ($retrieved_products['details']['http_code'] === 404)
            {
                return;
            }
        }

        $page = 1;
        while (count($retrieved_products) % 250 === 0 && count($retrieved_products) !== 0)
        {
            $page++;
            $productsListIteration = $this->api->get("/products/search/name/" . $search_query . '?p=' . $page);
            if (count($productsListIteration) === 0) break;
            $retrieved_products = array_merge($retrieved_products, $productsListIteration);
        }

        sort($retrieved_products);

        return $retrieved_products;
    }

    /**
     * Insert Hiboutik login info in database
     */
    public function update_params($request)
    {
        $data = $request->get_json_params();

        extract($data);

        if (!isset($hiboutik_link) || !isset($hiboutik_login) || !isset($hiboutik_key))
        {
            return new \Exception('Please provide an API Link, an API Login and an API Password.');
        }

        $link = esc_url_raw($hiboutik_link);
        $login = sanitize_email($hiboutik_login);
        $key = sanitize_text_field($hiboutik_key);

        $initial_data = $this->get_params();
        $initial_data = $initial_data['data'];

        if ($link === $initial_data['hiboutik_url'] && $login === $initial_data['hiboutik_login'] && $key === $initial_data['hiboutik_key'])
        {
            return [
                'status' => 'success',
                'data' => 'No changes detected',
            ];
        }

        try
        {
            update_option('nwh_hiboutik_url', $link);
            update_option('nwh_hiboutik_login', $login);
            update_option('nwh_hiboutik_key', $key);
        }
        catch (\Exception $e)
        {
            return new \Exception($e);
        }
    }

    public function get_categories()
    {
        return $this->api->get("/categories?order_by=category_position&sort=ASC");
    }

    public function get_category($id)
    {
        $categories = $this->get_categories();
        $matching_category = array_filter($categories, function ($category) use ($id)
        {
            return $category['category_id'] === $id;
        });

        return array_shift($matching_category);
    }

    public function get(int $id)
    {
        return $this->api->get("/products/" . $id);
    }



    public function get_all_stocks()
    {
        return $this->api->get("/stock_available/all_wh/");
    }

    /**
     * Get stock
     */
    public function get_stock($productImported, $allStocks)
    {
        foreach ($allStocks as $productStock)
        {
            if ($productStock['product_barcode'] == $productImported['product_barcode'])
            {
                return $productStock['stock_available'];
            }
        }
        return null;
    }

    public function get_stock_by_id(int $id)
    {
        return $this->api->get("/stock_available/product_id/" . $id);
    }

    public function get_brands()
    {
        return $this->api->get("/brands/");
    }

    public function get_sizes()
    {
        return $this->api->get("/size_types/");
    }

    public function get_size_types()
    {
        return $this->api->get("/size_types/");
    }

    public function get_size_children($id)
    {
        $sizes = $this->api->get("/sizes/" . $id);
        return $sizes;
    }
}
