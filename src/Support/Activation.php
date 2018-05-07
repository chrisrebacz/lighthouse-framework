<?php 
namespace Lighthouse\Support;

class Activation
{
    const AJAX_KEY = 'lighthouse';

    /**
     * Base Utilities that we may want to process on activation
     */
    protected static $utilities = [
        'tables' => 'Lighthouse\Utilities\CreateTables',
        'options' => 'Lighthouse\Utilities\PublishOptions',
        'directories' => 'Lighthouse\Utilities\BuildDirectories',
        'roles' => 'Lighthouse\Utilities\CreateUserRolesAndCaps',
    ];


    public static function run() 
    {
        //THIS SHOULD BE OVERWRITTEN IN THE CHILD CLASS.
    }

    public static function processUtilities($config)
    {
        if (isset($config['prefix'])) {
            $prefix = $config['prefix'];
            $baseDir = app('path.uploads') . '/' . $prefix;
            foreach (self::$utilities as $utilKey => $utilClass) {
                if (isset($config[$utilKey])) {
                    $utility = new $utilClass($config[$utilKey], $prefix, $baseDir);
                    $utility->run();
                }
            }
        }
    }

    public static function install($config)
    {
        if (! is_array($config)) {
            return;
        }

        if (is_multisite() && is_plugin_active_for_network(app('path.file'))) {
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                self::processUtilities($config);
                restore_current_blog();
            } 
        } else {
            self::processUtilities($config);
        }
    }
}
