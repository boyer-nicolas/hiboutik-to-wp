<?php

namespace Niwee\Niwhiboutik;

use Exception;
use \GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Product
{
    private $products;
    private $hiboutik;
    private Menu $menu;
    private $estimated_time_left;
    private $time_start;
    private ByNiWee $byniwee;
    private $status_message_base;

    /**
     * @var float|int
     */
    private $estimated_time;
    private $debug;
    private $stocks;
    private $time_end;
    private string $execution_time;
    private string $hiboutik_upload_dir;

    public function __construct()
    {
        $this->hiboutik = new Hiboutik();
        $this->menu = new Menu();
        $this->byniwee = new ByNiWee();
        $this->category = new Category();
        $stock = new Stock();
        $this->stocks = $stock->getAll();
        $this->debug = false;
        $this->brand = new Brand();
    }

    public function getAll()
    {
        return $this->products;
    }

    public function setAll()
    {
        $this->products = $this->hiboutik->fetch();
    }

    public function importAll(bool $import_images = true)
    {
        Api::write_import_status(
            __(
                'Importing ',
                'niwhiboutik'
            ) . count($this->products) . __(
                ' products in Woocommerce.',
                'niwhiboutik'
            )
        );

        $this->time_start = microtime(true);

        $this->startImport();

        $errors = 0;

        foreach ($this->products as $index => $product)
        {
            if (file_get_contents(sys_get_temp_dir() . '/importAllProducts') != 'run')
            {
                break;
            }
            else
            {
                if ($this->byniwee->is_license_activated_simple() === false)
                {
                    break;
                }
                else
                {
                    try
                    {
                        $importPercentage = round(
                            ($index / count($this->products)) * 100,
                            1
                        );

                        $this->status_message_base = "$importPercentage | " . $product['product_id'] . "/" . count(
                            $this->products
                        ) . " | " . $product['product_model'] . ' | ' . $this->estimated_time_left . " | ";

                        $this->importSingle(
                            $product,
                            $import_images
                        );

                        if ($index !== 0)
                        {
                            $this->estimated_time = $this->time_start + (count($this->products) * (microtime(
                                true
                            ) - $this->time_start) / $index);

                            $this->estimated_time_left = Utils::formatMicrotime(
                                $this->estimated_time - microtime(true) / 60
                            );

                            if ($this->estimated_time_left === 1)
                            {
                                $this->estimated_time_left = __(
                                    'Less than 1',
                                    'niwhiboutik'
                                );
                            }
                        }
                        else
                        {
                            $this->estimated_time_left = 0;
                        }
                    }
                    catch (Exception $exception)
                    {
                        $errors++;
                        Api::write_import_status(
                            __(
                                'Cannot import product: ' . $exception->getMessage(),
                                'niwhiboutik'
                            )
                        );
                        error_log("Niwhiboutik Import Error: " . $exception->getMessage());
                    }
                }
            }
        }

        Api::write_import_status(
            __(
                'Cleaning up',
                'niwhiboutik'
            )
        );

        $this->menu->generate();

        unlink(sys_get_temp_dir() . '/importAllProducts');

        $this->time_end = microtime(true);

        $this->execution_time = Utils::formatMicrotime(($this->time_end - $this->time_start) / 60);

        if ($errors === 0)
        {
            return Notice::success($this->execution_time);
        }
        else
        {
            return Notice::error($this->execution_time);
        }
    }

    public function startImport(): bool
    {
        try
        {
            return file_put_contents(
                sys_get_temp_dir() . '/importAllProducts',
                'run'
            );
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    /**
     * Import a single product
     *
     * @throws Exception
     */
    public function importSingle(
        $product,
        bool $import_images = true
    ): array
    {
        $stop_at_end = false;
        $remove_product = 0;
        // Check if this is a single direct import or from the global import loop
        try
        {
            if (gettype($product) === 'string')
            {
                $product_id = $product;
                $temp_product = $this->hiboutik->get($product_id);
                $hb_product = $temp_product[0];
            }
            else
            {
                $product_id = $product['product_id'];
                $hb_product = $product;
            }

            if (!$this->isImporting())
            {
                $this->stopImport();
            }

            Api::write_import_status(
                __(
                    'Importing product #' . $product_id . ' in Woocommerce',
                    'niwhiboutik'
                )
            );

            if ($this->status_message_base === null)
            {
                $this->status_message_base = "Import du produit #" . $product;
            }

            Api::write_import_status($this->status_message_base . "Initialisation");
        }
        catch (Exception $e)
        {
            Api::write_import_status(
                __(
                    'The product could not be imported',
                    'niwhiboutik'
                )
            );
            error_log($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => __(
                    'The product could not be imported',
                    'niwhiboutik'
                ),
            ];
        }

        // Check if product is to be imported
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'Metadata',
                    'niwhiboutik'
                )
            );
            if ($hb_product['product_arch'])
            {
                Api::write_import_status(
                    $this->status_message_base . __(
                        'The product is archived and will not be imported',
                        'niwhiboutik'
                    )
                );

                $remove_product = 1;
            }

            if (
                array_key_exists(
                    'product_display_www',
                    $hb_product
                ) && $hb_product['product_display_www'] === 0
            )
            {
                Api::write_import_status(
                    $this->status_message_base . __(
                        'The product is set not to display and will not be imported',
                        'niwhiboutik'
                    )
                );

                $remove_product = 1;
            }
        }
        catch (Exception $e)
        {
            error_log('Cannot find archived state on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        $is_variable = false;

        if (array_key_exists("product_size_details", $hb_product))
        {
            $is_variable = true;
        }

        $sku = $hb_product['product_barcode'];

        // Check if product exists via barcode
        try
        {
            if (wc_get_product_id_by_sku($sku))
            {
                $wc_product = wc_get_product(wc_get_product_id_by_sku($sku));

                if ($remove_product === 1)
                {
                    $this->delete($wc_product);

                    return [
                        'status' => 'success',
                        'message' => $this->status_message_base . __(
                            'Import is done',
                            'niwhiboutik'
                        )
                    ];
                }
            }
            else
            {
                if ($remove_product === 1)
                {
                    return [
                        'status' => 'success',
                        'message' => $this->status_message_base . __(
                            'Import is done',
                            'niwhiboutik'
                        )
                    ];
                }
                else
                {
                    if ($is_variable)
                    {
                        $wc_product = new \WC_Product_Variable();
                    }
                    else
                    {
                        $wc_product = new \WC_Product();
                    }
                }
            }
        }
        catch (Exception $e)
        {
            error_log($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set Description
        $description = '';
        try
        {
            if (isset($hb_product['product_desc']))
            {
                $description = $hb_product['product_desc'];
            }
            else
            {
                if (isset($hb_product['products_desc']))
                {
                    $description = $hb_product['products_desc'];
                }
            }
            $wc_product->set_description($description);
        }
        catch (Exception $e)
        {
            error_log('Cannot find description on product #' . $product_id . " : " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set name & SKU
        try
        {
            $wc_product->set_name($hb_product['product_model']);
            $wc_product->set_sku($hb_product['product_barcode']);
        }
        catch (Exception $e)
        {
            error_log('Cannot find name on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // If product variation, set all variations attributes
        try
        {
            if ($is_variable)
            {
                if ($this->debug === true)
                {
                    error_log('Product is variable, setting attributes');
                }

                $attributes = [];

                foreach ($hb_product['product_specific_rules'] as $size)
                {
                    $attr_parent = $hb_product['product_size_type'];

                    $size_types = $this->hiboutik->get_size_types();

                    $matching_size_type = array_filter(
                        $size_types,
                        function ($size_type) use ($attr_parent)
                        {
                            return $size_type['size_type_id'] === $attr_parent;
                        }
                    );

                    $matching_size_type = array_values($matching_size_type);

                    $attr_parent = $matching_size_type[0];

                    $attr_children = $this->hiboutik->get_size_children($attr_parent['size_type_id']);
                    $label = $attr_parent['size_type_name'];
                    $attr_name = sanitize_title($label);

                    $options = [];
                    foreach ($attr_children as $child)
                    {
                        $options[] = $child['size_name'];
                    }

                    $attribute = new \WC_Product_Attribute();
                    $attribute->set_id(0);
                    $attribute->set_name($label);
                    $attribute->set_options($options);
                    $attribute->set_position(0);
                    $attribute->set_visible(true);
                    $attribute->set_variation(true);

                    $attributes[] = $attribute;
                }

                $wc_product->set_attributes($attributes);
            }
        }
        catch (Exception $e)
        {
            error_log('Cannot find variation attributes on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set variations
        try
        {
            if ($is_variable)
            {
                foreach ($hb_product['product_size_details'] as $size)
                {
                    $attr_parent = $hb_product['product_size_type'];

                    $size_types = $this->hiboutik->get_size_types();

                    $matching_size_type = array_filter(
                        $size_types,
                        function ($size_type) use ($attr_parent)
                        {
                            return $size_type['size_type_id'] === $attr_parent;
                        }
                    );

                    $matching_size_type = array_values($matching_size_type);

                    $attr_parent = $matching_size_type[0];

                    $attr_children = $this->hiboutik->get_size_children($attr_parent['size_type_id']);
                    $label = $attr_parent['size_type_name'];
                    $attr_name = sanitize_title($label);

                    if (wc_get_product_id_by_sku($size['barcode']))
                    {
                        $variation = wc_get_product(wc_get_product_id_by_sku($size['barcode']));
                    }
                    else
                    {
                        $variation = new \WC_Product_Variation();
                    }

                    $variation->set_parent_id($wc_product->get_id());
                    $size_name = $size['size_name'];

                    $variation->set_sku($size['barcode']);

                    $variation->set_regular_price($hb_product['product_price']);

                    $variation->set_manage_stock(true);

                    // Disable stock management at product level
                    $wc_product->set_manage_stock(false);

                    if (array_key_exists('stock_available', $hb_product))
                    {
                        $stock_available = $hb_product['stock_available'];
                        foreach ($stock_available as $stock)
                        {
                            if ($stock['product_size'] === $size['size_id'])
                            {
                                $variation->set_stock_quantity($stock['stock_available']);
                            }
                        }
                    }

                    $variation_attributes = [];
                    foreach ($attr_children as $child)
                    {
                        if (gettype($child) === "array" && array_key_exists("size_name", $child) && $child['size_name'] == $size_name)
                        {
                            $variation_attributes[$attr_name] = $child['size_name'];
                        }
                    }
                    $variation->set_attributes($variation_attributes);

                    $variation->save();
                }
            }
        }
        catch (Exception $e)
        {
            error_log('Cannot set variations on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set price
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'Price',
                    'niwhiboutik'
                )
            );

            $wc_product->set_regular_price($hb_product['product_price']);
            $wc_product->set_price($hb_product['product_price']);

            $wc_product->set_sale_price(
                $hb_product['product_discount_price'] > 0 ? $hb_product['product_discount_price'] : ""
            );
        }
        catch (Exception $e)
        {
            error_log('Cannot find price on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set VAT
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'VAT',
                    'niwhiboutik'
                )
            );

            $vat = $hb_product['product_vat'];

            if ($vat == 0)
            {
                $wc_product->set_tax_status('none');
            }
            else
            {
                $wc_product->set_tax_status('taxable');
                $wc_product->set_tax_class($vat);

                // $vat_value = $hb_product['product_vat_value'];
            }
        }
        catch (Exception $e)
        {
            error_log('Cannot find VAT on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set visibility
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'Visibility',
                    'niwhiboutik'
                )
            );

            $wc_product->set_catalog_visibility($hb_product['product_display'] == 1 ? 'visible' : 'hidden');
        }
        catch (Exception $e)
        {
            error_log('Cannot find visibility on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set stock
        try
        {

            if (!$is_variable)
            {

                Api::write_import_status(
                    $this->status_message_base . __(
                        'Getting stock',
                        'niwhiboutik'
                    )
                );

                $wc_product->set_manage_stock($hb_product['product_stock_management']);

                if ($hb_product['product_stock_management'])
                {
                    if ($this->stocks === null)
                    {
                        $stock = $this->hiboutik->get_stock_by_id($product_id);
                    }
                    else
                    {
                        $stock = $this->hiboutik->get_stock(
                            $hb_product,
                            $this->stocks
                        );
                    }

                    if (is_array($stock))
                    {
                        $global_stock = 0;
                        $i = 0;
                        foreach ($stock as $stock_item)
                        {
                            $global_stock += $stock_item['stock_available'];
                            $i++;
                        }

                        if ($i > 1)
                        {
                            // Product is variable, set stock management at variation level
                            $wc_product->set_manage_stock(false);
                        }
                        if (
                            !array_key_exists(
                                'stock_available',
                                $stock
                            )
                        )
                        {
                            if (isset($stock[0]['stock_available']))
                            {
                                $stock = $stock[0]['stock_available'];
                            }
                            else
                            {
                                $stock = 0;
                            }
                        }

                        $wc_product->set_stock_quantity($global_stock);
                        $wc_product->set_stock_status($global_stock > 0 ? 'instock' : 'outofstock');
                    }
                    else
                    {
                        if (!$stock)
                        {
                            $wc_product->set_stock_quantity(0);
                            $wc_product->set_stock_status('outofstock');
                        }
                        else
                        {
                            $wc_product->set_stock_quantity($stock);
                            $wc_product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
                        }
                    }
                }
            }
        }
        catch (Exception $e)
        {
            error_log(
                'Cannot find stock on product #' . $product_id . " : " . $e->getMessage() . " : " . $e->getTraceAsString()
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set refs
        try
        {
            $wc_product->add_meta_data(
                'products_ref_ext',
                $hb_product['products_ref_ext']
            );
        }
        catch (Exception $e)
        {
            error_log('Cannot find external ref on product #' . $product_id . " : " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
        if ($import_images === true)
        {

            // Set image(s)
            try
            {
                Api::write_import_status(
                    $this->status_message_base . __(
                        'Downloading images',
                        'niwhiboutik'
                    )
                );

                wp_delete_post($wc_product->get_image_id());

                // Get image gallery
                $gallery = $wc_product->get_gallery_image_ids();

                // Delete all images
                foreach ($gallery as $image_id)
                {
                    wp_delete_post($image_id);
                }

                $upload_dir = wp_upload_dir();

                $this->hiboutik_upload_dir = $upload_dir['basedir'] . '/hiboutik';
                if (!file_exists($this->hiboutik_upload_dir))
                {
                    mkdir(
                        $this->hiboutik_upload_dir,
                        0755,
                        true
                    );
                }

                for ($i = 1; $i <= 100; $i++)
                {

                    if (!$this->import_image($wc_product, $hb_product, $description, $i))
                    {
                        break;
                    }
                    else
                    {
                        Api::write_import_status(
                            $this->status_message_base . __(
                                "Image $i downloaded",
                                'niwhiboutik'
                            )
                        );
                    }

                    Api::write_import_status(
                        $this->status_message_base . __(
                            'Images downloaded',
                            'niwhiboutik'
                        )
                    );
                }
            }
            catch (Exception $e)
            {
                error_log(
                    'Error while retrieving images on product #' . $hb_product['product_id'] . ': ' . $e->getMessage()
                );

                return [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            catch (GuzzleException $e)
            {
                error_log(
                    'Error while retrieving images on product #' . $hb_product['product_id'] . ': ' . $e->getMessage()
                );

                return [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        // Set categories
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'Categories',
                    'niwhiboutik'
                )
            );

            if (empty($fetched_categories))
            {
                $fetched_categories = $this->hiboutik->get_categories();
            }

            $categories = $this->category->getExisting($hb_product);

            $wc_product->set_category_ids($categories);
        }
        catch (Exception $e)
        {
            error_log(
                'Error while retrieving categories on product #' . $hb_product['product_id'] . ': ' . $e->getMessage()
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Set brands
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'Brands',
                    'niwhiboutik'
                )
            );

            if (empty($fetched_brands))
            {
                $fetched_brands = $this->hiboutik->get_brands();
            }

            $brands = $this->brand->getExisting($hb_product);

            wp_set_post_terms(
                $wc_product->get_id(),
                $brands,
                'product_brand'
            );
        }
        catch (Exception $e)
        {
            error_log(
                'Error while retrieving brands on product #' . $hb_product['product_id'] . ': ' . $e->getMessage()
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        // Save
        try
        {
            Api::write_import_status(
                $this->status_message_base . __(
                    'Saving',
                    'niwhiboutik'
                )
            );
            $wc_product->save_meta_data();
            $wc_product->save();
            Api::write_import_status(
                $this->status_message_base . __(
                    'Import is done',
                    'niwhiboutik'
                )
            );
        }
        catch (Exception $e)
        {
            Api::write_import_status($this->status_message_base . "Erreur: " . $e->getMessage());
            error_log($this->status_message_base . "Erreur: " . $e->getMessage() . ' - ' . $e->getTraceAsString());

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        if ($stop_at_end === true)
        {
            $this->stopImport();
        }


        return [
            'status' => 'success',
            'message' => $this->status_message_base . __(
                'Import is done',
                'niwhiboutik'
            )
        ];
    }

    /**
     * @return bool
     */
    public function isImporting(): bool
    {
        if (file_exists(sys_get_temp_dir() . '/importAllProducts'))
        {
            if (file_get_contents(sys_get_temp_dir() . '/importAllProducts') === 'run')
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Stop the current import
     */
    public function stopImport(): bool
    {
        try
        {
            if (
                file_put_contents(
                    sys_get_temp_dir() . '/importAllProducts',
                    'stop'
                )
            )
            {
                Notice::stop();

                return true;
            }
            else
            {
                return false;
            }
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    /**
     * Method to delete Woo Product
     *
     * @param object $product
     * @param bool $force true to permanently delete product, false to move to trash.
     * @return bool
     * @throws Exception
     */
    public function delete(
        object $product,
        $force = TRUE
    ): bool
    {
        if (empty($product))
        {
            throw new Exception(
                __(
                    'No product was found',
                    'woocommerce'
                )
            );
        }

        // If we're forcing, then delete permanently.
        if ($force)
        {
            if ($product->is_type('variable'))
            {
                foreach ($product->get_children() as $child_id)
                {
                    $child = wc_get_product($child_id);
                    $child->delete(true);
                }
            }
            elseif ($product->is_type('grouped'))
            {
                foreach ($product->get_children() as $child_id)
                {
                    $child = wc_get_product($child_id);
                    $child->set_parent_id(0);
                    $child->save();
                }
            }

            $product->delete(true);
            $result = !($product->get_id() > 0);
        }
        else
        {
            $product->delete();
            $result = 'trash' === $product->get_status();
        }

        if (!$result)
        {
            throw new Exception(
                __(
                    'This %s cannot be deleted',
                    'woocommerce'
                )
            );
        }

        // Delete parent product transients.
        if ($parent_id = wp_get_post_parent_id($product))
        {
            wc_delete_product_transients($parent_id);
        }

        return true;
    }

    /**
     * @param object $wc_product
     * @param array $hb_product
     * @param string $description
     * @param int $i
     * @return bool
     * @throws GuzzleException
     */
    private function import_image(
        object $wc_product,
        array  $hb_product,
        string $description,
        int    $i
    ): bool
    {

        $file = $this->hiboutik_upload_dir . '/' . 'product-' . $hb_product['product_id'] . '-' . $i . '.jpg';

        $filename = basename('product-' . $hb_product['product_id'] . '-' . $i . '.jpg');

        $client = new Client([
            'base_uri' => "https://{$this->hiboutik->hiboutik_link}.hiboutik.com/api/products_images/",
        ]);

        $response = $client->get('big_' . $hb_product['product_id'] . '-' . $i . '.jpg', [
            'auth' => [
                $this->hiboutik->hiboutik_login,
                $this->hiboutik->hiboutik_key
            ],
            'stream' => true
        ]);

        $remoteImage = $response->getBody()->getContents();

        Api::write_import_status(
            $this->status_message_base . __(
                'Saving images',
                'niwhiboutik'
            )
        );

        if (file_put_contents($file, $remoteImage))
        {
            if (strlen($remoteImage) > 1000)
            {
                $wp_filetype = wp_check_filetype(
                    $filename,
                    null
                );

                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($hb_product['product_model']),
                    'post_content' => $description,
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment(
                    $attachment,
                    $file,
                    $wc_product->get_id()
                );

                if (!is_wp_error($attach_id))
                {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');

                    $attach_data = wp_generate_attachment_metadata(
                        $attach_id,
                        $file
                    );

                    wp_update_attachment_metadata(
                        $attach_id,
                        $attach_data
                    );

                    if ($i === 1)
                    {
                        set_post_thumbnail(
                            $wc_product->get_id(),
                            $attach_id
                        );

                        $wc_product->set_image_id($attach_id);
                    }
                    else if ($i > 1)
                    {

                        $media = get_attached_media(
                            'image',
                            $wc_product->get_id()
                        );

                        $media_ids = [];

                        foreach ($media as $media_item)
                        {
                            $media_ids[] = $media_item->ID;
                        }

                        $media_ids[] = $attach_id;

                        // Remove main image from gallery
                        if (($key = array_search($wc_product->get_image_id(), $media_ids)) !== false)
                        {
                            unset($media_ids[$key]);
                        }

                        $wc_product->set_gallery_image_ids($media_ids);
                    }

                    return true;
                }
            }
        }
        else
        {
            error_log(
                'Error while saving images on product #' . $hb_product['product_id'] . ': Impossible de sauvegarder l\'image:' . $remoteImage
            );
        }
        return false;
    }
}
