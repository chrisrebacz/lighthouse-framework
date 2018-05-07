<?php 
if (!current_user_can('upload_files')) {
    wp_die('You do not have permission to upload files.');
}
global $wpdb;
$table_name = $wpdb->prefix."posts";

$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = " . (int) $_GET["attachment_id"];

list($current_filename, $current_filetype) = $wpdb->get_row($sql, ARRAY_N);

$current_filename = substr($current_filename, (strrpos($current_filename, "/") + 1));
?>
<div class="wrap">
    <h1><?php echo "Replace Media Upload"; ?></h1>

    <?php  
    $url = admin_url( "upload.php?page=enable-media-replace/enable-media-replace.php&noheader=true&action=media_replace_upload&attachment_id=" . (int) $_GET["attachment_id"]);
    $action = "media_replace_upload";
    $formurl = wp_nonce_url( $url, $action );
    if (FORCE_SSL_ADMIN) {
        $formurl = str_replace("http:", "https:", $formurl);
    }
    ?>
    <form enctype="multipart/form-data" method="post" action="<?php echo $formurl; ?>">
        <?php #wp_nonce_field('enable-media-replace'); ?>
        <input type="hidden" name="ID" value="<?php echo (int) $_GET["attachment_id"]; ?>">
        <div id="message" class="updated notice notice-success is-dismissible"><p><?php printf( __('NOTE: You are about to replace the media file "%s". There is no undo. Think about it!', "enable-media-replace"), $current_filename ); ?></p></div>

        <p><?php echo __("Choose a file to upload from your computer", "enable-media-replace"); ?></p>

        <input type="file" name="userfile" />
        <p class="howto"><?php printf( __("Note: You are required to upload a file of the same type (%s) as the one you are replacing. The name of the attachment will stay the same (%s) no matter what the file you upload is called.", "enable-media-replace"), $current_filetype, $current_filename ); ?></p>
        <input type="hidden" name="replace_type" value="replace" />
        <input type="submit" class="button" value="<?php echo __("Upload", "enable-media-replace"); ?>" /> <a href="#" onclick="history.back();"><?php echo __("Cancel", "enable-media-replace"); ?></a>
    </form>
</div>
