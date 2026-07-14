<?php
/**
 * Plugin Name: IBAW-Image-Sequence-Widget
 * Description: Custom Elementor widget to display a sequence of images with cross-fade transition.
 * Version: 1.0.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function ibaw_register_image_sequence_widget( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/image-sequence-widget.php' );
    $widgets_manager->register( new \IBAW_Image_Sequence_Widget() );
}
add_action( 'elementor/widgets/register', 'ibaw_register_image_sequence_widget' );

// Ensure directory exists
if ( ! is_dir( __DIR__ . '/widgets' ) ) {
    mkdir( __DIR__ . '/widgets' );
}

$widget_code = '<?php
class IBAW_Image_Sequence_Widget extends \Elementor\Widget_Base {
    public function get_name() { return "ibaw-image-sequence"; }
    public function get_title() { return "Image Sequence"; }
    public function get_icon() { return "eicon-slides"; }
    public function get_categories() { return [ "general" ]; }

    protected function register_controls() {
        $this->start_controls_section("content_section", ["label" => "Settings"]);
        $this->add_control("img1", ["label" => "Base Image", "type" => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control("img2", ["label" => "Middle Image", "type" => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control("img3", ["label" => "Final Image", "type" => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control("speed", ["label" => "Speed (ms)", "type" => \Elementor\Controls_Manager::NUMBER, "default" => 3000]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $images = [$settings["img1"]["url"], $settings["img2"]["url"], $settings["img3"]["url"]];
        $speed = $settings["speed"];
        ?>
        <div class="ibaw-sequence-container" style="position: relative; width: 100%; height: 500px;">
            <?php foreach($images as $index => $url): ?>
                <img src="<?php echo esc_url($url); ?>" class="ibaw-img" style="position: absolute; top:0; left:0; width:100%; opacity:<?php echo $index === 0 ? 1 : 0; ?>; transition: opacity 1s;">
            <?php endforeach; ?>
        </div>
        <script>
            (function() {
                let container = document.currentScript.previousElementSibling;
                let imgs = container.querySelectorAll(".ibaw-img");
                let index = 0;
                setInterval(() => {
                    imgs[index].style.opacity = 0;
                    index = (index + 1) % imgs.length;
                    imgs[index].style.opacity = 1;
                }, <?php echo $speed; ?>);
            })();
        </script>
        <?php
    }
}';

file_put_contents( __DIR__ . '/widgets/image-sequence-widget.php', $widget_code );