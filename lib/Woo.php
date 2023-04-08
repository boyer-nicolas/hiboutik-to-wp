<?php

namespace Niwee\Niwhiboutik;

class Woo
{
    private Category $category;
    private Brand $brand;
    private Stock $stock;
    private Product $product;
    private Menu $menu;

    public function __construct()
    {
        $this->category = new Category();
        $this->brand = new Brand();
        $this->stock = new Stock();
        $this->product = new Product();
        $this->menu = new Menu();
    }

    /**
     * Actually fetch and import all products
     */
    public function import_products($request = null): void
    {
        $this->category = new Category();
        $this->brand = new Brand();
        $this->stock = new Stock();
        $this->product = new Product();

        if ($request !== null) {
            $import_images = $request->get_param('import_images');
        } else {
            $import_images = true;
        }

        if (
            file_exists(sys_get_temp_dir() . '/importAllProducts') && file_get_contents(
                sys_get_temp_dir() . '/importAllProducts'
            ) === 'run'
        ) {
            exit('Import is already running');
        } else {
            set_time_limit(0);

            Api::write_import_status(
                __(
                    'Initializing',
                    'niwhiboutik'
                )
            );

            $this->category->setAll();
            $this->category->importAll();

            $this->brand->setAll();
            $this->brand->importAll();

            $this->stock->setAll();

            $this->product->setAll();
            $this->product->importAll($import_images);

        }
    }
}
