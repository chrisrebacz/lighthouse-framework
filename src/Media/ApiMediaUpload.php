<?php
namespace Lighthouse\Media;

use Lighthouse\Routing\Api\BaseController;

class ApiMediaUpload extends BaseController 
{

    protected $base = 'media';

    /**
     * Set the namespace as used by the API
     * @return string
     */
    public function setNamespace()
    {
        return '';
    }

    /**
     * Register routes within the API
     * @return void
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/media',
            [
                array(
                    'methods'               => \WP_REST_Server::CREATABLE,
                    'permission_callback'   => [$this, 'userCanCreate'],
                    'callback'              => [$this, 'store'],
                )
            ]
        );
    }

    public function store()
    {
        //
    }
}