<?php 
if (! current_user_can('upload_files')) {
    wp_die('You do not have permission to upload files.');
}
global $wpdb;
$table_name = $wpdb->prefix."posts";
$postmeta_table_name = $wpdb->prefix."postmeta";

function lighthouse_delete_current_files($current_file)
{
    $current_path = substr($current_file, 0, (strrpos($current_file, "/")));
    if (file_exists($current_file)) {
        clearstatcache();
        if (is_writable($current_file)) {
            unlink($current_file);
        } else {
            printf(__('The file %1$s can not be deleted by the web server, most likely because the permissions on the file are wrong.', "enable-media-replace"), $current_file);
            exit;   
        }
    }

    $suffix = substr($current_file, (strlen($current_file)-4));
    $prefix = substr($current_file, 0, (strlen($current_file)-4));
    $imgAr = array(".png", ".gif", ".jpg");
    if (in_array($suffix, $imgAr)) { 
        $metadata = wp_get_attachment_metadata($_POST["ID"]);
        if (is_array($metadata)) {
            foreach($metadata["sizes"] as $thissize) {
                $thisfile = $thissize["file"];
                $oldfilesAr[] = $thisfile;
                if (strlen($thisfile)) {
                    $thisfile = $current_path.'/'.$thissize["file"];
                    if (file_exists($thisfile)) {
                        unlink($thisfile);
                    }
                }
            }
        }
    }
}

$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = '" . (int) $_POST["ID"] . "'";
list($current_filename, $current_filetype) = $wpdb->get_row($sql, ARRAY_N);

$current_guid = $current_filename;
$current_filename = substr($current_filename, (strrpos($current_filename, "/") + 1));

$current_file = get_attached_file((int) $_POST["ID"], apply_filters( 'emr_unfiltered_get_attached_file', true ));
$current_path = substr($current_file, 0, (strrpos($current_file, "/")));
$current_file = str_replace("//", "/", $current_file);
$current_filename = basename($current_file);

$replace_type = $_POST["replace_type"];

if (is_uploaded_file($_FILES["userfile"]["tmp_name"])) {
    $filedata = wp_check_filetype_and_ext($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"]);
    
    if ($filedata["ext"] == "") {
        echo "File type does not meet security guidelines. Try another.";
        exit;
    }
    $new_filename = $_FILES["userfile"]["name"];
    $new_filesize = $_FILES["userfile"]["size"];
    $new_filetype = $filedata["type"];

    $original_file_perms = fileperms($current_file) & 0777;
    if ($replace_type == 'replace') {

        lighthouse_delete_current_files($current_file);
        
        move_uploaded_file($_FILES["userfile"]["tmp_name"], $current_file);
        
        @chmod($current_file, $original_file_perms);
        
        wp_update_attachment_metadata(
            (int) $_POST["ID"], 
            wp_generate_attachment_metadata((int) $_POST["ID"], $current_file)
        );
        
        update_attached_file((int) $_POST["ID"], $current_file);
    } else if ('replace_and_search' == $replace_type && apply_filters('youknow_enable_replace_and_search', true)) {
        
        lighthouse_delete_current_files($current_file);
        
        $new_filename = wp_unique_filename($current_path, $new_filename);
        
        $new_file = $current_path . "/" . $new_filename;
        move_uploaded_file($_FILES["userfile"]["tmp_name"], $new_file);
        
        @chmod($current_file, $original_file_perms);

        $new_filetitle = preg_replace('/\.[^.]+$/', '', basename($new_file));
        $new_filetitle = apply_filters( 'enable_media_replace_title', $new_filetitle ); 
        $new_guid = str_replace($current_filename, $new_filename, $current_guid);

        // Update database file name
        $sql = $wpdb->prepare(
            "UPDATE $table_name SET post_title = '$new_filetitle', post_name = '$new_filetitle', guid = '$new_guid', post_mime_type = '$new_filetype' WHERE ID = %d;",
            (int) $_POST["ID"]
        );
        $wpdb->query($sql);

        // Update the postmeta file name

        // Get old postmeta _wp_attached_file
        $sql = $wpdb->prepare(
            "SELECT meta_value FROM $postmeta_table_name WHERE meta_key = '_wp_attached_file' AND post_id = %d;",
            (int) $_POST["ID"]
        );
        
        $old_meta_name = $wpdb->get_row($sql, ARRAY_A);
        $old_meta_name = $old_meta_name["meta_value"];

        // Make new postmeta _wp_attached_file
        $new_meta_name = str_replace($current_filename, $new_filename, $old_meta_name);
        $sql = $wpdb->prepare(
            "UPDATE $postmeta_table_name SET meta_value = '$new_meta_name' WHERE meta_key = '_wp_attached_file' AND post_id = %d;",
            (int) $_POST["ID"]
        );
        $wpdb->query($sql);

        wp_update_attachment_metadata( 
            (int) $_POST["ID"], 
            wp_generate_attachment_metadata( (int) $_POST["ID"], $new_file) 
        );

        $sql = $wpdb->prepare(
            "SELECT ID, post_content FROM $table_name WHERE post_content LIKE %s;",
            '%' . $current_guid . '%'
        );

        $rs = $wpdb->get_results($sql, ARRAY_A);
        
        foreach($rs as $rows) {
            
            $post_content = $rows["post_content"];
            $post_content = addslashes(str_replace($current_guid, $new_guid, $post_content));

            $sql = $wpdb->prepare(
                "UPDATE $table_name SET post_content = '$post_content' WHERE ID = %d;",
                $rows["ID"]
            );

            $wpdb->query($sql);
        }
        update_attached_file( (int) $_POST["ID"], $new_file);
    }
    $returnurl = get_bloginfo("wpurl") . "/wp-admin/post.php?post={$_POST["ID"]}&action=edit&message=1";
    if (isset($new_guid)) {
        do_action("enable-media-replace-upload-done", ($new_guid ? $new_guid: $current_guid));
    }
} else {
    $returnurl = get_bloginfo("wpurl")."/wp-admin/upload.php";
}
if (FORCE_SSL_ADMIN) {
    $returnurl = str_replace("http:", "https:", $returnurl);
}
wp_redirect($returnurl);