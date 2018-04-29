<?php
namespace Lighthouse\Registries;

use Lighthouse\Components\PostType;
use Lighthouse\Components\Taxonomy;

class ContentRegistry 
{
    /**
     * List of post types to register
     * @var array
     */
    protected $types = [];

    /**
     * Instantiate the Content Registry
     * @param array $types 
     * @param array $taxonomies
     * @return \Lighthouse\Registries\ContentRegistry
     */
    public function __construct($types = null, $taxonomies = null)
    {
        $this->types = is_null($types) ? 
            $this->getTypesFromConfig() : $types;

        $this->taxonomies = is_null($taxonomies) ? 
            $this->getTaxonomiesFromConfig() : $taxonomies;
    }

    /**
     * Hook into the WP eventing system
     * @return void
     */
    public function run()
    {
        add_action('init', [$this, 'register']);
        add_action('init', [$this, 'renameCategoryTaxonomy']);
    }

    /**
     * Run through all of the post types and taxonomies to register
     * @return void
     */
    public function register()
    {
        foreach($this->types as $type) {
            $t = new PostType($type);
            $t->register();
        }
        foreach ($this->taxonomies as $taxonomy) {
            $tax = new Taxonomy($taxonomy);
            $tax->register();
        }
    }

    /**
     * Rename the category taxonomy to better reflect
     * the type of categorization we want to do.
     * @return void
     */
    public function renameCategoryTaxonomy()
    {
        
    }

    /**
     * Get the list of post types to register from the 
     * config.php file.
     * @return array
     */
    public function getTypesFromConfig()
    {
        return app('config')->get('posttypes', []);
    }

    /**
     * Get the list of taxonomies to register from the 
     * config.php file.
     * @return array
     */
    public function getTaxonomiesFromConfig()
    {
        return app('config')->get('taxonomies', []);
    }
}