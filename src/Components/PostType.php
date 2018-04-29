<?php
namespace Lighthouse\Components;

use Exception;

class PostType 
{

    /**
     * The post type name. This is a unique identifier WP uses to 
     * register the post type within YouKnow. It should be lowercase,
     * singular, and separated by underscores. 
     * @var string
     */
    public $name;

    /**
     * The human friendly singular version of the name. 
     * It should be singular, title case, and separated with spaces.
     * @var string
     */
    public $singular;

    /**
     * The human friendly plural version of the name. 
     * It should be plural, title case, and separated with spaces.
     * @var string
     */
    public $plural;


    /**
     * The slug is used for urls (permalinks).
     * It should be lowercase, plural, and separated with hyphens.
     * @var string
     */
    public $slug;

    /**
     * The specific options for the post type
     * @var array
     */
    public $options; 


    /**
     * An array of taxonomies to use for filters within the 
     * admin panel. 
     * @var array
     */
    public $filters = [];

    /**
     * The columns object used for managing the post type columns. If 
     * no customized columns are needed, should be set to boolean false.
     * @var  Lighthouse\Components\AdminColumns | boolean
     */
    public $columns = false;

    /**
     * Used for translation/localization efforts
     * @var string
     */
    public $textdomain = 'att';

    /**
     * Instantiate the post type object
     * @param  array $config Metadata from the config file needed to create the post type
     */
    public function __construct($config)
    {   
        if (! isset($config['name'])) {
            throw new Exception('The post type you want to register does not have the required name attribute.');
        }
        $this->name = $config['name'];

        $labels = isset($config['labels']) ? $config['labels'] : [];
        $this->setNames($labels);

        $options = isset($config['options']) ? $config['options'] : [];
        $this->setOptions($options);

        $this->config = $config;
    }

    public function register()
    {
        if (! post_type_exists($this->name)) {
            // dd($this->options);
            register_post_type($this->name, $this->options);
        }
    }


    /**
     * Identify the plural, singular and slug versions of the post type
     * name. 
     * @param array $labels 
     */
    public function setNames($labels)
    {
        if (! is_array($labels)) {
            $labels = [$labels];
        }
        foreach(['singular', 'plural', 'slug'] as $key) {
            if (! isset($labels[$key])) {
                //we will need to create the singular/plural or slug from the name attribute. 
                if ($key === 'singular' || $key === 'plural') {
                    $term = ucwords(strtolower(str_replace(['-', '_'], ' ', $this->name)));
                    if ($key === 'plural') {
                        $term = str_plural($term);
                    }
                } else if ($key === 'slug') {
                    $term = str_plural(strtolower(str_replace([" ", '_'], '-', $this->name)));
                }
            } else {
                $term = $labels[$key];
            }
            $this->$key = $term;
        }
    }

    /**
     * Identify the specific options that should be added to the post type
     * upon registration. If nothing is passed, we will use the basic 
     * defaults identified in the base PostType class.  
     * @param array $options 
     */
    public function setOptions($options)
    {
        $defaults = [
            'labels' => $this->getLabels(),
            'public' => true,
            'taxonomies' => ['category', 'post_tag'],
            'supports' => ['title', 'editor', 'excerpt', 'post-formats', 'page-attributes', 'author'],
            'has_archive' => true,
            'menu_position' => 6,
            'capability_type' => 'post',
            'author' => true,
            'show_in_rest' => true
        ];

        $this->options = array_replace_recursive($defaults, $options);
    }

    /**
     * Generate all of the labels used when the post type data is displayed to
     * users within the admin panel.
     * @return array 
     */
    public function getLabels()
    {
        return [
            'name' => sprintf(__('%s', $this->textdomain), $this->plural),
            'singular_name' => sprintf(__('%s', $this->textdomain), $this->singular),
            'menu_name' => sprintf(__('%s', $this->textdomain), $this->plural),
            'all_items' => sprintf(__('%s', $this->textdomain), $this->plural),
            'add_new' => __('Add New', $this->textdomain),
            'add_new_item' => sprintf(__('Add New %s', $this->textdomain), $this->singular),
            'edit_item' => sprintf(__('Edit %s', $this->textdomain), $this->singular),
            'new_item' => sprintf(__('New %s', $this->textdomain), $this->singular),
            'view_item' => sprintf(__('View %s', $this->textdomain), $this->singular),
            'search_items' => sprintf(__('Search %s', $this->textdomain), $this->plural),
            'not_found' => sprintf(__('No %s found', $this->textdomain), $this->plural),
            'not_found_in_trash' => sprintf(__('No %s found in Trash', $this->textdomain), $this->plural),
            'parent_item_colon' => sprintf(__('Parent %s:', $this->textdomain), $this->singular),
        ];
    }
}