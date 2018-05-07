<?php
namespace Lighthouse\Media;

use enshrined\svgSanitize\Sanitizer;

/**
 * Based on Darrell Doyle's plugin SafeSvg
 */

class EnableSvgUploads 
{

    /**
     * Sanitizer instance
     * @var \enshrined\svgSanitize\Sanitizer
     */
    protected $sanitizer;

    /**
     * Instantiate the class
     * @param Sanitizer $sanitizer 
     */
    public function __construct(Sanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
        $this->sanitizer->minify(true);
    }

    /**
     * Hook into WordPress eventing system
     * @return void
     */
    public function run()
    {
        add_filter('upload_mimes', [$this, 'enable']);
        add_filter('wp_handle_upload_prefilter', [$this, 'maybeSanitize']);
        add_filter('wp_check_filetype_and_ext', [$this, 'normalizeMimeType'], 75, 4);
        add_filter('wp_prepare_attachment_for_js', [$this, 'fixAdminPreview'], 10, 3);
        add_filter('wp_get_attachment_image_src', [$this, 'adjustImgSize'], 10, 4);
        add_filter('admin_post_thumbnail_html', [$this, 'wrapFeaturedImage']);
        add_action('get_image_tag', [$this, 'updateImageTag'], 10, 6);
    }

    /**
     * Sanitize the svg
     * @param  obj $file
     * @return boolean|int
     */
    protected function sanitize($file)
    {
        $dirty = file_get_contents($file);
        if ($is_zipped = $this->isGzipped($dirty)) {
            $dirty = gzdecode($dirty);
        }
        if ($dirty === false) {
            return false;
        }
        $clean = $this->sanitizer->sanitize($dirty);
        if ($clean === false) {
            return false;
        }
        if ($is_zipped) {
            $clean = gzencode($clean);
        }
        file_put_contents($file, $clean);

        return true;
    }

    /**
     * Check whether the contents are g-zipped
     * @param  string  $contents
     * @return boolean
     */
    protected function isGzipped($contents)
    {
        if (function_exists('mb_strpos')) {
            return 0 === mb_strpos($contents, "\x1f" . "\x8b" . "\x08");
        } else {
            return 0 === strpos($contents, "\x1f" . "\x8b" . "\x08");
        }
    }

    /**
     * Add svg to allowable file types for media upload
     * @param  array $mimes
     * @return array
     */
    public function enable($mimes)
    {
        $mimes['svg'] = 'image/svg+xml'; 
        $mimes['svgz'] = 'image/svg+xml';  

        return $mimes;
    }

    /**
     * Check if the file is an SVG
     * @param  string $file
     * @return mixed
     */
    public function maybeSanitize($file)
    {
        if ($file['type'] === 'image/svg+xml') {
            if (! $this->sanitize($file['tmp_name'])) {
                $file['error'] = __("Sorry, this file couldn't be sanitized so for security reasons it wasn't uploaded",
                    LH_DOMAIN);
            }
        }

        return $file;
    }

    /**
     * Make sure the svg file type and extension are properly identified
     * within WordPress.
     * @param  array|null $data
     * @param  array $file
     * @param  string $filename
     * @param  array $mimes
     * @return array       
     */
    public function normalizeMimeType($data = null, $file = null, $filename = null, $mimes = null)
    {
        $ext = isset($data['ext']) ? $data['ext'] : '';
        if (strlen($ext) < 1) {
            $exploded = explode('.', $filename);
            $ext = strtolower(end($exploded));
        }
        if ($ext === 'svg') {
            $data['type'] = 'image/svg+xml';
            $data['ext'] = 'svg';
        } elseif ($ext === 'svgz') {
            $data['type'] = 'image/svg+xml';
            $data['ext'] = 'svgz';
        }
        return $data;
    }

    /**
     * Filters the attachment data prepared for JS to add the sizes
     * array to the response if it is SVG
     * @param  array      $response
     * @param  int|object $attachment
     * @param  array      $meta
     * @return array
     */
    public function fixAdminPreview($response, $attachment, $meta)
    {
        if ($response['mime'] == 'image/svg+xml') {
            $possible_sizes = apply_filters('image_size_names_choose', [
                'thumbnail' => __('Thumbnail'),
                'medium'    => __('Medium'),
                'large'     => __('Large'),
                'full'      => __('Full Size'),
            ]);
            $sizes = [];
            foreach ($possible_sizes as $size => $label) {
                $sizes[$size] = array(
                    'height'      => 2000,
                    'width'       => 2000,
                    'url'         => $response['url'],
                    'orientation' => 'portrait',
                );
            }
            $response['sizes'] = $sizes;
            $response['icon'] = $response['url'];
        }

        return $response;
    }
    /**
     * Set the height and width of the img to 100% if SVG.
     * @param  string|array $image
     * @param  int $attachment_id
     * @param  string|array $size
     * @param  boolean $icon
     * @return array
     */
    public function adjustImgSize($image, $attachment_id, $size, $icon)
    {
        if (get_post_mime_type($attachment_id) == 'image/svg+xml') {
            $image['1'] = false;
            $image['2'] = false;
        }

        return $image;
    }

    /**
     * Wrap featured image in a span if it is SVG
     * @param  string $content
     * @param  int    $post_id
     * @param  int    $thumbnail_id
     * @return string
     */
    public function wrapFeaturedImage($content, $post_id, $thumbnail_id)
    {
        $mime = get_post_mime_type($thumbnail_id);
        if ('image/svg+xml' === $mime) {
            $content = sprintf('<span class="svg">%s</span>', $content);
        }

        return $content;
    }

    /**
     * Add size info to an img tag containing the SVG
     * @param  string $html  
     * @param  int $id    
     * @param  string $alt   
     * @param  string $title 
     * @param  string $align 
     * @param  string $size  
     * @return string
     */
    public function updateImageTag($html, $id, $alt, $title, $align, $size)
    {
        $mime = get_post_mime_type($id);

        if('image/svg+xml' === $mime) {
            $html = str_replace('width="1" ', '', $html);
            $html = str_replace('height="1" ', '', $html);
        }

        return $html;
    }
}