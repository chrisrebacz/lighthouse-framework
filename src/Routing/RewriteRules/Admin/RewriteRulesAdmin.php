<?php
namespace Lighthouse\Routing\RewriteRules\Admin;

class RewriteRulesAdmin
{
    /**
     * Admin Page slug
     * @var string
     */
    public $slug = 'lh_rewrite_rules';

    /**
     * List of sources for the rewrite rules that can used for filters
     * @var array
     */
    public $sources = [];

    /**
     * Singleton instance of the Admin class
     * @var Lighthouse\Routing\RewriteRules\Admin\RewriteRulesAdmin
     */
    public static $instance;

    public $flushing_enabled = true;

    /**
     * Instantiate the Inspector
     */
    public function __construct()
    {
        add_action('init', [$this, 'register']);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register()
    {
        if (! is_admin()) {
            return;
        }

        if (isset($_GET['page']) && $_GET['page'] == $this->slug) {
            if (isset($_GET['action'])) {
                $action = $_GET['action'];
                if ($action == 'download-rules') {
                    add_action('admin_init', [$this, 'downloadRules']);
                } elseif ($action == 'flush-rules') {
                    add_action('admin_init', [$this, 'flushRules']);
                }
            } elseif (isset($_GET['message']) && $_GET['message'] == 'flush-success') {
                add_action('admin_notices', [$this, 'displayAdminNotice']);
            }
        }
    }

    public function getRules()
    {
        global $wp_rewrite;
        $arrRules = [];
        $rewriteRules = get_option('rewrite_rules');
        if (! $rewriteRules) {
            $rewriteRules = [];
        }
        $rulesBySource = [
            'post' => $wp_rewrite->generate_rewrite_rules($wp_rewrite->permalink_structure, EP_PERMALINK),
            'date' =>  $wp_rewrite->generate_rewrite_rules($wp_rewrite->get_date_permastruct(), EP_DATE),
            'root' => $wp_rewrite->generate_rewrite_rules($wp_rewrite->root . '/', EP_ROOT),
            'comments' => $wp_rewrite->generate_rewrite_rules($wp_rewrite->root.$wp_rewrite->comments_base, EP_COMMENTS, true, true, true, false),
            'author' => $wp_rewrite->generate_rewrite_rules($wp_rewrite->get_author_permastruct(), EP_AUTHORS),
            'page' => $wp_rewrite->page_rewrite_rules(),
        ];

        foreach ($wp_rewrite->extra_permastructs as $name => $permastruct) {
            if (is_array($permastruct)) {
                $rulesBySource[$name] = $wp_rewrite->generate_rewrite_rules($permastruct['struct'], $permastruct['ep_mask'], $permastruct['paged'], $permastruct['feed'], $permastruct['forcomments'], $permastruct['walk_dirs'], $permastruct['endpoints']);
            } else {
                $rulesBySource[$name] = $wp_rewrite->generate_rewrite_rules($permastruct, EP_NONE);
            }
        }

        foreach ($rulesBySource as $source => $rules) {
            $rulesBySource[$source] = apply_filters($source.'_rewrite_rules', $rules);
            if ('post_tag' == $source) {
                $rulesBySource[$source] = apply_filters('tag_rewrite_rules', $rules);
            }
        }
        foreach ($rewriteRules as $rule => $rewrite) {
            $arrRules[$rule]['rewrite'] = $rewrite;
            foreach ($rulesBySource as $source => $rules) {
                if (array_key_exists($rule, $rules)) {
                    $arrRules[$rule]['source'] = $source;
                }
            }
            if (! isset($arrRules[$rule]['source'])) {
                $arrRules[$rule]['source'] = apply_filters('lh_rewrite_rules_source', 'other', $rule, $rewrite);
            }
        }

        $maybeMissing = $wp_rewrite->rewrite_rules();
        $missingRules = [];
        $arrRules = array_reverse($arrRules, true);
        foreach ($maybeMissing as $rule => $rewrite) {
            if (! array_key_exists($rule, $arrRules)) {
                $arrRules[$rule] = [
                    'rewrite' => $rewrite,
                    'source' => 'missing',
                ];
            }
        }
        $arrRules = array_reverse($arrRules, true);
        $sources = ['all'];
        foreach ($arrRules as $rule => $data) {
            $sources[] = $data['source'];
        }

        $this->sources = array_unique($sources);
        if (! empty($_GET['s'])) {
            $matchPath = parse_url(esc_url($_GET['s'] ), PHP_URL_PATH);
            $wpSiteSubDir = parse_url(home_url(), PHP_URL_PATH);
            if (! empty($wpSiteSubDir)) {
                $matchPath = str_replace($wpSiteSubDir, '', $matchPath);
            }
            $matchPath = ltrim( $matchPath, '/' );
        }

        $shouldFilterBySource = ! empty($_GET['source']) && 'all' !== $_GET['source'] && in_array($_GET['source'], $this->sources);

        foreach ($arrRules as $rule => $data) {
            if (! empty($matchPath) && ! preg_match("!^$rule!", $matchPath)) {
                unset($arrRules[$rule]);
            } elseif ($shouldFilterBySource && $data['source'] != $_GET['source']) {
                unset($arrRules[$rule]);
            }
        }

        return $arrRules;
    }

    /**
     * Download the rules into a text file
     * @return void 
     */
    public function downloadRules()
    {
        check_admin_referer('download-rules');
        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have permissions to view this page'));
        }
        $theme_name = sanitize_key(get_option('stylesheet'));
        $filename = date('Ymd').'.'.$theme_name.'rewriterules.txt';
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $rules = $this->getRules();
        $export = [];
        foreach ($rules as $rule => $data) {
            $export[$rule] = $data['rewrite'];
        }
        echo var_export($export, true);
        exit;
    }

    /**
     * Flush rewrite rules from the admin page.
     * @return void
     */
    public function flushRules()
    {
        global $plugin_page;
        check_admin_referer('flush-rules');
        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have permissions to perform this action'));
        }
        flush_rewrite_rules(false);
        $args = [
            'message' => 'flush-success',
        ];
        wp_safe_redirect(add_query_arg($args, menu_page_url($plugin_page, false)));
        exit;
    }

    /**
     * Show a message when the rewrite rules are successfully flushed.
     * @return void
     */
    public function displayAdminNotice()
    {
        echo '<div class="message updated"><p>' . __('Rewrite rules flushed.', 'att').'</p></div>';
    }
}