<?php
namespace Lighthouse\Routing\RewriteRules\Admin;

class RewriteRulesListTable extends \WP_List_Table 
{
    public function __construct()
    {
        $screen = get_current_screen();
        parent::__construct([
            'plural' => 'Rewrite Rules',
        ]);
    }

    /**
     * Load all of the matching rewrite rules into our list table
     */
    function prepare_items() {
        global $rewrite_rules_inspector;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->items = $rewrite_rules_inspector->getRules();
    }

    /**
     * What to print when no items were found
     */
    function no_items() {
        _e( 'No rewrite rules were found.', 'rewrite-rules-inspector' );
    }

    /**
     * Display the navigation for the list table
     */
    function display_tablenav ($which) {
        global $plugin_page, $rewrite_rules_inspector;

        $search = ! empty($_GET['s']) ? esc_url($_GET['s']) : '';

        if ( $which == 'bottom' )
            return false;
        ?>
        <div class="custom-tablenav-top" style="padding-top:5px;padding-bottom:10px;">
        <div style="float:right;">
            <?php
            // Only show the flush button if enabled
            if ( $rewrite_rules_inspector->flushing_enabled ):
            ?>
            <?php
                // Flush the current set of rewrite rules
                $args = array(
                    'action' => 'flush-rules',
                    '_wpnonce' => wp_create_nonce('flush-rules'),
                );
                $flush_url = add_query_arg($args, menu_page_url($plugin_page, false));
            ?>
            <a title="<?php _e('Flush your rewrite rules to regenerate them', 'lh'); ?>" class="button-secondary" href="<?php echo esc_url($flush_url); ?>"><?php _e('Flush Rules', 'lh'); ?></a>
            <?php endif; ?>
            <?php
                // Prepare the link to download a set of rules
                // Link is contingent on the current filter state
                $args = array(
                    'action' => 'download-rules',
                    '_wpnonce' => wp_create_nonce('download-rules'),
                );
                if (isset($_GET['source']) && in_array($_GET['source'], $rewrite_rules_inspector->sources))
                    $args['source'] = sanitize_key($_GET['source']);
                else
                    $args['source'] = 'all';
                $args['s'] = !empty($_GET['s'] ) ? $_GET['s'] : '';

                $download_url = add_query_arg($args, menu_page_url($plugin_page, false));
            ?>
            <a title="<?php _e('Download current list of rules as a .txt file', 'lh'); ?>" class="button-secondary" href="<?php echo esc_url($download_url); ?>"><?php _e('Download', 'lh'); ?></a>
        </div>
        <form method="GET">
            <label for="s"><?php _e('Match URL:', 'lh'); ?></label>
            <input type="text" id="s" name="s" value="<?php echo esc_attr($search); ?>" size="50"/>
            <input type="hidden" id="page" name="page" value="<?php echo esc_attr($plugin_page); ?>" />
            <label for="source"><?php _e('Rule Source:', 'lh'); ?></label>
            <select id="source" name="source">
            <?php
                if (isset($_GET['source'] ) && in_array($_GET['source'], $rewrite_rules_inspector->sources)) {
                    $filter_source = sanitize_key( $_GET['source'] );
                } else {
                    $filter_source = 'all';
                }
                foreach ($rewrite_rules_inspector->sources as $value) {
                    echo '<option value="' . esc_attr( $value ) . '" ';
                    selected( $filter_source, $value );
                    echo '>' . esc_attr( $value ) . '</option>';
                }
            ?>
            </select>
            <?php submit_button(__('Filter', 'lh'), 'primary', null, false ); ?>
            <?php if ($search || ! empty($_GET['source'])): ?>
                <a href="<?php menu_page_url($plugin_page); ?>" class="button-secondary"><?php _e('Reset', 'lh'); ?></a>
            <?php endif; ?>
        </form>
        </div>
        <?php
    }

    /**
     * Define the columns for our list table
     */
    function get_columns() {
        $columns = array(
            'rule'          => __('Rule', 'lh'),
            'rewrite'       => __('Rewrite', 'lh'),
            'source'        => __('Source', 'lh'),
        );
        return $columns;
    }

    /**
     * Display each row of rewrite rule data
     */
    function display_rows() {
        foreach ($this->items as $rewrite_rule => $rewrite_data) {
            $rewrite_data['rule'] = $rewrite_rule;
            $this->single_row($rewrite_data);
        }
    }

    function single_row ($item) 
    {
        $rule = $item['rule'];
        $source = $item['source'];
        $rewrite = $item['rewrite'];
        $class = 'source-' . $source;

        echo "<tr class='$class'>";

        list($columns, $hidden) = $this->get_column_info();
        foreach ($columns as $column_name => $column_display_name) {
            switch ($column_name) {
                case 'rule':
                    echo "<td class='column-rule'><strong>" . esc_html($rule) . "</strong></td>";
                    break;
                case 'rewrite':
                    echo "<td class='column-rewrite'>" . esc_html($rewrite) . "</td>";
                    break;
                case 'source':
                    echo "<td class='column-source'>" . esc_html($source) . "</td>";
                    break;
            }
        }
        
        echo "</tr>";
    }
}