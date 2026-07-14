<?php
/**
 * Plugin Name: Shed Custom Gallery
 * Description: Replaces the product gallery using the Featured Image and the 'shed_gallery' ACF field with Nav Arrows.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Enqueue Styles and Scripts
function scg_enqueue_assets() {
    wp_register_style( 'scg-style', false );
    wp_enqueue_style( 'scg-style' );
    wp_add_inline_style( 'scg-style', "
        .scg-container { display: flex; flex-direction: column; gap: 15px; max-width: 600px; margin: 20px 0; position: relative; }
        
        /* Main Image Viewport */
        .scg-main-wrapper { position: relative; width: 100%; }
        .scg-main-image img { width: 100%; height: auto; border-radius: 8px; transition: opacity 0.3s ease; display: block; }
        
        /* Navigation Arrows */
        .scg-nav-btn {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(0,0,0,0.5); color: #fff; border: none;
            padding: 15px 10px; cursor: pointer; font-size: 20px; z-index: 10;
            border-radius: 4px; transition: background 0.2s;
        }
        .scg-nav-btn:hover { background: rgba(0,0,0,0.8); }
        .scg-prev { left: 10px; }
        .scg-next { right: 10px; }

        /* Thumbnails */
        .scg-thumbnails { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; }
        .scg-thumb img { width: 100%; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent; border-radius: 4px; }
        .scg-thumb img.active { border-color: #81B716; }
        .scg-thumb img:hover { opacity: 0.8; }
    ");
}
add_action( 'wp_enqueue_scripts', 'scg_enqueue_assets' );

// 2. The Gallery Shortcode [shed_gallery_view]
function scg_display_gallery() {
    $post_id = get_the_ID();
    $featured_id = get_post_thumbnail_id($post_id);
    $gallery_images = get_field('shed_gallery', $post_id);
    
    if ( !$featured_id && empty($gallery_images) ) return '';

    $all_ids = array();
    if ($featured_id) $all_ids[] = $featured_id;
    
    if ($gallery_images) {
        foreach ($gallery_images as $img) {
            $id = is_array($img) ? $img['ID'] : $img;
            if ($id !== $featured_id) $all_ids[] = $id;
        }
    }

    ob_start();
    ?>
    <div class="scg-container">
        <div class="scg-main-wrapper">
            <button class="scg-nav-btn scg-prev" id="scg-prev-btn">&#10094;</button>
            <div class="scg-main-image" id="scg-main-viewport">
                <?php echo wp_get_attachment_image($all_ids[0], 'large'); ?>
            </div>
            <button class="scg-nav-btn scg-next" id="scg-next-btn">&#10095;</button>
        </div>

        <div class="scg-thumbnails">
            <?php foreach ($all_ids as $index => $img_id) : 
                $thumb_url = wp_get_attachment_image_src($img_id, 'thumbnail')[0];
                $large_url = wp_get_attachment_image_src($img_id, 'large')[0];
                $srcset    = wp_get_attachment_image_srcset($img_id, 'large');
            ?>
                <div class="scg-thumb">
                    <img 
                        src="<?php echo esc_url($thumb_url); ?>" 
                        data-large="<?php echo esc_url($large_url); ?>"
                        data-srcset="<?php echo esc_attr($srcset); ?>"
                        class="<?php echo ($index === 0) ? 'active' : ''; ?>"
                        alt="Gallery Thumbnail"
                    >
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const thumbs = document.querySelectorAll('.scg-thumb img');
        const mainImg = document.querySelector('#scg-main-viewport img');
        const prevBtn = document.querySelector('#scg-prev-btn');
        const nextBtn = document.querySelector('#scg-next-btn');

        function updateMainImage(index) {
            const selectedThumb = thumbs[index];
            mainImg.style.opacity = '0.5';
            
            setTimeout(() => {
                mainImg.src = selectedThumb.dataset.large;
                mainImg.srcset = selectedThumb.dataset.srcset;
                mainImg.style.opacity = '1';
            }, 150);

            // Update Active Class
            thumbs.forEach(t => t.classList.remove('active'));
            selectedThumb.classList.add('active');
        }

        // Thumbnail Click
        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => updateMainImage(index));
        });

        // Navigation Logic
        nextBtn.addEventListener('click', function() {
            let currentIndex = Array.from(thumbs).findIndex(img => img.classList.contains('active'));
            let nextIndex = (currentIndex + 1) % thumbs.length;
            updateMainImage(nextIndex);
        });

        prevBtn.addEventListener('click', function() {
            let currentIndex = Array.from(thumbs).findIndex(img => img.classList.contains('active'));
            let prevIndex = (currentIndex - 1 + thumbs.length) % thumbs.length;
            updateMainImage(prevIndex);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'shed_gallery_view', 'scg_display_gallery' );