<?php
namespace Lighthouse\Media;

class MediaLibraryAuthorFilter 
{
    /**
     * Taxonomies which should be ignored when identifying the list
     * of available taxonomies within the media library
     * @var array
     */
    protected $ignored = [
        'nav_menu',
        'link_category',
        'post_format'
    ];

    /**
     * Hook into the WP Eventing system
     * @return void
     */
    public function run()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_filter('posts_join', [$this, 'join']);
        add_filter('posts_groupby', [$this, 'groupby']);
        add_action('restrict_manage_posts', [$this, 'setDropdowns']);
    }

    /**
     * Add taxonomy and term dropdown menu to upload.php
     */
    public function setDropdowns()
    {
        if (! $this->inMediaLibrary()) {
            return;
        }
        $author   = filter_input(INPUT_GET, 'author', FILTER_SANITIZE_STRING);
        $selected = (int)$author > 0 ? : '-1';
        $mediaRoles = lighthouse_get_admin_panel_roles();
        $args = array(
            'show_option_none'   => 'All Authors',
            'name'               => 'author',
            'selected'           => $selected,
            'role__in'           => $mediaRoles,
        );
        wp_dropdown_users( $args );
    }

    public function groupby($groupby)
    {
        if (! $this->inMediaLibrary()) {
            return $groupby;
        }
        $selectedTaxonomy = $this->getSelectedTaxonomy();
        if (! $selectedTaxonomy) {
            return $groupby;
        }
        global $wpdb;
        $groupby = $wpdb->posts.".ID";
        return $groupby;
    }

    /**
     * Enqueue scripts that should be available when on the media library screen
     * @param  string $hook 
     * @return void   
     */
    public function enqueueAdminScripts($hook)
    {
        if ('upload.php' != $hook) {
            return;
        }
        wp_enqueue_script('youknow_media_library_js', LH_ASSET_URL.'/js/media-library.js', ['jquery']);
    }

    /**
     * Filter the join used within the sql query for identifying available
     * media attachments
     * @param  string $sql 
     * @return string    
     */
    public function join($sql) {

        // If we're not on the upload.php screen, return.
        if ( ! $this->inMediaLibrary() ) {
            return $sql;
        }

        global $wpdb;

        $selectedTaxonomy = $this->getSelectedTaxonomy();
        $selectedTermId  = $this->getSelectedTermId();

        if ( ! $selectedTaxonomy ) {
            return $sql;
        }

        $taxonomy_sql = $wpdb->prepare( " AND $wpdb->term_taxonomy.taxonomy = %s ", $selectedTaxonomy );
        $term_sql     = ( $selectedTermId ) ? $wpdb->prepare( " AND $wpdb->terms.term_id = %d ", $selectedTermId ) : " ";

        $sql .= " ";
        $sql .= "INNER JOIN $wpdb->term_relationships ON ( $wpdb->posts.post_parent = $wpdb->term_relationships.object_id ) ";
        $sql .= "INNER JOIN $wpdb->term_taxonomy ON ( $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id ) ";
        $sql .= $taxonomy_sql;
        $sql .= "INNER JOIN $wpdb->terms ON ( $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id ) ";
        $sql .= $term_sql;

        return $sql;
    }


    /*
    |----------------------------------------------------------------
    | HELPER FUNCTIONS
    |----------------------------------------------------------------
    */

    /**
     * Get a list of all taxonomies available based on the current filters
     * in place
     * @return array 
     */
    protected function getTaxonomies()
    {
        global $wpdb;
        $date_sql   = $this->getDateSql();
        $filter_sql = $this->getAttachmentFilterSql();
        $search_sql = $this->getSearchSql();

        $sql = "
            SELECT
                tt.taxonomy AS 'name',
                COUNT( DISTINCT( child.ID ) ) AS 'total'
            FROM $wpdb->posts AS child
                LEFT JOIN $wpdb->posts AS parent ON parent.ID = child.post_parent
                LEFT JOIN $wpdb->term_relationships AS tr ON tr.object_id = parent.ID
                INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE 1 = 1
            $date_sql
            $filter_sql
            $search_sql
            AND child.post_type = 'attachment'
            AND ( child.post_status = 'inherit' OR child.post_status = 'private' )
            GROUP BY tt.taxonomy
            ORDER BY tt.taxonomy ASC
            ";

        $taxonomies = $wpdb->get_results( $sql );

        return $taxonomies;
    }

    /**
     * Get a list of all the terms given the current filters in place and 
     * currently selected taxonomy.
     * @param  string $taxonomy 
     * @return array 
     */
    protected function getTerms($taxonomy)
    {
        global $wpdb;
        $taxonomy_sql = $wpdb->prepare( " AND tt.taxonomy = %s ", $taxonomy );
        $date_sql     = $this->getDateSql();
        $filter_sql   = $this->getAttachmentFilterSql();
        $search_sql   = $this->getSearchSql();

        $sql = "
            SELECT
                tt.taxonomy AS 'taxonomy',
                t.name AS 'name',
                t.term_id AS 'term_id',
                COUNT( DISTINCT( child.ID ) ) AS 'total'
            FROM $wpdb->posts AS child
                LEFT JOIN $wpdb->posts AS parent ON parent.ID = child.post_parent
                LEFT JOIN $wpdb->term_relationships AS tr ON tr.object_id = parent.ID
                INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                    $taxonomy_sql
                LEFT JOIN $wpdb->terms AS t ON t.term_id = tt.term_id
            WHERE 1 = 1
            $date_sql
            $filter_sql
            $search_sql
            AND child.post_type = 'attachment'
            AND ( child.post_status = 'inherit' OR child.post_status = 'private' )
            GROUP BY t.term_id
            ORDER BY t.name ASC
            ";

        $terms = $wpdb->get_results( $sql );

        return $terms;
    }

    /**
     * Return the selected taxonomy or false if no type is selected
     * @return string|boolean
     */
    protected function getSelectedTaxonomy()
    {
        $type = filter_input( INPUT_GET, 'lighthouse_taxonomy', FILTER_SANITIZE_STRING );
        $type = trim($type);
        if (! empty($type)) {
            return $type;
        }
        return false;
    }

    /**
     * Get the id of the selected term or false if the term does not
     * exist
     * @return string|boolean 
     */
    protected function getSelectedTermId()
    {
        $id = filter_input( INPUT_GET, 'lighthouse_term_id', FILTER_SANITIZE_NUMBER_INT );
        $id = intval( trim( $id ) );
        if ( $id > 0 ) {
            return $id;
        }
        return false;
    }

    /**
     * Return selected date in an array format or false if no term is selected.
     * Ex. ['y' => "2016", 'm' => "01"];
     * @return array|boolean
     */
    protected function getSelectedDate()
    {
        $m = filter_input( INPUT_GET, 'm', FILTER_SANITIZE_NUMBER_INT );
        $m = intval( trim( $m ) );
        if ( $m > 0 ) {
            return array( 'y' => substr( $m, 0, 4 ), 'm' => substr( $m, - 2 ) );
        }
        return false;
    }

    /**
     * Return the sql statement when a user wants to filter by date
     * @return string
     */
    protected function getDateSql()
    {
        $selected_date = $this->getSelectedDate();

        $y = esc_sql( $selected_date['y'] );
        $m = esc_sql( $selected_date['m'] );

        return ( $selected_date ) ? " AND YEAR( child.post_date ) = $y AND MONTH( child.post_date ) = $m " : " ";
    }

    /**
     * Modify the query if the user wishes to filter by mime type
     * @return string 
     */
    protected function getAttachmentFilterSql()
    {
        $filter = filter_input(INPUT_GET, 'attachment-filter', FILTER_SANITIZE_ENCODED);

        if ('detached' == $filter) {
            return " AND child.post_parent = 0 ";
        }

        $filter = urldecode($filter);
        $filter = explode(":", $filter);

        if (2 != count($filter)) {
            return "";
        }

        global $wpdb;
        $value = $filter[1] . '/%';

        return $wpdb->prepare( " AND ( child.post_mime_type LIKE %s ) ", $value );
    }

    /**
     * Modify the query with search terms provided by end user
     * @return string
     */
    protected function getSearchSql()
    {
        $s = filter_input( INPUT_GET, 's', FILTER_SANITIZE_ENCODED );
        $s = trim($s);
        if (empty($s)) {
            return " ";
        }

        global $wpdb;
        $s = '%' . $wpdb->esc_like( $s ) . '%';

        return $wpdb->prepare( " AND ( ( child.post_title LIKE %s ) OR ( child.post_content LIKE %s ) ) ", $s, $s );
    }

    /**
     * Return a list of taxonomies ignored during the filter process
     * @return array 
     */
    protected function getIgnoredTaxonomies()
    {
        return apply_filters('lighthouse_ignored_taxonomies', $this->ignored);
    }


    /**
     * Check whether we are within the media library
     * @return boolean 
     */
    protected function inMediaLibrary()
    {
        return 'upload' == basename( $_SERVER["SCRIPT_FILENAME"], '.php' );
    }

}