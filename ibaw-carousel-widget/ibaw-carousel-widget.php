<?php
/**
 * Plugin Name: IBAW- Elementor Carousel Widget
 * Description: Custom 3D Carousel Widget for Elementor.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_ibaw_carousel_widget( $widgets_manager ) {
    class IBAW_Carousel_Widget extends \Elementor\Widget_Base {

        public function get_name() { return 'ibaw-carousel'; }
        public function get_title() { return 'IBAW Carousel'; }
        public function get_icon() { return 'eicon-slider-push'; }
        public function get_categories() { return [ 'general' ]; }

        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __( 'Carousel Images', 'ibaw-carousel' ),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'image_1',
                [
                    'label' => __( 'Image 1 (Main Position)', 'ibaw-carousel' ),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'default' => [
                        'url' => 'https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/image-carousel-cc-1.webp',
                    ],
                ]
            );

            $this->add_control(
                'image_2',
                [
                    'label' => __( 'Image 2 (Right Position)', 'ibaw-carousel' ),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'default' => [
                        'url' => 'https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/image-carousel-cc-3.webp',
                    ],
                ]
            );

            $this->add_control(
                'image_3',
                [
                    'label' => __( 'Image 3 (Left Position)', 'ibaw-carousel' ),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'default' => [
                        'url' => 'https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/image-carousel-cc-2.webp',
                    ],
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            
            $img1 = !empty( $settings['image_1']['url'] ) ? $settings['image_1']['url'] : 'https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/image-carousel-cc-1.webp';
            $img2 = !empty( $settings['image_2']['url'] ) ? $settings['image_2']['url'] : 'https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/image-carousel-cc-3.webp';
            $img3 = !empty( $settings['image_3']['url'] ) ? $settings['image_3']['url'] : 'https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/image-carousel-cc-2.webp';
            ?>
            <section class="ibaw-carousel-container">
                <ul class="carousel">
                    <li class="items main-pos" id="1"><img src="<?php echo esc_url( $img1 ); ?>" /></li>
                    <li class="items right-pos" id="2"><img src="<?php echo esc_url( $img2 ); ?>" /></li>
                    <li class="items left-pos" id="3"><img src="<?php echo esc_url( $img3 ); ?>"/></li>
                </ul>
                <div class="carousel-nav">
                    <input type="button" value="Prev" id="prev">
                    <input type="button" value="Next" id="next">
                </div>
            </section>

            <style>
                .ibaw-carousel-container {
                    font-size: 16px; /* Base size for standard scaling */
                    width: 40em; 
                    height: 25em; 
                    margin: 0 auto; 
                    position: relative;
                    max-width: 100%;
                }
                
                .ibaw-carousel-container ul.carousel {
                    padding: 0;
                    margin: 0;
                    list-style: none;
                }

                /* Fixed pixels converted to em so everything scales together */
                .ibaw-carousel-container li { 
                    width: 31.25em; 
                    height: 17.56em; 
                    background: #333; 
                    display: inline-block; 
                    transition: all .3s ease-in-out; 
                    overflow: hidden; 
                    position: absolute; 
                }
                
                /* Ensure images cover the li responsively */
                .ibaw-carousel-container li img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    display: block;
                }

                .ibaw-carousel-container li p { color: white; font-weight: bold; font-size: 5em; text-align: center; margin-top: 1.175em; }
                .main-pos { margin-left: 2em !important; z-index: 3000; }
                .left-pos { opacity: .3; margin-left: -17em !important; z-index: 1000; transform: scale(.75); }
                .back-pos { margin-left: 2em !important; opacity: .05; transform: scale(.5); }
                .right-pos { opacity: .3; margin-left: 21em !important; z-index: 1000; transform: scale(.75); }
                
                /* Center nav fluidly instead of relying on fixed positions */
                .carousel-nav { 
                    position: absolute; 
                    bottom: 0; 
                    left: 50%; 
                    transform: translateX(-50%); 
                    display: flex; 
                    gap: 15px; 
                    z-index: 4000;
                }
                
                /* Button Styles */
                .carousel-nav input[type="button"] {
                    border: none;
                    padding: 10px 20px;
                    color: white;
                    font-weight: bold;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    font-size: 16px; /* Prevent buttons from shrinking on mobile */
                }
                
                #prev { background-color: #59bcd1; }
                #next { background-color: #218ac3; }
                
                #prev:hover { background-color: #218ac3; }
                #next:hover { background-color: #59bcd1; }

                /* Media Queries for Mobile Responsiveness */
                @media (max-width: 900px) {
                    .ibaw-carousel-container { font-size: 1.8vw; }
                }
                @media (max-width: 600px) {
                    .ibaw-carousel-container { font-size: 2.1vw; }
                }
            </style>

            <script>
                jQuery(document).ready(function($) {
                    var autoSwap = setInterval(swap, 3500);
                    $('.carousel, .carousel-nav').hover(
                        function () { clearInterval(autoSwap); }, 
                        function () { autoSwap = setInterval(swap, 3500); }
                    );

                    function swap(action) {
                        var $main = $('.main-pos');
                        var $right = $('.right-pos');
                        var $left = $('.left-pos');

                        if(action == 'counter-clockwise') {
                            $main.removeClass('main-pos').addClass('right-pos');
                            $right.removeClass('right-pos').addClass('left-pos');
                            $left.removeClass('left-pos').addClass('main-pos');
                        } else {
                            $main.removeClass('main-pos').addClass('left-pos');
                            $left.removeClass('left-pos').addClass('right-pos');
                            $right.removeClass('right-pos').addClass('main-pos');
                        }
                    }

                    $('#next').click(function() { swap('clockwise'); });
                    $('#prev').click(function() { swap('counter-clockwise'); });
                    $('.carousel li').click(function() {
                        if($(this).hasClass('left-pos')) swap('counter-clockwise'); else swap('clockwise');
                    });
                });
            </script>
            <?php
        }
    }
    $widgets_manager->register( new IBAW_Carousel_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_carousel_widget' );