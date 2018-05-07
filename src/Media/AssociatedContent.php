<?php 
namespace Lighthouse\Media;

class AssociatedContent
{
    public function run()
    {
       add_filter('attachment_fields_to_edit', [$this, 'listAssociatedPosts'], 10, 2);

       add_action('wp_ajax_lh_media_associated_content', [$this, 'findPostsForAttachment']);
    }

    public function findPostsForAttachment()
    {
        check_ajax_referer('lh_admin_token');

        if (!isset($_GET['attachment_id']) || $_GET['attachment_id'] == '0') {
            $data = [
                'result' => 'failure',
                'message' => 'Missing attachment id'
            ];
            return wp_send_json_error($data);
        }
        $attachment_id = intval($_GET['attachment_id']);

        //actual logic.
        $output = $this->getAssociatedPosts($attachment_id);
        
        $response['result'] = 'success';
        $response['posts'] = $output;

        return wp_send_json_success($response);
    }

    public function listAssociatedPosts($form_fields, $attachment)
    {
        $form_fields['used_in'] = [
            'label' => 'Used In',
            'input' => 'html',
            'html' => $this->render($attachment->ID)
        ];

        //$this->getAssociatedPosts($attachment->ID, 'details')

        return $form_fields;
    }

    public function render($attachment_id)
    {
        $output = '<div id="lh_where_used_container">';
        $output .= '<p style="margin-bottom:0;">';
        $output .= '<button id="lh_btn_where_used" class="button" data-att="'.$attachment_id.'">Get Usage Information</button>';
        $output .= '</p>';
        $output .= '<p id="lh_where_used_list" style="margin-top:0.2rem;"><em>Get the list of sections where this asset is used</em></p></div>';

        return $output;
    }

    public function getAssociatedPosts($attachment_id)
    {
        $post_ids = $this->getPostsByAttachmentId($attachment_id);
        $posts_list = array_merge($post_ids['thumbnail'], $post_ids['content']);
        $posts_list = array_unique($posts_list);

        $item_format = '%1$s %3$s<br />';
        $output_format = '<div style="padding-top: 8px">%s</div>';

        $output = '';


        $associated = new \WP_Query(['post__in' => $posts_list, 'post_type' => ['post', 'slide', 'iefs_tu', 'tutorial', 'page', 'attachment', 'module', 'course']]);

        $posts = $associated->posts;

        foreach ($posts as $post) {
            if (! $post) {
                continue;
            }
            $post_id = $post->ID;
            $post_title = _draft_or_post_title($post);
            $post_type = get_post_type_object($post->post_type);
            if ($post_type && $post_type->show_ui && current_user_can('edit_post', $post_id)) {
                $link = sprintf('<a href="%s">%s</a>', get_edit_post_link($post_id), $post_title);
            } else {
                $link = $post_title;
            }
            if (in_array($post_id, $post_ids['thumbnail']) && in_array($post_id, $post_ids['content'])) {
                $usage_content = '(as Featured Image and in content)';
            } else if (in_array($post_id, $post_ids['thumbnail'])) {
                $usage_content = '(as Featured Image)';
            } else {
                $usage_content = '(in content)';
            }

            $output .= sprintf($item_format, $link, get_the_time('Y/m/d'), $usage_content);
        }

        if (! $output) {
            $output = '(Unused)';
        }

        $output = sprintf($output_format, $output);

        return $output;
    }

    protected function getPostsByAttachmentId($attachment_id)
    {
        $used_as_thumbnail = [];

        // if (wp_attachment_is_image($attachment_id)) {
        //     $thumbnail_query = new \WP_Query([
        //         'meta_key' => '_thumbnail_id',
        //         'meta_value' => $attachment_id,
        //         'post_type' => ['post', 'page', 'slide', 'iefs_tu', 'module', 'course', 'curriculum', ' tutorial'],
        //         'fields' => 'ids',
        //         'no_found_rows' => true, 
        //         'posts_per_page' => -1,
        //     ]);
        //     $used_as_thumbnail = $thumbnail_query->posts;
        // }

        $attachment_urls = [ wp_get_attachment_url($attachment_id) ];
        if (wp_attachment_is_image($attachment_id)) {
            foreach(get_intermediate_image_sizes() as $size) {
                $intermediate = image_get_intermediate_size($attachment_id, $size);
                if ($intermediate) {
                    $attachment_urls[] = $intermediate['url'];
                }
            }
        }

        $used_in_content = [];
        foreach ($attachment_urls as $attachment_url) {
            $content_query = new \WP_Query([
                's' => $attachment_url, 
                'post_type' => ['post', 'page', 'slide', 'iefs_tu', 'module', 'course', 'curriculum',' tutorial'],
                'fields' => 'ids',
                'no_found_rows' => true,
                'posts_per_page' => -1,
            ]);
            $used_in_content = array_merge($used_in_content, $content_query->posts);
        }
        $used_in_content = array_unique($used_in_content);

        $posts = [
            'thumbnail' => $used_as_thumbnail,
            'content' => $used_in_content
        ];
        return $posts;
    }
}
