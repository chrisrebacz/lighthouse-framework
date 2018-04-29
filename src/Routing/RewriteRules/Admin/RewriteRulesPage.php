<?php
namespace Lighthouse\Routing\RewriteRules\Admin;

use Lighthouse\Components\AdminPage;

class RewriteRulesPage extends AdminPage 
{
    /**
     * Slug for the admin page
     * @var string
     */
    protected $slug = 'lh_rewrite_rules';

    /**
     * The title of the admin page
     * @var string
     */
    protected $title = 'Inspect Rewrite Rules';

    /**
     * Label as displayed in the menu.
     * @var string
     */
    protected $menu = 'Rewrite Rules';

    /**
     * The parent menu item's slug
     * @var string
     */
    protected $parent = 'lh-training-admin';
    
    /**
     * Description of the admin page purpose
     * @var string
     */
    protected $description = 'Review the rewrite rules associated with Lighthouse';

    /**
     * Page menu details
     * @var array
     */
    protected $pageMenu = [
        'parent' => 'Admin',
        'name'   => 'Rewrite Rules',
        'url' => ''
    ];

    /**
     * Instance of the RewriteRulesAdmin
     * @var Lighthouse\Support\RewriteRules\RewriteRulesAdmin
     */
    protected $admin;

    /**
     * Blade view to render
     * @var string
     */
    protected $view = 'admin.rewriterules';

    /**
     * Scripts & Styles to enqueue
     * @param  string $hook
     * @return void
     */
    public function enqueue($hook)
    {
        if ($hook !== $this->hook) {
            return;
        }
        // wp_enqueue_style('font-awesome', LH_ASSET_URL.'css/font-awesome.min.css');
        // wp_enqueue_style('lighthouse-admin-css', LH_ASSET_URL.'css/admin.css');
        // wp_enqueue_script('lighthouse-admin-js', LH_ASSET_URL.'js/base.admin.js', ['jquery'], LH_VERSION, true);

        // wp_localize_script('lighthouse-admin-js', 'LighthouseAdmin', [
        //     'site_url' => get_site_url(),
        // ]);
    }

    public function setContext()
    {
        $admin = RewriteRulesAdmin::getInstance();

        if (! class_exists('WP_List_Table')) {
            require_once ABSPATH.'/wp-admin/includes/class-wp-list-table.php';
        }
        $listTable = new RewriteRulesListTable();
        $listTable->prepare_items();

        return [
            'title' => 'Rewrite Rules',
            'description' => $this->description,
            'rules' => $admin->getRules(),
            'sources' => $admin->sources,
            'listTable' => $listTable
        ];
    }
}