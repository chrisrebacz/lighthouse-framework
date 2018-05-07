<?php
namespace Lighthouse\Routing\Api;

class AcfOptionController extends AcfController
{
    public function __construct($type)
    {
        parent::__construct( $type );
        $this->rest_base = 'options';
    }

    public function register_routes()
    {
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/?(?P<field>[\w\-\_]+)?', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
            ),
        ) );
    }
}