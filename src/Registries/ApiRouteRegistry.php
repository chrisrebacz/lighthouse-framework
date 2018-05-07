<?php 
namespace Lighthouse\Registries;

use Lighthouse\Routing\Api\AcfController;
use Lighthouse\Routing\Api\AcfTermController;
use Lighthouse\Routing\Api\AcfOptionController;
use Lighthouse\Routing\Api\AcfAttachmentController;

/*
|----------------------------------------------------------------
| Route Registry
|----------------------------------------------------------------
| This class helps register all endpoints that are needed within
| the Lighthouse API
 */

class ApiRouteRegistry
{
    /**
     * The array of routes to be registered in
     * the WP API for use within Lighthouse.
     * @var array
     */
    protected $routes;

    /**
     * The directory we want to check for controllers
     * related to the API routing. 
     * @var string;
     */
    protected $controllerDir;  

    /**
     * The main lighthouse api namespace
     * @var string
     */
    protected $namespace;

    /**
     * Instantiate the registry and populate the 
     * routes that need to be registered.
     * @param array $routes
     * @return \Lighthouse\Registries\RouteRegistry
     */
    public function __construct($name, $version, $dir)
    {
        $this->namespace = $name . '/' . $version;
        $this->controllerDir = $dir;
    }

    /**
     * Hook into WordPress eventing system
     * @return void
     */
    public function run()
    {
        $routeFile = app('path.bootstrap') . '/api.php';
        if (file_exists($routeFile)) {
            $router = $this;
            $routes = require $routeFile;
            add_action('rest_api_init', [$this, 'register']);
        }

        if (class_exists('acf')) {
            $this->createAcfRoutes();
        }
    }

    public function register()
    {
        $routes = apply_filters('lh_api_routes', $this->routes);
        foreach ($routes as $route) {
            register_rest_route(
                $this->namespace,
                $route[0],
                $route[1]
            );
        }
    }

    public function add($uri, $args)
    {
        if (!is_array($args) || empty($args)) {
            throw new \Exception("required endpoint data is not available", 1);
        }
        $endpoints = [];
        foreach ($args as $action => $uses) {
            $endpoint = [];
            if (!is_array($uses)) {
                $uses = explode('@a', $uses);
            }
            $controller = $uses[0];
            if (starts_with('App', $uses[0])) {
                $controller = $uses[0];
            } else {
                $controller = $this->controllerDir . $uses[0];
            }
            $method = $uses[1];

            $endpoint['methods'] = $this->getMethod($action);
            $permission = $this->allow($action);
            $endpoint['permission_callback'] = [$controller, $permission];
            $endpoint['callback'] = [$controller, $method];
            $endpoints[] = $endpoint;
        }

        $this->routes[] = [$uri, $endpoints];
    }

    protected function allow($method)
    {
        switch ($method) {
            case 'post':
                return 'canCreate';
                break;
            case 'put':
                return 'canUpdate';
                break;
            case 'patch':
                return 'canUpdate';
                break;
            case 'delete':
                return 'canDelete';
                break;
            default:
                return 'canRead';
                break;
        }
    }

    protected function getMethod($method)
    {
        switch ($method) {
            case 'post':
                return \WP_REST_Server::CREATABLE;
                break;
            case 'put':
                return \WP_REST_Server::EDITABLE;
                break;
            case 'patch':
                return \WP_REST_Server::EDITABLE;
                break;
            case 'delete':
                return \WP_REST_Server::DELETABLE;
                break;
            default:
                return \WP_REST_Server::READABLE;
                break;
        }
    }

    protected function idParam($name = 'id')
    {
        return '(?P<' . $name . '>\d+)';
    }

    protected function slugParam($name = 'slug')
    {
        return '(?P<' . $name . '>\S+)';
    }

    public function createAcfRoutes()
    {
        $default = ['user', 'comment', 'term', 'option'];
        $types = get_post_types(['show_in_rest' => true]);

        if ($types && isset($types['attachment'])) {
            unset($types['attachment']);
            $default[] = 'media';
        }

        $types = apply_filters('acf/rest_api/types', array_merge($types, array_combine($default, $default)));

        if (is_array($types) && count($types) > 0) {
            foreach ($types as $type) {
                if ('term' == $type) {
                    $controller = new AcfTermController($type);
                } elseif ('media' == $type) {
                    $controller = new AcfAttachmentController($type);
                } elseif ('option' == $type) {
                    $controller = new AcfOptionController($type);
                } else {
                    $controller = new AcfController($type);
                }

                $controller->register_routes();
                $controller->register_hooks();
            }
        }
    }
}