<?php
namespace Lighthouse\Components;

abstract class AdminPage 
{
    /**
     * The slug (id) of the admin page
     * @var string
     */
    protected $slug;

    /**
     * The title of the admin page and how it
     * appears in the menu panel.
     * @var string
     */
    protected $title;

    /**
     * If the page is also the page group, we
     * rename it here so that it shows up 
     * properly in the submenu.
     * @var string
     */
    protected $rename;  

    /**
     * The WP id of the page (i.e. current screen). This 
     * is what we want to match any conditional methods
     * against.
     * @var string
     */
    protected $hook;

    /**
     * User capability that is required to view this page.
     * @var string
     */
    protected $cap = 'manage_options';

    /**
     * The menu icon for a top level page
     * @var string
     */
    protected $icon = 'dashicons-book';

    /**
     * A description of the admin page's purpose
     * @var string
     */
    protected $description = '';

    /**
     * Where in the WP menu the top level admin page
     * should be located
     * @var integer
     */
    protected $position;

    /**
     * If adding a wp-page or a sub-page, we need to
     * associate the parent page slug here. 
     * @var string|null
     */
    protected $parent = null;

    /**
     * Within the DLH admin panel, identify the parent
     * menu where this page should be assigned.
     * @var array
     */
    protected $pageMenu = [
        'parent' => '',
        'position' => 'bottom'
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
    }

    /**
     * Add a page to the Admin Panel
     */
    public function register()
    {
        $menu = is_null($this->rename) ? $this->menu : $this->rename;
        $this->hook = add_submenu_page(
            $this->parent, 
            $this->title,
            $menu, 
            $this->cap, 
            $this->slug, 
            [$this, 'render']
        );

        $this->addActions();
    }


    /**
     * Add actions to WP based on the specific admin page
     */
    public function addActions()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action("load-{$this->hook}", [$this, 'load']);
        add_action("admin_print_styles-{$this->hook}", [$this, 'printStyles']);
        add_action("admin_print_scripts-{$this->hook}", [$this, 'printScripts']);
        add_action("admin_head-{$this->hook}", [$this, 'adminHead']);
        add_action("admin_footer-{$this->hook}", [$this, 'adminFooter']);
    }

    /**
     * Callback for when the admin page is registered in WP to render the
     * page view.
     * Should be overridden in child class
     * @return mixed
     */
    public function render()
    {
        $this->setPageUrl();
        $context = $this->setContext();
        return view($this->view, $context);
    }

    /**
     * Identify the url for the admin page and 
     * add it to the page menu.  
     */
    public function setPageUrl()
    {
        $this->pageMenu['url'] = esc_url(add_query_arg('page', $this->slug, admin_url('admin.php')));
    }

    /**
     * Callback for admin_enqueue_scripts to load
     * scripts and styles for the page  
     * Should be overridden in child class
     * @param  string $hook 
     * @return void       
     */
    public function enqueue($hook)
    {
        // if ($hook !== $this->hook) {
        //     return;
        // }
    }

    /**
     * Callback for the load-menu-slug action
     * Should be overridden in child class
     * @return void 
     */
    public function load(){}

    /**
     * Callback for page specific admin_print_styles
     * action; should be overridden in child class
     * @return void 
     */
    public function printStyles() {}

    /**
     * Callback for page specific admin_print_scripts
     * action; should be overridden in child class
     * @return void 
     */
    public function printScripts() {}

    /**
     * Callback for page specific admin_head
     * action; should be overridden in child class
     * @return void 
     */
    public function adminHead() {}

    /**
     * Callback for page specific admin_footer
     * action; should be overridden in child class
     * @return void 
     */
    public function adminFooter() {}

    /**
     * Get the page parent
     * @return string 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the page parent
     * @param string $parent 
     */
    public function setParent($parent = null)
    {
        if ($parent == null) {
            $parent = 'youknow-admin';
        }   
        $this->parent = $parent;
    }


    /**
     * Get the page slug
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set the page slug
     * @param string $slug 
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get the page hook 
     * @return string 
     */
    public function getHook()
    {
        return $this->hook;
    }

    /**
     * Set the page hook
     * @param string $hook 
     */
    public function setHook($hook)
    {
        $this->hook = $hook;
    }

    /**
     * Get the page title 
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the page title
     * @param string $title 
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get the page icon 
     * @return string 
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set the page icon
     * @param string $icon 
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Get the page position 
     * @return string 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set the page position
     * @param string $position 
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Get the page capability
     * @return string 
     */
    public function getCap()
    {
        return $this->cap;
    }

    /**
     * Set the page capability
     * @param string $cap 
     */
    public function setCap($cap)
    {
        $this->cap = $cap;
    }

    /**
     * Get the page description 
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the page description
     * @param string $description 
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get the internal page menu  
     * @return array 
     */
    public function getPageMenu()
    {
        return $this->pageMenu;
    }

    /**
     * Set the internal page menu
     * @param array $pageMenu 
     */
    public function setPageMenu($pageMenu)
    {
        $this->pageMenu = $pageMenu;
    }

    /**
     * Get the page menu label 
     * @return array 
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Set the page menu label
     * @param array $menu 
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;
    }

    /**
     * Whether we should enqueue scripts on this page
     * @param  string $hook 
     * @return boolean 
     */
    public function shouldEnqueue($hook)
    {
        if ($hook == $this->hook) {
            return true;
        }
        if (str_contains($hook, $this->slug)) {
            return true;
        }

        return false;
    }
}