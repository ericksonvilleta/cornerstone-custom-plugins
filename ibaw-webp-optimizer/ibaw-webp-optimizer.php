<?php
/**
 * Plugin Name: IBAW- WebP Image Optimizer
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Automatically converts PNG, JPG, and JPEG uploads to WebP to boost site speed and performance.
 * Version:     1.0.0
 * Author:      Erick Villeta
 * Author URI:  https://ericksonvilleta.com
 * License:     GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
class IBAW_WebP_Optimizer {

    public function __construct() {
        // Hook into the attachment metadata generation process
        add_filter('wp_generate_attachment_metadata', [$this, 'ibaw_process_webp_conversion'], 10, 2);
    }

    /**
     * Handles the conversion of uploaded images to WebP.
     * * @param array $metadata      Attachment metadata.
     * @param int   $attachment_id Attachment ID.
     * @return array Modified metadata.
     */
    public function ibaw_process_webp_conversion($metadata, $attachment_id) {
        $file = get_attached_file($attachment_id);
        $type = get_post_mime_type($attachment_id);

        // Define supported mime types
        $supported_types = ['image/jpeg', 'image/jpg', 'image/png'];

        if (!in_array($type, $supported_types)) {
            return $metadata;
        }

        // Check if server supports WebP conversion via GD or Imagick
        if (!$this->ibaw_server_supports_webp()) {
            return $metadata;
        }

        $image_editor = wp_get_image_editor($file);

        if (!is_wp_error($image_editor)) {
            $file_info = pathinfo($file);
            $dirname   = $file_info['dirname'];
            $filename  = $file_info['filename'];
            
            // Create the WebP path
            $webp_path = $dirname . '/' . $filename . '.webp';

            // Save the image as WebP
            $saved = $image_editor->save($webp_path, 'image/webp');

            if (!is_wp_error($saved)) {
                // Update the database record to point to the new WebP file
                update_attached_file($attachment_id, $webp_path);

                // Update metadata to reflect the new extension and mime type
                $metadata['file'] = str_replace(
                    [$file_info['basename']], 
                    [$filename . '.webp'], 
                    $metadata['file']
                );

                // Optionally delete the original file to save space
                if (file_exists($file) && $file !== $webp_path) {
                    unlink($file);
                }
            }
        }

        return $metadata;
    }

    /**
     * Check if the server has the necessary libraries for WebP.
     * * @return bool
     */
    private function ibaw_server_supports_webp() {
        if (function_exists('imagick_supported_formats')) {
            $formats = @Imagick::queryFormats('WEBP');
            if (in_array('WEBP', $formats)) {
                return true;
            }
        }

        if (function_exists('gd_info')) {
            $gd_info = gd_info();
            if (isset($gd_info['WebP Support']) && $gd_info['WebP Support']) {
                return true;
            }
        }

        return false;
    }
}

// Initialize the plugin
new IBAW_WebP_Optimizer();