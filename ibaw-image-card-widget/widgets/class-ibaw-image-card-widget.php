<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Image_Size;

class IBAW_Image_Card_Widget extends Widget_Base {

	public function get_name() {
		return 'ibaw_image_card_widget';
	}

	public function get_title() {
		return esc_html__( 'IBAW Image Card Button', 'ibaw-image-card' );
	}

	public function get_icon() {
		return 'eicon-image-box';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	protected function register_controls() {

		// ----------------------------------------------------
		// CONTENT TAB
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Card Content', 'ibaw-image-card' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'ibaw-image-card' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'ULTIMATE POWER FOR TOUGH JOBS', 'ibaw-image-card' ),
				'placeholder' => esc_html__( 'Enter card title', 'ibaw-image-card' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'image',
			[
				'label'   => esc_html__( 'Foreground Overlapping Image', 'ibaw-image-card' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'image',
				'default' => 'large',
			]
		);

		$this->add_control(
			'bg_overlay_image',
			[
				'label'   => esc_html__( 'Background Overlay Image', 'ibaw-image-card' ),
				'type'    => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Faded photo displayed behind the text inside the card.', 'ibaw-image-card' ),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => esc_html__( 'Button Text', 'ibaw-image-card' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Learn more', 'ibaw-image-card' ),
				'placeholder' => esc_html__( 'Enter button text', 'ibaw-image-card' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'button_link',
			[
				'label'       => esc_html__( 'Link', 'ibaw-image-card' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'ibaw-image-card' ),
				'default'     => [
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => false,
				],
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// STYLE TAB: CARD BACKDROP
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => esc_html__( 'Card Background', 'ibaw-image-card' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'card_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'ibaw-image-card' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5b7dbd',
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-bg' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_max_width',
			[
				'label'      => esc_html__( 'Card Max Width (px)', 'ibaw-image-card' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 200, 'max' => 1000 ],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 350,
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-container' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_height',
			[
				'label'      => esc_html__( 'Card Height (px)', 'ibaw-image-card' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 150, 'max' => 800 ],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 450,
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-bg' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'ibaw-image-card' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'      => '60',
					'right'    => '60',
					'bottom'   => '60',
					'left'     => '60',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-bg' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => esc_html__( 'Padding', 'ibaw-image-card' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'    => '50',
					'right'  => '25',
					'bottom' => '20',
					'left'   => '25',
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-bg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// STYLE TAB: BACKGROUND OVERLAY IMAGE
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_style_bg_overlay',
			[
				'label' => esc_html__( 'Background Image Overlay', 'ibaw-image-card' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'overlay_opacity',
			[
				'label'     => esc_html__( 'Opacity', 'ibaw-image-card' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'alpha' ],
				'range'     => [
					'alpha' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ],
				],
				'default'   => [
					'unit' => 'alpha',
					'size' => 0.15,
				],
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-bg-overlay' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'overlay_blend_mode',
			[
				'label'     => esc_html__( 'Blend Mode', 'ibaw-image-card' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'normal',
				'options'   => [
					'normal'      => esc_html__( 'Normal', 'ibaw-image-card' ),
					'multiply'    => esc_html__( 'Multiply', 'ibaw-image-card' ),
					'screen'      => esc_html__( 'Screen', 'ibaw-image-card' ),
					'overlay'     => esc_html__( 'Overlay', 'ibaw-image-card' ),
					'darken'      => esc_html__( 'Darken', 'ibaw-image-card' ),
					'lighten'     => esc_html__( 'Lighten', 'ibaw-image-card' ),
					'color-dodge' => esc_html__( 'Color Dodge', 'ibaw-image-card' ),
					'luminosity'  => esc_html__( 'Luminosity', 'ibaw-image-card' ),
				],
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-bg-overlay' => 'mix-blend-mode: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'overlay_position',
			[
				'label'     => esc_html__( 'Position', 'ibaw-image-card' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => [
					'center center' => esc_html__( 'Center Center', 'ibaw-image-card' ),
					'center top'    => esc_html__( 'Center Top', 'ibaw-image-card' ),
					'center bottom' => esc_html__( 'Center Bottom', 'ibaw-image-card' ),
					'left top'      => esc_html__( 'Left Top', 'ibaw-image-card' ),
					'right top'     => esc_html__( 'Right Top', 'ibaw-image-card' ),
				],
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-bg-overlay' => 'background-position: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// STYLE TAB: TITLE
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__( 'Title Typography', 'ibaw-image-card' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'ibaw-image-card' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ibaw-card-title',
			]
		);

		$this->add_responsive_control(
			'title_margin_bottom',
			[
				'label'      => esc_html__( 'Margin Bottom', 'ibaw-image-card' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// STYLE TAB: IMAGE POSITIONING
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Image Layout', 'ibaw-image-card' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_width',
			[
				'label'      => esc_html__( 'Width', 'ibaw-image-card' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'range'      => [
					'%'  => [ 'min' => 10, 'max' => 180 ],
					'px' => [ 'min' => 100, 'max' => 800 ],
				],
				'default'    => [
					'unit' => '%',
					'size' => 110,
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-img-wrapper img' => 'width: {{SIZE}}{{UNIT}}; max-width: none;',
				],
			]
		);

		$this->add_responsive_control(
			'image_y_offset',
			[
				'label'      => esc_html__( 'Vertical Overlap Offset (px)', 'ibaw-image-card' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => -300, 'max' => 300 ],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 50,
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-img-wrapper' => 'transform: translateY({{SIZE}}{{UNIT}});',
				],
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// STYLE TAB: BUTTON
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__( 'Button', 'ibaw-image-card' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => esc_html__( 'Text Color', 'ibaw-image-card' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'ibaw-image-card' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5b7dbd',
				'selectors' => [
					'{{WRAPPER}} .ibaw-card-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .ibaw-card-btn',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'ibaw-image-card' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'    => '15',
					'right'  => '40',
					'bottom' => '15',
					'left'   => '40',
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'ibaw-image-card' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top'    => '35',
					'right'  => '35',
					'bottom' => '35',
					'left'   => '35',
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin_top',
			[
				'label'      => esc_html__( 'Spacing Above Button', 'ibaw-image-card' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 300 ],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 90,
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-card-btn-container' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( ! empty( $settings['button_link']['url'] ) ) {
			$this->add_link_attributes( 'button_attr', $settings['button_link'] );
		}
		$this->add_render_attribute( 'button_attr', 'class', 'ibaw-card-btn' );

		$overlay_style = '';
		if ( ! empty( $settings['bg_overlay_image']['url'] ) ) {
			$overlay_style = 'background-image: url(' . esc_url( $settings['bg_overlay_image']['url'] ) . ');';
		}
		?>

		<style>
			.ibaw-card-container {
				display: flex;
				flex-direction: column;
				align-items: center;
				width: 100%;
				margin: 0 auto;
				text-align: center;
				box-sizing: border-box;
			}
			.ibaw-card-bg {
				width: 100%;
				position: relative;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: flex-start;
				box-sizing: border-box;
				overflow: visible;
			}
			.ibaw-card-bg-overlay {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background-size: cover;
				background-repeat: no-repeat;
				pointer-events: none;
				z-index: 1;
				border-radius: inherit; 
			}
			.ibaw-card-content-wrap {
				position: relative;
				z-index: 2;
				width: 100%;
				display: flex;
				flex-direction: column;
				align-items: center;
			}
			.ibaw-card-title {
				font-family: sans-serif;
				font-size: 32px;
				font-weight: 900;
				font-style: italic;
				text-transform: uppercase;
				line-height: 1.15;
				margin: 0;
				word-break: break-word;
				text-shadow: 0px 2px 4px rgba(0,0,0,0.15);
			}
			.ibaw-card-img-wrapper {
				position: relative;
				width: 100%;
				display: flex;
				justify-content: center;
				z-index: 4;
				pointer-events: none;
			}
			.ibaw-card-img-wrapper img {
				height: auto;
				object-fit: contain;
			}
			.ibaw-card-btn-container {
				width: 100%;
				display: flex;
				justify-content: center;
				z-index: 5;
			}
			.ibaw-card-btn {
				display: inline-block;
				text-decoration: none;
				font-family: sans-serif;
				font-weight: 800;
				font-size: 19px;
				transition: background 0.3s ease, transform 0.2s ease;
			}
			.ibaw-card-btn:hover {
				transform: scale(1.04);
				opacity: 0.95;
			}
		</style>

		<div class="ibaw-card-container">
			<div class="ibaw-card-bg">
				<?php if ( ! empty( $settings['bg_overlay_image']['url'] ) ) : ?>
					<div class="ibaw-card-bg-overlay" style="<?php echo $overlay_style; ?>"></div>
				<?php endif; ?>

				<div class="ibaw-card-content-wrap">
					<?php if ( ! empty( $settings['title'] ) ) : ?>
						<h2 class="ibaw-card-title"><?php echo esc_html( $settings['title'] ); ?></h2>
					<?php endif; ?>

					<?php if ( ! empty( $settings['image']['url'] ) ) : ?>
						<div class="ibaw-card-img-wrapper">
							<?php echo Group_Control_Image_Size::get_attachment_image_html( $settings, 'image' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $settings['button_text'] ) ) : ?>
				<div class="ibaw-card-btn-container">
					<a <?php $this->print_render_attribute_string( 'button_attr' ); ?>>
						<?php echo esc_html( $settings['button_text'] ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

		<?php
	}
}