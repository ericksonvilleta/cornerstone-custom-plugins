<?php
/**
 * Plugin Name: IBAW- Features Grid
 * Description: An Elementor addon widget that displays a 3x2 features grid with customizable typography.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.1.0
 * Text Domain: ibaw-features-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the Elementor Widget
 */
function register_ibaw_features_widget( $widgets_manager ) {

	class IBAW_Features_Widget extends \Elementor\Widget_Base {

		public function get_name() {
			return 'ibaw_features_grid';
		}

		public function get_title() {
			return esc_html__( 'IBAW- Features Grid', 'ibaw-features-grid' );
		}

		public function get_icon() {
			return 'eicon-info-box';
		}

		public function get_categories() {
			return [ 'general' ];
		}

		protected function register_controls() {

			// -----------------------------------------------------
			// CONTENT TAB
			// -----------------------------------------------------
			$this->start_controls_section(
				'content_section',
				[
					'label' => esc_html__( 'Features Content', 'ibaw-features-grid' ),
					'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'icon',
				[
					'label' => esc_html__( 'Icon', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::ICONS,
					'default' => [
						'value' => 'fas fa-check',
						'library' => 'solid',
					],
				]
			);

			$repeater->add_control(
				'title',
				[
					'label' => esc_html__( 'Title', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'default' => esc_html__( 'Feature Title' , 'ibaw-features-grid' ),
					'label_block' => true,
				]
			);

			$repeater->add_control(
				'description',
				[
					'label' => esc_html__( 'Description', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::TEXTAREA,
					'default' => esc_html__( 'Feature description goes here.' , 'ibaw-features-grid' ),
					'show_label' => true,
				]
			);

			$this->add_control(
				'features_list',
				[
					'label' => esc_html__( 'Features', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::REPEATER,
					'fields' => $repeater->get_controls(),
					'default' => [
						[
							'icon' => ['value' => 'far fa-file-alt', 'library' => 'regular'],
							'title' => esc_html__( 'No Permits. No Hassle.', 'ibaw-features-grid' ),
							'description' => esc_html__( 'Portable decks typically don\'t require permits or inspections, saving you time, paperwork, and unexpected delays before enjoying your outdoor space.', 'ibaw-features-grid' ),
						],
						[
							'icon' => ['value' => 'fas fa-stopwatch', 'library' => 'solid'],
							'title' => esc_html__( 'Faster Installation.', 'ibaw-features-grid' ),
							'description' => esc_html__( 'Traditional decks can take weeks of construction. Portable decks are installed quickly, so you can start relaxing and entertaining sooner.', 'ibaw-features-grid' ),
						],
						[
							'icon' => ['value' => 'fas fa-shield-alt', 'library' => 'solid'],
							'title' => esc_html__( 'Protect Your Property.', 'ibaw-features-grid' ),
							'description' => esc_html__( 'Portable decks sit above the ground, helping reduce digging, soil disruption, and long-term impact on your yard or property.', 'ibaw-features-grid' ),
						],
						[
							'icon' => ['value' => 'fas fa-coins', 'library' => 'solid'],
							'title' => esc_html__( 'Lower Overall Cost.', 'ibaw-features-grid' ),
							'description' => esc_html__( 'Without extensive labor, permits, and foundations, portable decks are often more affordable than conventional builds while still providing a beautiful outdoor space.', 'ibaw-features-grid' ),
						],
						[
							'icon' => ['value' => 'fas fa-puzzle-piece', 'library' => 'solid'],
							'title' => esc_html__( 'Flexible & Expandable.', 'ibaw-features-grid' ),
							'description' => esc_html__( 'Your outdoor needs can change. Portable decks make it easy to reposition, expand, or adjust your layout without a full rebuild.', 'ibaw-features-grid' ),
						],
						[
							'icon' => ['value' => 'fas fa-truck-moving', 'library' => 'solid'],
							'title' => esc_html__( 'Move It When You Move.', 'ibaw-features-grid' ),
							'description' => esc_html__( 'Unlike permanent decks, portable decks can move with you. If you relocate, your investment goes with you instead of staying behind.', 'ibaw-features-grid' ),
						],
					],
					'title_field' => '{{{ title }}}',
				]
			);

			$this->end_controls_section();

			// -----------------------------------------------------
			// STYLE TAB - ICON
			// -----------------------------------------------------
			$this->start_controls_section(
				'style_icon_section',
				[
					'label' => esc_html__( 'Icon Style', 'ibaw-features-grid' ),
					'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				]
			);

			$this->add_control(
				'icon_bg_color',
				[
					'label' => esc_html__( 'Icon Background Color', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ibaw-feature-icon-wrapper' => 'background-color: {{VALUE}};',
					],
					'default' => '#93c54b', 
				]
			);

			$this->add_control(
				'icon_color',
				[
					'label' => esc_html__( 'Icon Color', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ibaw-feature-icon-wrapper i' => 'color: {{VALUE}};',
						'{{WRAPPER}} .ibaw-feature-icon-wrapper svg' => 'fill: {{VALUE}};',
					],
					'default' => '#ffffff', 
				]
			);

			$this->end_controls_section();

			// -----------------------------------------------------
			// STYLE TAB - TITLE
			// -----------------------------------------------------
			$this->start_controls_section(
				'style_title_section',
				[
					'label' => esc_html__( 'Title Style', 'ibaw-features-grid' ),
					'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				]
			);

			$this->add_control(
				'title_color',
				[
					'label' => esc_html__( 'Title Color', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ibaw-feature-title' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name' => 'title_typography',
					'selector' => '{{WRAPPER}} .ibaw-feature-title',
				]
			);

			$this->end_controls_section();

			// -----------------------------------------------------
			// STYLE TAB - DESCRIPTION
			// -----------------------------------------------------
			$this->start_controls_section(
				'style_desc_section',
				[
					'label' => esc_html__( 'Description Style', 'ibaw-features-grid' ),
					'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				]
			);

			$this->add_control(
				'desc_color',
				[
					'label' => esc_html__( 'Description Color', 'ibaw-features-grid' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .ibaw-feature-desc' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'name' => 'desc_typography',
					'selector' => '{{WRAPPER}} .ibaw-feature-desc',
				]
			);

			$this->end_controls_section();
		}

		protected function render() {
			$settings = $this->get_settings_for_display();

			if ( empty( $settings['features_list'] ) ) {
				return;
			}
			?>
			
			<div class="ibaw-features-grid-container">
				<?php foreach ( $settings['features_list'] as $item ) : ?>
					<div class="ibaw-feature-item">
						
						<?php if ( ! empty( $item['icon']['value'] ) ) : ?>
							<div class="ibaw-feature-icon-wrapper">
								<?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
							</div>
						<?php endif; ?>
						
						<?php if ( ! empty( $item['title'] ) ) : ?>
							<h3 class="ibaw-feature-title"><?php echo esc_html( $item['title'] ); ?></h3>
						<?php endif; ?>
						
						<?php if ( ! empty( $item['description'] ) ) : ?>
							<p class="ibaw-feature-desc"><?php echo esc_html( $item['description'] ); ?></p>
						<?php endif; ?>
						
					</div>
				<?php endforeach; ?>
			</div>

			<style>
				/* Base Grid Layout */
				.ibaw-features-grid-container {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					column-gap: 40px;
					row-gap: 60px;
					text-align: center;
					font-family: inherit;
				}

				.ibaw-feature-item {
					display: flex;
					flex-direction: column;
					align-items: center;
					padding: 10px;
				}

				/* Icon Styling */
				.ibaw-feature-icon-wrapper {
					display: flex;
					align-items: center;
					justify-content: center;
					width: 80px;
					height: 80px;
					border-radius: 50%;
					font-size: 36px;
					margin-bottom: 25px;
					background-color: #93c54b; /* Default green */
					color: #ffffff;
					box-shadow: inset -5px -5px 15px rgba(0,0,0,0.05);
				}

				/* Typography defaults (Can be overridden by Elementor Typography Controls) */
				.ibaw-feature-title {
					font-size: 22px;
					font-weight: 400;
					color: #333333;
					margin: 0 0 15px 0;
					line-height: 1.2;
				}

				.ibaw-feature-desc {
					font-size: 15px;
					line-height: 1.6;
					color: #666666;
					margin: 0;
				}

				/* Responsive Adjustments */
				@media (max-width: 992px) {
					.ibaw-features-grid-container {
						grid-template-columns: repeat(2, 1fr);
					}
				}

				@media (max-width: 768px) {
					.ibaw-features-grid-container {
						grid-template-columns: 1fr;
					}
				}
			</style>
			<?php
		}
	}

	$widgets_manager->register( new \IBAW_Features_Widget() );
}

add_action( 'elementor/widgets/register', 'register_ibaw_features_widget' );