<?php
namespace IBAW\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class IBAW_Tractor_Slider_Widget extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'ibaw_tractor_slider';
	}

	public function get_title(): string {
		return esc_html__( 'IBAW Tractor Metrics Slider', 'ibaw-plugin' );
	}

	public function get_icon(): string {
		return 'eicon-slider-album';
	}

	public function get_categories(): array {
		return [ 'general' ];
	}

	public function get_style_depends(): array {
		wp_register_style(
			'ibaw-tractor-slider-style',
			plugins_url( '../assets/css/slider.css', __FILE__ ),
			[],
			'1.0.0'
		);
		return [ 'ibaw-tractor-slider-style' ];
	}

	protected function register_controls(): void {
		
		// ----------------------------------------------------
		// SECTION 1: CONTENT TAB
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_slider',
			[
				'label' => esc_html__( 'Slider Slides Content', 'ibaw-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'model_name',
			[
				'label'       => esc_html__( 'Model Name Label', 'ibaw-plugin' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'MT232E',
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'bg_text',
			[
				'label'   => esc_html__( 'Background Large Watermark', 'ibaw-plugin' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'MT232E',
			]
		);

		$repeater->add_control(
			'tractor_image',
			[
				'label'   => esc_html__( 'Product Display Image', 'ibaw-plugin' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		/* Spec loop */
		for ( $m = 1; $m <= 5; $m++ ) {
			$repeater->add_control(
				"spec_label_{$m}",
				[
					'label'     => sprintf( esc_html__( 'Metric Spec #%d Name', 'ibaw-plugin' ), $m ),
					'type'      => \Elementor\Controls_Manager::TEXT,
					'default'   => 'ENGINE',
				]
			);

			$repeater->add_control(
				"spec_value_{$m}",
				[
					'label'     => sprintf( esc_html__( 'Metric Spec #%d Value', 'ibaw-plugin' ), $m ),
					'type'      => \Elementor\Controls_Manager::TEXT,
					'default'   => '31.7',
				]
			);

			$repeater->add_control(
				"spec_suffix_{$m}",
				[
					'label'     => sprintf( esc_html__( 'Metric Spec #%d Suffix', 'ibaw-plugin' ), $m ),
					'type'      => \Elementor\Controls_Manager::TEXT,
					'default'   => 'hp',
				]
			);
		}

		$repeater->add_control(
			'video_thumbnail',
			[
				'label'       => esc_html__( 'Video Background Thumbnail', 'ibaw-plugin' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'default'     => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'video_url',
			[
				'label'       => esc_html__( 'YouTube Video Link URL', 'ibaw-plugin' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://www.youtube.com/watch?v=...', 'ibaw-plugin' ),
				'default'     => [
					'url'         => '',
					'is_external' => true,
					'nofollow'    => true,
				],
			]
		);

		$this->add_control(
			'slides_dataset',
			[
				'label'       => esc_html__( 'Tractor Model Slides Collection', 'ibaw-plugin' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_fields(),
				'default'     => [
					[ 'model_name' => 'MT232E', 'bg_text' => 'MT232E' ],
				],
				'title_field' => '{{{ model_name }}}',
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// SECTION 2: SLIDER CONTROL MODULE SETTINGS
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_slider_settings',
			[
				'label' => esc_html__( 'Slider Settings Engine', 'ibaw-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => esc_html__( 'Autoplay Continuous Loop', 'ibaw-plugin' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'ibaw-plugin' ),
				'label_off'    => esc_html__( 'Off', 'ibaw-plugin' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'     => esc_html__( 'Autoplay Speed Delay (ms)', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1500,
				'max'       => 15000,
				'step'      => 500,
				'default'   => 5000,
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => esc_html__( 'Pause Slide Cycle on Mouse Hover', 'ibaw-plugin' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ibaw-plugin' ),
				'label_off'    => esc_html__( 'No', 'ibaw-plugin' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// SECTION 3: STYLE TAB - CONFIGURATION OVERRIDES
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__( 'General Container Layout Customization', 'ibaw-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'slider_border_radius',
			[
				'label'      => esc_html__( 'Component Box Border Radius', 'ibaw-plugin' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50, 'step' => 1 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .ibaw-slider-component' => '--ibaw-slider-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_stage',
			[
				'label' => esc_html__( 'Watermark & Image Stage Customization', 'ibaw-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'watermark_typography',
				'label'    => esc_html__( 'Watermark Font Style Settings', 'ibaw-plugin' ),
				'selector' => '{{WRAPPER}} .ibaw-background-watermark-text',
			]
		);

		$this->add_control(
			'watermark_color',
			[
				'label'     => esc_html__( 'Watermark Accent Overlay Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.06)',
				'selectors' => [
					'{{WRAPPER}} .ibaw-background-watermark-text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_metrics_bar',
			[
				'label' => esc_html__( 'Metrics Data Row Style Customization', 'ibaw-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'metrics_bg_color',
			[
				'label'     => esc_html__( 'Metrics Panel Row Fill Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1e293b',
				'selectors' => [
					'{{WRAPPER}} .ibaw-metrics-spec-grid' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'metrics_divider_color',
			[
				'label'     => esc_html__( 'Cell Grid Column Inner Dividers Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.08)',
				'selectors' => [
					'{{WRAPPER}} .ibaw-metric-cell' => 'border-right-color: {{VALUE}}; border-bottom-color: {{VALUE}}; border-top-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'metrics_label_color',
			[
				'label'     => esc_html__( 'Labels Color (e.g. ENGINE)', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#94a3b8',
				'selectors' => [
					'{{WRAPPER}} .ibaw-metric-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'metrics_value_color',
			[
				'label'     => esc_html__( 'Numerical Counters Output Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .ibaw-metric-value-group' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'metrics_label_typography',
				'label'    => esc_html__( 'Labels Font Style Customization', 'ibaw-plugin' ),
				'selector' => '{{WRAPPER}} .ibaw-metric-label',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'metrics_value_typography',
				'label'    => esc_html__( 'Counters Font Style Customization', 'ibaw-plugin' ),
				'selector' => '{{WRAPPER}} .ibaw-metric-value-group',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_navigation_bar',
			[
				'label' => esc_html__( 'Navigation Controls Style Customization', 'ibaw-plugin' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'nav_tray_bg_color',
			[
				'label'     => esc_html__( 'Navigation Bottom Tray Background Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .ibaw-navigation-control-tray' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'nav_active_color',
			[
				'label'     => esc_html__( 'Active Status State Highlight Accent Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#002244',
				'selectors' => [
					'{{WRAPPER}} .ibaw-nav-thumbnail-card.is-active' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ibaw-nav-thumbnail-card.is-active .ibaw-thumbnail-label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ibaw-nav-dot.is-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'nav_inactive_color',
			[
				'label'     => esc_html__( 'Inactive Base Navigation Items Color', 'ibaw-plugin' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#cbd5e1',
				'selectors' => [
					'{{WRAPPER}} .ibaw-nav-thumbnail-card' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ibaw-thumbnail-label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ibaw-nav-dot' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$slides = $settings['slides_dataset'];

		if ( empty( $slides ) ) return;

		$unique_id = 'ibaw-slider-' . $this->get_id();
		
		// Configure operational metadata flags for presentation engine
		$slider_control_config = [
			'autoplay'     => ( 'yes' === $settings['autoplay'] ),
			'delay'        => intval( $settings['autoplay_speed'] ),
			'pauseOnHover' => ( 'yes' === $settings['pause_on_hover'] )
		];
		?>

		<div id="<?php echo esc_attr( $unique_id ); ?>" class="ibaw-slider-component" data-engine-loop='<?php echo wp_json_encode( $slider_control_config ); ?>'>
			
			<!-- Presentation Frame Window Viewport Container -->
			<div class="ibaw-viewport-window">
				<div class="ibaw-background-watermark-text" id="<?php echo $unique_id; ?>-bgtext"></div>
				<div class="ibaw-asset-stage">
					<img src="" alt="" class="ibaw-foreground-product-image" id="<?php echo $unique_id; ?>-img">
				</div>
			</div>

			<!-- Dynamic Specifications Metric Matrix Grid row -->
			<div class="ibaw-metrics-spec-grid" id="<?php echo $unique_id; ?>-metrics">
				<!-- Injected layout components target generated dynamically -->
			</div>

			<!-- Interactive Multi-Mode Controller Interface Tray -->
			<div class="ibaw-navigation-control-tray">
				
				<!-- Desktop Form Layout Frame (Thumbnail Strip Cards Grid Layout) -->
				<div class="ibaw-thumbnail-strips-wrapper">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<div class="ibaw-nav-thumbnail-card <?php echo ( 0 === $index ) ? 'is-active' : ''; ?>" data-slide-target="<?php echo $index; ?>">
							<img src="<?php echo esc_url( $slide['tractor_image']['url'] ); ?>" alt="<?php echo esc_attr( $slide['model_name'] ); ?>">
							<span class="ibaw-thumbnail-label"><?php echo esc_html( $slide['model_name'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Mobile Form Layout Frame (Responsive Navigation Dot buttons) -->
				<div class="ibaw-dot-indicators-wrapper">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<button class="ibaw-nav-dot <?php echo ( 0 === $index ) ? 'is-active' : ''; ?>" data-slide-target="<?php echo $index; ?>" aria-label="Go to slide <?php echo $index + 1; ?>"></button>
					<?php endforeach; ?>
				</div>

			</div>
		</div>

		<!-- Automated Client Engine Loop Logic Runtime Script Broker -->
		<script type="text/javascript">
		(function() {
			const data = <?php echo wp_json_encode( $slides ); ?>;
			const widgetContainer = document.getElementById('<?php echo $unique_id; ?>');
			
			if (!widgetContainer || !data.length) return;

			// Extract slider control configurations from instance attribute nodes
			const controlLoop = JSON.parse(widgetContainer.getAttribute('data-engine-loop') || '{}');

			const bgTextNode = document.getElementById('<?php echo $unique_id; ?>-bgtext');
			const imgNode = document.getElementById('<?php echo $unique_id; ?>-img');
			const metricsGridNode = document.getElementById('<?php echo $unique_id; ?>-metrics');

			let currentIndex = 0;
			let intervalEngine = null;

			function updateSlide(index) {
				currentIndex = index;
				const activeSlide = data[currentIndex];
				
				// Apply smooth image entrance logic hooks
				imgNode.classList.remove('is-visible');
				
				setTimeout(() => {
					bgTextNode.textContent = activeSlide.bg_text;
					imgNode.src = activeSlide.tractor_image.url;
					imgNode.alt = activeSlide.model_name;
					imgNode.classList.add('is-visible');
				}, 100);

				let metricsHTML = '';
				
				// Compile rows 1 to 5 dynamically
				for (let m = 1; m <= 5; m++) {
					const label = activeSlide['spec_label_' + m];
					const val = activeSlide['spec_value_' + m];
					const suffix = activeSlide['spec_suffix_' + m];

					if (label || val) {
						metricsHTML += `
							<div class="ibaw-metric-cell">
								<div class="ibaw-metric-label">${label}</div>
								<div class="ibaw-metric-value-group">
									<span class="ibaw-metric-counter">${val}</span>
									<span class="ibaw-metric-suffix"> ${suffix}</span>
								</div>
							</div>
						`;
					}
				}

				// Map Video thumbnail to inline grid column item slot #6
				const videoThumbUrl = activeSlide.video_thumbnail ? activeSlide.video_thumbnail.url : '';
				const targetVideoUrl = activeSlide.video_url ? activeSlide.video_url.url : '#';
				
				metricsHTML += `
					<div class="ibaw-metric-cell ibaw-video-slot" style="padding: 0;">
						<a href="${targetVideoUrl}" target="_blank" rel="noopener" class="ibaw-metric-video-link" aria-label="Watch tractor demonstration video">
							<div class="ibaw-video-thumbnail-container">
								<img src="${videoThumbUrl}" class="ibaw-video-bg-img" alt="Video presentation preview frame" />
								<div class="ibaw-video-overlay-play">
									<svg class="ibaw-play-icon-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
										<circle cx="32" cy="32" r="30" fill="#D0021B" stroke="#FFFFFF" stroke-width="3"/>
										<path d="M26 20V44L44 32L26 20Z" fill="#FFFFFF"/>
									</svg>
								</div>
							</div>
						</a>
					</div>
				`;

				metricsGridNode.innerHTML = metricsHTML;

				// Toggle active tracking classes over matching data elements
				widgetContainer.querySelectorAll('[data-slide-target]').forEach(node => {
					if (parseInt(node.getAttribute('data-slide-target')) === currentIndex) {
						node.classList.add('is-active');
					} else {
						node.classList.remove('is-active');
					}
				});
			}

			// Automated loop scheduling controller engine logic loops
			function startAutoplayCycle() {
				if (!controlLoop.autoplay || intervalEngine) return;
				intervalEngine = setInterval(() => {
					let nextIndex = (currentIndex + 1) % data.length;
					updateSlide(nextIndex);
				}, controlLoop.delay || 5000);
			}

			function clearAutoplayCycle() {
				if (intervalEngine) {
					clearInterval(intervalEngine);
					intervalEngine = null;
				}
			}

			// Interactivity listener engine mappings
			widgetContainer.addEventListener('click', function(e) {
				const trigger = e.target.closest('[data-slide-target]');
				if (!trigger) return;
				
				const targetIndex = parseInt(trigger.getAttribute('data-slide-target'));
				clearAutoplayCycle();
				updateSlide(targetIndex);
				startAutoplayCycle();
			});

			if (controlLoop.pauseOnHover) {
				widgetContainer.addEventListener('mouseenter', clearAutoplayCycle);
				widgetContainer.addEventListener('mouseleave', startAutoplayCycle);
			}

			// Boot initial slider instance view parameters 
			updateSlide(0);
			startAutoplayCycle();
		})();
		</script>
		<?php
	}
}