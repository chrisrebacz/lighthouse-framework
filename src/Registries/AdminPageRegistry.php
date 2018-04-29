<?php
namespace Lighthouse\Registries;

class AdminPageRegistry 
{

    public $items = [];
 
    public $hook = 'toplevel_page_youknow-admin';

    protected static $mainHook = null;

    public function run()
    {
        $this->getItems();
        if (function_exists('add_action')) {
            foreach ($this->items as $item) {
                $obj = app()->make($item);
            }
        }
    }

    public function getItems()
    {
        $config = app('config')->get('admin.pages', []);
        $this->items = apply_filters('lighthouse_admin_page_registry', $config);
    }

}