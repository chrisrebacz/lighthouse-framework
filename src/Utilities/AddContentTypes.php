<?php
namespace Lighthouse\Utilities;

use Lighthouse\Registries\ContentRegistry;

class AddContentTypes extends Utility 
{
    public function run()
    {
        $this->registerContentTypes();
        flush_rewrite_rules();
    }

    public function registerContentTypes()
    {
        $configFile = app('path.bootstrap').'/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $posttypes = isset($config['posttypes']) ? $config['posttypes'] : [];
            $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : [];
            $registry = new ContentRegistry($posttypes, $taxonomies);
            $registry->register();
        }
    }
}