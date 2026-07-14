<?php
/**
 * Plugin Name: IBAW - Product Highlight
 * Plugin URI: https://ericksonvilleta.com
 * Description: A custom Elementor widget that displays a product image, title, price, star rating, specifications list, and a shop button.
 * Version: 1.0.0
 * Author: Erick Villeta
 * Author URI: https://ericksonvilleta.com
 * Text Domain: ibaw-product-highlight
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function register_ibaw_product_highlight_widget( $widgets_manager ) {

	class IBAW_Product_Highlight_Widget extends \Elementor\Widget_Base {

		public function get_name() {
			return 'ibaw_product_highlight';
		}

		public function get_title() {
			return esc_html__( 'IBAW - Product Highlight', 'ibaw-product-highlight' );
		}

		public function get_icon() {
			return 'eicon-product-meta';
		}

		public function get_categories() {
			return [ 'general' ];
		}

		protected function register_controls() {
			
			// --- Content Tab ---
			$this->start_controls_section(
				'content_section',
				[
					'label' => esc_html__( 'Product Details', 'ibaw-product-highlight' ),
					'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			$this->add_control(
				'product_image',
				[
					'label' => esc_html__( 'Product Image', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::MEDIA,
					'default' => [
						'url' => \Elementor\Utils::get_placeholder_image_src(),
					],
				]
			);

			$this->add_control(
				'product_title',
				[
					'label' => esc_html__( 'Product Title', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::TEXTAREA,
					'default' => esc_html__( 'BRADLEY 32" BELT DRIVE E-CLUTCH WALK-BEHIND MOW...', 'ibaw-product-highlight' ),
				]
			);

			$this->add_control(
				'product_price',
				[
					'label' => esc_html__( 'Price', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => esc_html__( '$3,149.00', 'ibaw-product-highlight' ),
				]
			);

			$this->add_control(
				'review_stars',
				[
					'label' => esc_html__( 'Star Rating (1-5)', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::NUMBER,
					'min' => 0,
					'max' => 5,
					'step' => 0.5,
					'default' => 5,
				]
			);

			$this->add_control(
				'review_count_text',
				[
					'label' => esc_html__( 'Review Count Text', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => esc_html__( '(1 reviews)', 'ibaw-product-highlight' ),
				]
			);

			$this->end_controls_section();

			// --- Specs Repeater Section ---
			$this->start_controls_section(
				'specs_section',
				[
					'label' => esc_html__( 'Specifications', 'ibaw-product-highlight' ),
					'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'spec_icon',
				[
					'label' => esc_html__( 'Icon', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::ICONS,
					'default' => [
						'value' => 'fas fa-bolt',
						'library' => 'solid',
					],
				]
			);

			$repeater->add_control(
				'spec_label',
				[
					'label' => esc_html__( 'Label', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => esc_html__( 'ENGINE', 'ibaw-product-highlight' ),
				]
			);

			$repeater->add_control(
				'spec_value',
				[
					'label' => esc_html__( 'Value', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::TEXTAREA,
					'default' => esc_html__( '17.5HP Briggs & Stratton Intek Electric Start Engine', 'ibaw-product-highlight' ),
				]
			);

			$this->add_control(
				'specs_list',
				[
					'label' => esc_html__( 'Specs List', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::REPEATER,
					'fields' => $repeater->get_controls(),
					'default' => [
						[
							'spec_label' => esc_html__( 'ENGINE', 'ibaw-product-highlight' ),
							'spec_value' => esc_html__( '17.5HP Briggs & Stratton Intek Electric Start Engine', 'ibaw-product-highlight' ),
							'spec_icon' => ['value' => 'fas fa-bolt', 'library' => 'solid'],
						],
						[
							'spec_label' => esc_html__( 'DRIVE', 'ibaw-product-highlight' ),
							'spec_value' => esc_html__( 'Peerless 5-speed transmission', 'ibaw-product-highlight' ),
							'spec_icon' => ['value' => 'fas fa-cog', 'library' => 'solid'],
						],
						[
							'spec_label' => esc_html__( 'CUTTING WIDTH', 'ibaw-product-highlight' ),
							'spec_value' => esc_html__( '32', 'ibaw-product-highlight' ),
							'spec_icon' => ['value' => 'fas fa-arrows-alt-h', 'library' => 'solid'],
						],
					],
					'title_field' => '{{{ spec_label }}}',
				]
			);

			$this->end_controls_section();

			// --- Button Section ---
			$this->start_controls_section(
				'button_section',
				[
					'label' => esc_html__( 'Action Button', 'ibaw-product-highlight' ),
					'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			$this->add_control(
				'button_text',
				[
					'label' => esc_html__( 'Button Text', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => esc_html__( 'SHOP NOW', 'ibaw-product-highlight' ),
				]
			);

			$this->add_control(
				'button_link',
				[
					'label' => esc_html__( 'Button Link', 'ibaw-product-highlight' ),
					'type' => \Elementor\Controls_Manager::URL,
					'placeholder' => esc_html__( 'https://your-link.com', 'ibaw-product-highlight' ),
					'default' => [
						'url' => '#',
					],
				]
			);

			$this->end_controls_section();
		}

		protected function render() {
			$settings = $this->get_settings_for_display();
			
			// Handle Image
			$image_url = ! empty( $settings['product_image']['url'] ) ? esc_url( $settings['product_image']['url'] ) : '';
			
			// Handle Button Link
			$this->add_link_attributes( 'button_link', $settings['button_link'] );
			
			?>
			<div class="ibaw-ph-container">
				
				<!-- Column 1: Image -->
				<div class="ibaw-ph-image-col">
					<?php if ( $image_url ) : ?>
						<img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr( $settings['product_title'] ); ?>">
					<?php endif; ?>
				</div>

				<!-- Column 2: Details -->
				<div class="ibaw-ph-details-col">
					<h2 class="ibaw-ph-title"><?php echo wp_kses_post( $settings['product_title'] ); ?></h2>
					
					<div class="ibaw-ph-price-rating-row">
						<div class="ibaw-ph-price"><?php echo esc_html( $settings['product_price'] ); ?></div>
						<div class="ibaw-ph-rating-wrap">
							<div class="ibaw-ph-stars">
								<?php
								$rating = (float) $settings['review_stars'];
								for ( $i = 1; $i <= 5; $i++ ) {
									if ( $rating >= $i ) {
										echo '<i class="fas fa-star"></i>';
									} elseif ( $rating >= $i - 0.5 ) {
										echo '<i class="fas fa-star-half-alt"></i>';
									} else {
										echo '<i class="far fa-star"></i>';
									}
								}
								?>
							</div>
							<div class="ibaw-ph-review-count"><?php echo esc_html( $settings['review_count_text'] ); ?></div>
						</div>
					</div>

					<div class="ibaw-ph-specs-list">
						<?php foreach ( $settings['specs_list'] as $item ) : ?>
							<div class="ibaw-ph-spec-item">
								<div class="ibaw-ph-spec-icon">
									<?php \Elementor\Icons_Manager::render_icon( $item['spec_icon'], [ 'aria-hidden' => 'true' ] ); ?>
								</div>
								<div class="ibaw-ph-spec-label"><?php echo esc_html( $item['spec_label'] ); ?></div>
								<div class="ibaw-ph-spec-value"><?php echo wp_kses_post( $item['spec_value'] ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Column 3: Button -->
				<div class="ibaw-ph-action-col">
					<a class="ibaw-ph-button" <?php echo $this->get_render_attribute_string( 'button_link' ); ?>>
						<?php echo esc_html( $settings['button_text'] ); ?>
					</a>
				</div>

			</div>

			<style>
				.ibaw-ph-container {
					display: flex;
					align-items: center;
					justify-content: space-between;
					background: #ffffff;
					padding: 20px 0;
					font-family: sans-serif;
				}
				.ibaw-ph-image-col {
					flex: 0 0 25%;
					padding-right: 30px;
					border-right: 1px solid #dcdcdc;
					text-align: center;
				}
				.ibaw-ph-image-col img {
					max-width: 100%;
					height: auto;
				}
				.ibaw-ph-details-col {
					flex: 1;
					padding: 0 40px;
				}
				.ibaw-ph-action-col {
					flex: 0 0 20%;
					padding-left: 30px;
					border-left: 1px solid #dcdcdc;
					text-align: center;
				}
				.ibaw-ph-title {
					font-size: 22px;
					font-weight: 800;
					text-transform: uppercase;
					color: #111;
					margin: 0 0 15px 0;
					line-height: 1.2;
					letter-spacing: 0.5px;
				}
				.ibaw-ph-price-rating-row {
					display: flex;
					justify-content: space-between;
					align-items: center;
					margin-bottom: 25px;
				}
				.ibaw-ph-price {
					color: #cc0000;
					font-size: 20px;
					font-weight: 700;
				}
				.ibaw-ph-rating-wrap {
					display: flex;
					align-items: center;
					gap: 8px;
				}
				.ibaw-ph-stars i {
					color: #ffc107;
					font-size: 16px;
				}
				.ibaw-ph-review-count {
					font-size: 14px;
					color: #333;
				}
				.ibaw-ph-spec-item {
					display: flex;
					align-items: flex-start;
					margin-bottom: 12px;
				}
				.ibaw-ph-spec-icon {
					flex: 0 0 30px;
					color: #888;
					font-size: 20px;
					text-align: center;
				}
				.ibaw-ph-spec-icon svg {
					width: 20px;
					height: 20px;
					fill: currentColor;
				}
				.ibaw-ph-spec-label {
					flex: 0 0 160px;
					color: #888;
					font-weight: 700;
					font-size: 14px;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}
				.ibaw-ph-spec-value {
					flex: 1;
					color: #000;
					font-weight: 600;
					font-size: 15px;
					line-height: 1.4;
				}
				.ibaw-ph-button {
					display: inline-block;
					background-color: #ba1919;
					color: #ffffff !important;
					padding: 15px 30px;
					font-size: 16px;
					font-weight: 700;
					text-transform: uppercase;
					text-decoration: none;
					transition: background-color 0.3s ease;
					cursor: pointer;
				}
				.ibaw-ph-button:hover {
					background-color: #8f1212;
				}

				/* Mobile Responsiveness */
				@media (max-width: 992px) {
					.ibaw-ph-container {
						flex-direction: column;
						border: 1px solid #dcdcdc;
						padding: 30px;
					}
					.ibaw-ph-image-col, .ibaw-ph-action-col {
						flex: 1 1 auto;
						width: 100%;
						border: none;
						padding: 0;
					}
					.ibaw-ph-details-col {
						padding: 20px 0;
					}
					.ibaw-ph-action-col {
						padding-top: 20px;
						border-top: 1px solid #dcdcdc;
					}
					.ibaw-ph-spec-label {
						flex: 0 0 130px;
					}
				}
				@media (max-width: 768px) {
					.ibaw-ph-price-rating-row {
						flex-direction: column;
						align-items: flex-start;
						gap: 10px;
					}
					.ibaw-ph-spec-item {
						flex-direction: column;
					}
					.ibaw-ph-spec-icon {
						display: none; /* Hide icons on very small screens to save space */
					}
					.ibaw-ph-spec-label {
						flex: 1 1 auto;
						margin-bottom: 4px;
					}
				}
			</style>
			<?php
		}
	}

	$widgets_manager->register( new IBAW_Product_Highlight_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_product_highlight_widget' );