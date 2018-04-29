<?php 
namespace Lighthouse\Components;

class Taxonomy
{
    /**
     * The name of the taxonomy
     * @var string
     */
    public $name;

    /**
     * The singular label
     * @var string
     */
    public $singular;

    /**
     * The plural label
     * @var string
     */
    public $plural;

    /**
     * The slug
     * @var string
     */
    public $slug;

    /**
     * Config options passed in to the object
     * @var array
     */
    public $config = [];

    /**
     * The options for the taxonomy
     * @var array
     */
    public $options = [];

    /**
     * Post types associated with the taxonomy
     * @var array
     */
    public $posttypes = ['post'];

    /**
     * The textdomain for translation
     * @var string
     */
    public $textdomain = 'att';

    /**
     * Create the taxonomy object
     * @param mixed $config 
     */
    public function __construct($config)
    {
        $this->config = $config;
        $options = isset($config['options']) ? $config['options'] : [];
        $types = isset($config['types']) ? $config['types'] : ['post'];
        $this->name = $config['name'];
        $this->setNames($config['labels']);
        $this->setOptions($options);
        $this->setPostTypes($types);
    }

    /**
     * Actually registers the taxonomy within WordPress
     * @return void 
     */
    public function register()
    {
        if (! taxonomy_exists($this->name)) {
            register_taxonomy($this->name, $this->posttypes, $this->options);
        }
    }

    /**
     * Set the required names for the taxonomy
     * @param mixed $names an array/string of taxonomy names
     */
    public function setNames($labels)
    {
        if (!is_array($labels)) {
            $labels = ['name' => $labels];
        }


        $required = ['singular','plural','slug'];

        foreach ($required as $key) {
            // if the required item has not been explicity
            // set, let's generate it ourselves
            if (!isset($labels[$key])) {
                // if it is the singular/plural make the post type name human friendly
                if ($key === 'singular' || $key === 'plural') {
                    $name = ucwords(strtolower(str_replace('-', ' ', str_replace('_', ' ', $labels['name']))));
                } elseif ($key === 'slug') {
                    $name = strtolower(str_replace([' ', '_'], '-', $labels['name']));
                }
                if ($key === 'plural' || $key === 'slug') {
                    $name = str_plural($name);
                }
            // otherwise use the name passed
            } else {
                $name = $labels[$key];
            }

            // set the name
            $this->$key = $name;
        }
    }

    /**
     * Set the taxonomy registration options based on 
     * defaults and any submited by the user
     * @param  array $options
     * @return void
     */
    public function setOptions($options)
    {
        $defaults = [
            'labels' => $this->getLabels(),
            'hierarchical' => true,
            'rewrite' => [
                'slug' => $this->slug
            ],
        ];
        // merge default options with user submitted options
        $this->options = array_replace_recursive($defaults, $options);
    }

    /**
     * Identify the post types to which this taxonomy should be associated.
     * @return  void
     */
    public function setPostTypes()
    {
        $assignable = [];
        if (isset($this->config['types']) && $this->config['types'] !== 'shared') {
            return $this->posttypes = $this->config['types'];
        }
        $types = app('config')->get('posttypes', []);
        foreach ($types as $type) {
            if (isset($type['meta']['taxonomy']) && $type['meta']['taxonomy']) {
                if (! is_array($type['name'])) {
                    $assignable[] = $type['name'];
                } else {
                    $assignable[] = $type['name']['name'];    
                }
            }
        }
        return $this->posttypes = $assignable;
    }

    /**
     * Dynamically generate the labels used within the admin panel
     * for the taxonomy based on the singular and plural forms of
     * the taxonomy name. 
     * @return array
     */
    public function getLabels()
    {
        return [
            'name' => sprintf(__('%s', $this->textdomain), $this->plural),
            'singular_name' => sprintf(__('%s', $this->textdomain), $this->singular),
            'menu_name' => sprintf(__('%s', $this->textdomain), $this->plural),
            'all_items' => sprintf(__('All %s', $this->textdomain), $this->plural),
            'edit_item' => sprintf(__('Edit %s', $this->textdomain), $this->singular),
            'view_item' => sprintf(__('View %s', $this->textdomain), $this->singular),
            'update_item' => sprintf(__('Update %s', $this->textdomain), $this->singular),
            'add_new_item' => sprintf(__('Add New %s', $this->textdomain), $this->singular),
            'new_item_name' => sprintf(__('New %s Name', $this->textdomain), $this->singular),
            'parent_item' => sprintf(__('Parent %s', $this->textdomain), $this->plural),
            'parent_item_colon' => sprintf(__('Parent %s:', $this->textdomain), $this->plural),
            'search_items' => sprintf(__('Search %s', $this->textdomain), $this->plural),
            'popular_items' => sprintf(__('Popular %s', $this->textdomain), $this->plural),
            'separate_items_with_commas' => sprintf(__('Seperate %s with commas', $this->textdomain), $this->plural),
            'add_or_remove_items' => sprintf(__('Add or remove %s', $this->textdomain), $this->plural),
            'choose_from_most_used' => sprintf(__('Choose from most used %s', $this->textdomain), $this->plural),
            'not_found' => sprintf(__('No %s found', $this->textdomain), $this->plural),
        ];          
    } 

    /**
     * Set the textdomain for translation
     * @param  string $textdomain the textdomain
     */
    public function textdomain($textdomain)
    {
        $this->textdomain = $textdomain;
    } 
}