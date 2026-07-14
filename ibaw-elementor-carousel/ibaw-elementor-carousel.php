<?php
/**
 * Plugin Name: IBAW- Elementor Ultimate Carousel
 * Plugin URI:  https://ericksonvilleta.com
 * Description: High-performance Elementor carousel with Infinite Loop, Autoplay, Dots, and working Mobile Anchors.
 * Version:     1.0
 * Author:      Erick Villeta
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function ibaw_register_ultimate_carousel_widget( $widgets_manager ) {

    class IBAW_Ultimate_Carousel_Widget extends \Elementor\Widget_Base {

        public function get_name() { return 'ibaw_ultimate_carousel'; }
        public function get_title() { return 'IBAW- Ultimate Carousel'; }
        public function get_icon() { return 'eicon-inner-section'; }
        public function get_categories() { return [ 'general' ]; }

        protected function register_controls() {
            $this->start_controls_section( 'content', [ 'label' => 'Carousel Settings' ] );

            $repeater = new \Elementor\Repeater();
            $repeater->add_control( 'image', [ 'label' => 'Image', 'type' => \Elementor\Controls_Manager::MEDIA ] );
            $repeater->add_control( 'link', [ 'label' => 'Link/Anchor', 'type' => \Elementor\Controls_Manager::TEXT ] );

            $this->add_control( 'slides', [
                'label' => 'Slides',
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
            ]);

            $this->add_control( 'autoplay_speed', [
                'label' => 'Autoplay Speed (ms)',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3000,
            ]);

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $id = 'ibaw-ult-' . $this->get_id();
            $slides = $settings['slides'];
            if ( empty( $slides ) ) return;
            ?>

            <style>
                .ibaw-ult-container { position: relative; width: 100%; overflow: hidden; }
                .ibaw-ult-wrapper { display: flex; transition: transform 0.5s ease-in-out; }
                .ibaw-ult-slide { flex: 0 0 100%; box-sizing: border-box; padding: 5px; }
                .ibaw-ult-slide img { width: 100%; border-radius: 12px; display: block; }
                
                /* Navigation Arrows */
                .ibaw-arrow { position: absolute; top: 50%; transform: translateY(-50%); background: #fff; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 5; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
                .ibaw-prev { left: 10px; }
                .ibaw-next { right: 10px; }

                /* Dots Navigation */
                .ibaw-dots { display: flex; justify-content: center; gap: 8px; margin-top: 15px; }
                .ibaw-dot { width: 10px; height: 10px; border-radius: 50%; background: #ccc; cursor: pointer; transition: 0.3s; }
                .ibaw-dot.active { background: #5DA92F; width: 25px; border-radius: 10px; }

                @media (min-width: 768px) { .ibaw-ult-slide { flex: 0 0 33.333%; } }
            </style>

            <div class="ibaw-ult-container" id="<?php echo $id; ?>">
                <div class="ibaw-ult-wrapper">
                    <?php 
                    // Clones for Infinite Loop (Last 3 to start, First 3 to end)
                    $total = count($slides);
                    for($i = $total-3; $i < $total; $i++) { if(isset($slides[$i])) $this->print_slide($slides[$i]); }
                    foreach ( $slides as $item ) { $this->print_slide($item); }
                    for($i = 0; $i < 3; $i++) { if(isset($slides[$i])) $this->print_slide($slides[$i]); }
                    ?>
                </div>
                <div class="ibaw-arrow ibaw-prev">❮</div>
                <div class="ibaw-arrow ibaw-next">❯</div>
                <div class="ibaw-dots">
                    <?php foreach($slides as $index => $item): ?>
                        <div class="ibaw-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                const $container = $('#<?php echo $id; ?>');
                const $wrapper = $container.find('.ibaw-ult-wrapper');
                const realSlideCount = <?php echo $total; ?>;
                const $dots = $container.find('.ibaw-dot');
                let currentIndex = 3; // Starting after clones
                let isTransitioning = false;

                function getSlideWidth() { return $container.find('.ibaw-ult-slide').outerWidth(); }

                function updateLayout(animate = true) {
                    const width = getSlideWidth();
                    $wrapper.css({
                        'transition': animate ? 'transform 0.5s ease-in-out' : 'none',
                        'transform': `translateX(-${currentIndex * width}px)`
                    });

                    // Update Dots
                    let dotIndex = (currentIndex - 3) % realSlideCount;
                    if (dotIndex < 0) dotIndex = realSlideCount + dotIndex;
                    $dots.removeClass('active').eq(dotIndex).addClass('active');
                }

                // Initial position
                updateLayout(false);

                function moveNext() {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    currentIndex++;
                    updateLayout();
                }

                $wrapper.on('transitionend', function() {
                    isTransitioning = false;
                    // INFINITE LOOP LOGIC
                    if (currentIndex >= realSlideCount + 3) {
                        currentIndex = 3;
                        updateLayout(false);
                    }
                    if (currentIndex <= 2) {
                        currentIndex = realSlideCount + 2;
                        updateLayout(false);
                    }
                });

                $container.find('.ibaw-next').on('click', moveNext);
                $container.find('.ibaw-prev').on('click', () => {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    currentIndex--;
                    updateLayout();
                });

                $dots.on('click', function() {
                    currentIndex = $(this).data('index') + 3;
                    updateLayout();
                });

                // AUTOPLAY
                let play = setInterval(moveNext, <?php echo $settings['autoplay_speed']; ?>);
                $container.hover(() => clearInterval(play), () => play = setInterval(moveNext, <?php echo $settings['autoplay_speed']; ?>));

                // MOBILE ANCHOR FIX
                $container.find('a[href^="#"]').on('click', function(e) {
                    const target = $(this.getAttribute('href'));
                    if(target.length) {
                        e.preventDefault();
                        $('html, body').animate({ scrollTop: target.offset().top - 80 }, 800);
                    }
                });
            });
            </script>
            <?php
        }

        private function print_slide($item) {
            echo '<div class="ibaw-ult-slide"><a href="'.esc_attr($item['link']).'"><img src="'.esc_url($item['image']['url']).'" /></a></div>';
        }
    }
    $widgets_manager->register( new \IBAW_Ultimate_Carousel_Widget() );
}
add_action( 'elementor/widgets/register', 'ibaw_register_ultimate_carousel_widget' );