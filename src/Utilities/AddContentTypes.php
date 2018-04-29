<?php
namespace Lighthouse\Utilities;

use Lighthouse\Registries\ContentRegistry;

class AddContentTypes extends Utility 
{
    public function run()
    {
        $this->registerContentTypes();
        if (isset($this->config['acf']))  {
            $this->registerAcfFieldGroups();
        }
        flush_rewrite_rules();
    }

    public function registerContentTypes()
    {
        $configFile = $this->baseDir.'/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $posttypes = isset($config['posttypes']) ? $config['posttypes'] : [];
            $taxonomies = isset($config['taxonomies']) ? $config['taxonomies'] : [];
            $registry = new ContentRegistry($posttypes, $taxonomies);
            $registry->register();
        }
    }

    public function registerAcfFieldGroups()
    {
        $file = $this->config['acf'];
        if (file_exists($file)) {
            require $file;
        }
    }
}