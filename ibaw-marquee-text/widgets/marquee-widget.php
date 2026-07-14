<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class IBAW_Marquee_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ibaw_marquee';
	}

	public function get_title() {
		return esc_html__( 'IBAW Marquee Text', 'ibaw-marquee' );
	}

	public function get_icon() {
		return 'eicon-text-align-center';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_style_depends() {
		return [ 'ibaw-marquee-style' ];
	}

	protected function register_controls() {

		// Content Tab
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Marquee Items', 'ibaw-marquee' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'text',
			[
				'label' => esc_html__( 'Text', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Marquee Item', 'ibaw-marquee' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'link',
			[
				'label' => esc_html__( 'Link', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'ibaw-marquee' ),
				'default' => [
					'url' => '',
				],
			]
		);

		$this->add_control(
			'marquee_items',
			[
				'label' => esc_html__( 'Items', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[ 'text' => esc_html__( 'Breaking News Update!', 'ibaw-marquee' ) ],
					[ 'text' => esc_html__( 'Click here for the latest offers.', 'ibaw-marquee' ) ],
					[ 'text' => esc_html__( 'New products dropping soon.', 'ibaw-marquee' ) ],
				],
				'title_field' => '{{{ text }}}',
			]
		);

		$this->end_controls_section();

		// Settings Tab
		$this->start_controls_section(
			'settings_section',
			[
				'label' => esc_html__( 'Settings', 'ibaw-marquee' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'direction',
			[
				'label' => esc_html__( 'Direction', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left' => esc_html__( 'Left', 'ibaw-marquee' ),
					'right' => esc_html__( 'Right', 'ibaw-marquee' ),
				],
			]
		);

		$this->add_control(
			'speed',
			[
				'label' => esc_html__( 'Animation Speed (Seconds)', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 's' ],
				'range' => [
					's' => [
						'min' => 5,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 's',
					'size' => 20,
				],
				'selectors' => [
					'{{WRAPPER}} .ibaw-marquee-track' => 'animation-duration: {{SIZE}}s;',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label' => esc_html__( 'Pause on Hover', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'ibaw-marquee' ),
				'label_off' => esc_html__( 'No', 'ibaw-marquee' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_responsive_control(
			'gap',
			[
				'label' => esc_html__( 'Gap Between Items', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', '%' ],
				'range' => [
					'px' => [ 'min' => 0, 'max' => 200 ],
				],
				'default' => [
					'unit' => 'px',
					'size' => 50,
				],
				'selectors' => [
					'{{WRAPPER}} .ibaw-marquee-item-wrapper' => 'padding-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'style_section',
			[
				'label' => esc_html__( 'Style', 'ibaw-marquee' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_color',
			[
				'label' => esc_html__( 'Text Color', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ibaw-marquee-item' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ibaw-marquee-link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'hover_color',
			[
				'label' => esc_html__( 'Hover Color', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ibaw-marquee-link:hover .ibaw-marquee-item' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ibaw-marquee-link:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'typography',
				'selector' => '{{WRAPPER}} .ibaw-marquee-item',
			]
		);

		$this->add_control(
			'background_color',
			[
				'label' => esc_html__( 'Background Color', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ibaw-marquee-wrapper' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'padding',
			[
				'label' => esc_html__( 'Container Padding', 'ibaw-marquee' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ibaw-marquee-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['marquee_items'] ) ) {
			return;
		}

		$direction_class = 'ibaw-marquee-' . esc_attr( $settings['direction'] );
		$pause_class = ( 'yes' === $settings['pause_on_hover'] ) ? 'ibaw-pause-hover' : '';

		$items_html = '';
		
		foreach ( $settings['marquee_items'] as $index => $item ) {
			$repeater_setting_key = $this->get_repeater_setting_key( 'text', 'marquee_items', $index );
			$this->add_render_attribute( $repeater_setting_key, 'class', 'ibaw-marquee-item' );

			$text_element = '<span ' . $this->get_render_attribute_string( $repeater_setting_key ) . '>' . esc_html( $item['text'] ) . '</span>';
			
			// Build the link if URL exists
			if ( ! empty( $item['link']['url'] ) ) {
				$link_key = 'link_' . $index;
				$this->add_link_attributes( $link_key, $item['link'] );
				$this->add_render_attribute( $link_key, 'class', 'ibaw-marquee-link' );
				
				$final_item = '<a ' . $this->get_render_attribute_string( $link_key ) . '>' . $text_element . '</a>';
			} else {
				$final_item = $text_element;
			}

			// Wrap each item in a container to manage the gap safely
			$items_html .= '<div class="ibaw-marquee-item-wrapper">' . $final_item . '</div>';
		}
		?>
		<div class="ibaw-marquee-wrapper">
			<div class="ibaw-marquee-track <?php echo esc_attr( $direction_class ); ?> <?php echo esc_attr( $pause_class ); ?>">
				<div class="ibaw-marquee-content">
					<?php echo wp_kses_post( $items_html ); ?>
				</div>
				<!-- Duplicated content allows infinite seamless scrolling via CSS -->
				<div class="ibaw-marquee-content" aria-hidden="true">
					<?php echo wp_kses_post( $items_html ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}