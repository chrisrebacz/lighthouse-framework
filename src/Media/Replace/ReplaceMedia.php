<?php
namespace Lighthouse\Media\Replace;

class ReplaceMedia
{
    public function run()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
        add_filter('attachment_fields_to_edit', [$this, 'enableMediaReplace'], 10, 2);
        add_filter('media_row_actions', [$this, 'addMediaActions'], 10, 2);
        add_action( 'attachment_submitbox_misc_actions', [$this, 'replaceMediaEditScreen'], 91 );

        add_shortcode('file_modified', [$this, 'getFileModifiedDate']);
    }

    public function adminMenu()
    {
        add_submenu_page(NULL, "Replace media", '', 'upload_files', 'enable-media-replace/enable-media-replace', [$this, 'mediaReplaceOptions']);
    }

    public function mediaReplaceOptions()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'media_replace') {
            check_admin_referer('media_replace'); // die if invalid
            if (array_key_exists("attachment_id", $_GET) && (int) $_GET["attachment_id"] > 0) {
                include __DIR__.'/popup.php';
            }
        }
        
        if (isset($_GET['action']) && $_GET['action'] == 'media_replace_upload') {
            check_admin_referer('media_replace_upload'); // die if invalid
            require_once(__DIR__."/upload.php");
        }
    }

    public function enableMediaReplace($form_fields, $post)
    {
        $url = admin_url( "upload.php?page=enable-media-replace/enable-media-replace.php&action=media_replace&attachment_id=" . $post->ID);
        $action = 'media_replace';
        $editurl = wp_nonce_url($url, $action);

        if (FORCE_SSL_ADMIN) {
            $editurl = str_replace('http:', 'https:', $editurl);
        }
        $link = "href=\"$editurl\"";

        $form_fields['enable-media-replace'] = [
            "label" => "Replace Media",
            "input" => "html",
            "html" => "<p><a class='button-secondary'$link"."Upload a new file</a></p>",
            "helps" => "To replace the current file, click the link and upload a replacement.",
        ];

        return $form_fields;
    }

    public function addMediaActions($actions, $post)
    {
        $url = admin_url( "upload.php?page=enable-media-replace/enable-media-replace.php&action=media_replace&attachment_id=" . $post->ID);
        $action = "media_replace";
        $editurl = wp_nonce_url( $url, $action );

        if (FORCE_SSL_ADMIN) {
            $editurl = str_replace("http:", "https:", $editurl);
        }
        $link = "href=\"$editurl\"";
        $newaction['adddata'] = '<a '.$link.' aria-label="Replace asset" rel="permalink">Replace asset</a>';

        return array_merge($actions, $newaction);
    }

    public function getFileModifiedDate($atts)
    {
        $id = 0;
        $format = '';

        extract(shortcode_atts([
            'id' => '',
            'format' => get_option('date_format').' '.get_option('time_format'),
        ], $atts));
        if ($id == '') return false;
        $current_file = get_attached_file($id);
        if (! file_exists($current_file)) return false;

        $filetime = filemtime($current_file);
        if (false !== $filetime) {
            return date($format, $filetime);
        }
        return false;
    }



    public function replaceMediaEditScreen()
    {
        if (! method_exists($this, 'enableMediaReplace')) return;
        global $post;
        $id = $post->ID;
        $shortcode = "[file_modified id=$id]";
        $file_modified_time = do_shortcode($shortcode);
        if (! $file_modified_time) {
            return;
        }
        ?>
        <div class="misc-pub-section curtime">
            <span id="timestamp">Revised <strong><?php echo $file_modified_time; ?></strong></span>
        </div>
        <?php
    }
}