<?php
namespace Lighthouse\Routing\Api;

abstract class BaseController extends \WP_REST_Controller 
{
    
    protected $base = '';

    public function __construct($namespace = null)
    {
        $this->rest_base = app('config')->get('api.namespace').'/'.app('config')->get('api.version');
        if ($namespace === null) {
            $namespace = $this->setNamespace();
        }
        
        $this->namespace = $namespace;
    }


    /**
     * Set the namespace as used by the API
     * @return string
     */
    abstract public function setNamespace();


    /**
     * Register routes within the API
     * @return void
     */
    abstract public function registerRoutes();

    /**
     * Hook into the WP Eventing System
     * @return void
     */
    public function run()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Check whether user is able to view comments
     * @param  \WP_REST_Request $request 
     * @return boolean      
     */
    public function userCanRead($request)
    {
        $allowed = current_user_can('edit_posts');
        $allowed = apply_filters('lh_api_allow_read_'.$this->base, $allowed, $request);
        return (bool) $allowed;
    }

    /**
     * Check whether user is able to create comments
     * @param  \WP_REST_Request $request 
     * @return boolean      
     */
    public function userCanCreate($request)
    {
        $allowed = current_user_can('edit_posts');
        $allowed = apply_filters('lh_api_allow_create_'.$this->base, $allowed, $request);
        return (bool) $allowed;
    }
}