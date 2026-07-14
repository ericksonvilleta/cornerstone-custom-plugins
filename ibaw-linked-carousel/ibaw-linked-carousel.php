<?php
/**
 * Plugin Name: IBAW - Linked Image Carousel
 * Plugin URI: https://ericksonvilleta.com
 * Description: A custom Elementor widget that acts like the native image carousel but allows individual links for each image.
 * Version: 1.0.0
 * Author: Erickson Villeta
 * Author URI: https://ericksonvilleta.com
 * Text Domain: ibaw-linked-carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the custom Elementor widget.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function register_ibaw_linked_carousel_widget( $widgets_manager ) {

	class IBAW_Linked_Carousel_Widget extends \Elementor\Widget_Base {

		public function get_name() {
			return 'ibaw_linked_carousel';
		}

		public function get_title() {
			return esc_html__( 'IBAW - Linked Carousel', 'ibaw-linked-carousel' );
		}

		public function get_icon() {
			return 'eicon-carousel';
		}

		public function get_categories() {
			return [ 'general' ];
		}

		// Ensure Elementor loads its Swiper library for this widget
		public function get_script_depends() {
			return [ 'swiper' ]; 
		}

		public function get_style_depends() {
			return [ 'swiper' ]; 
		}

		protected function register_controls() {
			
			// Content Tab: Slides
			$this->start_controls_section(
				'content_section',
				[
					'label' => esc_html__( 'Carousel Images', 'ibaw-linked-carousel' ),
					'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'image',
				[
					'label' => esc_html__( 'Choose Image', 'ibaw-linked-carousel' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'default' => [
						'url' => \Elementor\Utils::get_placeholder_image_src(),
					],
				]
			);

			$repeater->add_control(
				'image_link',
				[
					'label' => esc_html__( 'Link', 'ibaw-linked-carousel' ),
					'type' => \Elementor\Controls_Manager::URL,
					'placeholder' => esc_html__( 'https://your-link.com', 'ibaw-linked-carousel' ),
					'options' => [ 'url', 'is_external', 'nofollow' ],
					'default' => [
						'url' => '',
						'is_external' => true,
						'nofollow' => false,
					],
					'label_block' => true,
				]
			);

			$this->add_control(
				'carousel_items',
				[
					'label' => esc_html__( 'Images', 'ibaw-linked-carousel' ),
					'type' => \Elementor\Controls_Manager::REPEATER,
					'fields' => $repeater->get_controls(),
					'default' => [
						[
							'image' => [
								'url' => \Elementor\Utils::get_placeholder_image_src(),
							],
						],
					],
					'title_field' => '{{{ image.url ? "Slide" : "Image" }}}',
				]
			);

			$this->end_controls_section();

			// Content Tab: Settings
			$this->start_controls_section(
				'settings_section',
				[
					'label' => esc_html__( 'Settings', 'ibaw-linked-carousel' ),
					'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			$this->add_control(
				'slides_to_show',
				[
					'label' => esc_html__( 'Slides to Show', 'ibaw-linked-carousel' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 1,
					'max' => 10,
					'step' => 1,
					'default' => 3,
				]
			);

			$this->add_control(
				'navigation',
				[
					'label' => esc_html__( 'Navigation', 'ibaw-linked-carousel' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'default' => 'both',
					'options' => [
						'both' => esc_html__( 'Arrows and Dots', 'ibaw-linked-carousel' ),
						'arrows' => esc_html__( 'Arrows', 'ibaw-linked-carousel' ),
						'dots' => esc_html__( 'Dots', 'ibaw-linked-carousel' ),
						'none' => esc_html__( 'None', 'ibaw-linked-carousel' ),
					],
				]
			);

			$this->end_controls_section();
		}

		protected function render() {
			$settings = $this->get_settings_for_display();

			if ( empty( $settings['carousel_items'] ) ) {
				return;
			}

			// Unique ID to ensure multiple widgets on the same page don't conflict
			$uid = 'swiper-' . $this->get_id();
			$slides_to_show = $settings['slides_to_show'] ? $settings['slides_to_show'] : 3;
			$nav = $settings['navigation'];

			?>
			<div class="ibaw-linked-carousel-wrapper" id="<?php echo esc_attr( $uid ); ?>">
				<div class="swiper-container swiper">
					<div class="swiper-wrapper">
						<?php foreach ( $settings['carousel_items'] as $index => $item ) : ?>
							<div class="swiper-slide">
								<?php
								$image_url = ! empty( $item['image']['url'] ) ? esc_url( $item['image']['url'] ) : '';
								$image_html = '<img src="' . $image_url . '" alt="" />';

								if ( ! empty( $item['image_link']['url'] ) ) {
									$link_key = 'link_' . $index;
									$this->add_link_attributes( $link_key, $item['image_link'] );
									echo '<a ' . $this->get_render_attribute_string( $link_key ) . '>';
									echo $image_html;
									echo '</a>';
								} else {
									echo $image_html;
								}
								?>
							</div>
						<?php endforeach; ?>
					</div>
					
					<?php if ( in_array( $nav, [ 'both', 'dots' ] ) ) : ?>
						<div class="swiper-pagination"></div>
					<?php endif; ?>
					
					<?php if ( in_array( $nav, [ 'both', 'arrows' ] ) ) : ?>
						<div class="swiper-button-prev"></div>
						<div class="swiper-button-next"></div>
					<?php endif; ?>
				</div>
			</div>

			<style>
				.ibaw-linked-carousel-wrapper { position: relative; width: 100%; overflow: hidden; }
				.ibaw-linked-carousel-wrapper .swiper-slide img { display: block; width: 100%; height: auto; object-fit: cover; border-radius: 8px; }
				.ibaw-linked-carousel-wrapper a { display: block; width: 100%; height: 100%; transition: opacity 0.3s ease; }
				.ibaw-linked-carousel-wrapper a:hover { opacity: 0.85; }
				/* Basic Swiper Navigation Overrides */
				.ibaw-linked-carousel-wrapper .swiper-button-next, 
				.ibaw-linked-carousel-wrapper .swiper-button-prev { color: #333; transform: scale(0.7); }
			</style>

			<script>
			jQuery(document).ready(function($) {
				var initSwiper = function() {
					var swiperElement = document.querySelector('#<?php echo esc_attr( $uid ); ?> .swiper-container');
					if ( ! swiperElement ) return;
					
					var swiperConfig = {
						slidesPerView: <?php echo esc_js( $slides_to_show ); ?>,
						spaceBetween: 20,
						loop: true,
						autoplay: {
							delay: 1000, // Time between slides in milliseconds (1000 = 1 second)
							disableOnInteraction: false, // Keeps autoplay running after user clicks/swipes
						},
						pagination: {
							el: '#<?php echo esc_attr( $uid ); ?> .swiper-pagination',
							clickable: true,
						},
						navigation: {
							nextEl: '#<?php echo esc_attr( $uid ); ?> .swiper-button-next',
							prevEl: '#<?php echo esc_attr( $uid ); ?> .swiper-button-prev',
						},
						breakpoints: {
							320: { slidesPerView: 1 },
							768: { slidesPerView: <?php echo esc_js( min(2, $slides_to_show) ); ?> },
							1024: { slidesPerView: <?php echo esc_js( $slides_to_show ); ?> },
						}
					};
					
					// Handle Elementor's async Swiper implementation
					if (typeof elementorFrontend !== 'undefined' && typeof elementorFrontend.utils.swiper !== 'undefined') {
						new elementorFrontend.utils.swiper(swiperElement, swiperConfig).then( ( SwiperInstance ) => {
							// Force start autoplay
							if (SwiperInstance && SwiperInstance.autoplay) {
								SwiperInstance.autoplay.start();
							}
						});
					} else if (typeof Swiper !== 'undefined') {
						// Fallback if Swiper is already globally available
						var fallbackSwiper = new Swiper(swiperElement, swiperConfig);
						if (fallbackSwiper && fallbackSwiper.autoplay) {
							fallbackSwiper.autoplay.start();
						}
					}
				};

				// Init on load
				setTimeout(initSwiper, 200);

				// Re-init if edited live in the Elementor Editor
				$(window).on('elementor/frontend/init', function() {
					elementorFrontend.hooks.addAction('frontend/element_ready/ibaw_linked_carousel.default', function($scope){
						initSwiper();
					});
				});
			});
			</script>
			<?php
		}
	}

	$widgets_manager->register( new IBAW_Linked_Carousel_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_linked_carousel_widget' );