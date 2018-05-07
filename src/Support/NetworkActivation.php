<?php 
namespace Lighthouse\Support;

class NetworkActivation
{
    const AJAX_KEY = 'lighthouse';

    public static function init()
    {
        add_action('activated_plugin', [__class__, 'updateQueue'], 10, 2);
        add_action('deactivated_plugin', [__class__, 'updateQueue'], 10, 2);

        add_action('network_admin_notices', [__class__, 'adminNotice']);

        add_action('wp_ajax_' . self::AJAX_KEY, [__class__, 'ajaxResponse']);

        add_action('wpmu_new_blog', [__class__, 'setup']);
    }




    public static function updateQueue($plugin, $network_wide = null)
    {
        if (! is_multisite()) {
            return;            
        }

        if (! app('config')->get('app.network.activate_on_all_sites', false)) {
            return;
        }
        
        $currentFilter = explode('_', current_filter(), 2); //activated_plugin || deactivated_plugin
        $action = str_replace('activated', 'activate', $currentFilter[0]);
        $queue = get_site_option("network_{$action}_queue", array());
        $queue[$plugin] = (has_filter($action . '_' . $plugin) || has_filter($action . '_plugin'));

        update_site_option("network_{$action}_queue", $queue);        
    }

    public static function adminNotice()
    {
        //
    }

    public static function ajaxResponse()
    {
        //
    }

    public static function setup()
    {
        //
    }

}
