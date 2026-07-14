<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class IBAW_Glass_Slider_Widget extends \Elementor\Widget_Base {
	public function get_name() { return 'ibaw-glass-slider'; }
	public function get_title() { return esc_html__( 'IBAW Glass Slider', 'ibaw-glass-slider' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	public function get_categories() { return [ 'general' ]; }
	public function get_style_depends() { return [ 'ibaw-glass-slider-style' ]; }
	public function get_script_depends() { return [ 'ibaw-glass-slider-script' ]; }

	protected function register_controls() {
		$this->start_controls_section('content_section', ['label' => esc_html__('Header Content', 'ibaw-glass-slider'), 'tab' => \Elementor\Controls_Manager::TAB_CONTENT]);
		$this->add_control('main_heading', ['label' => esc_html__('Main Heading', 'ibaw-glass-slider'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'A Smarter Alternative To Traditional Decks', 'label_block' => true]);
		$this->add_control('sub_heading', ['label' => esc_html__('Sub Heading', 'ibaw-glass-slider'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Discover why more homeowners are choosing portable decks for faster installation, greater flexibility, and long-term value.']);
		$this->end_controls_section();

		$this->start_controls_section('cards_section', ['label' => esc_html__('Cards', 'ibaw-glass-slider'), 'tab' => \Elementor\Controls_Manager::TAB_CONTENT]);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control('card_icon_image', ['label' => esc_html__('Icon (WebP/SVG/PNG)', 'ibaw-glass-slider'), 'type' => \Elementor\Controls_Manager::MEDIA]);
		$repeater->add_control('card_title', ['label' => esc_html__('Title', 'ibaw-glass-slider'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Card Title']);
		$repeater->add_control('card_title_highlight', ['label' => esc_html__('Highlight Title?', 'ibaw-glass-slider'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes']);
		$repeater->add_control('card_content', ['label' => esc_html__('Content', 'ibaw-glass-slider'), 'type' => \Elementor\Controls_Manager::WYSIWYG, 'default' => 'Card Content']);
		$this->add_control('cards_list', ['type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ card_title }}}']);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="ibaw-glass-slider-wrapper">
			<div class="ibaw-glass-header">
				<h2 class="ibaw-glass-heading"><?php echo esc_html($settings['main_heading']); ?></h2>
				<p class="ibaw-glass-subheading"><?php echo esc_html($settings['sub_heading']); ?></p>
			</div>
			<div class="ibaw-glass-carousel-container">
				<div class="ibaw-glass-track">
					<?php foreach ($settings['cards_list'] as $item) : ?>
						<div class="ibaw-glass-card">
							<div class="ibaw-glass-card-icon">
								<?php if (!empty($item['card_icon_image']['url'])) echo '<img src="' . esc_url($item['card_icon_image']['url']) . '" alt="icon" />'; ?>
							</div>
							<h3 class="ibaw-glass-card-title <?php echo ('yes' === $item['card_title_highlight']) ? 'has-highlight' : ''; ?>">
								<span><?php echo esc_html($item['card_title']); ?></span>
							</h3>
							<div class="ibaw-glass-card-content"><?php echo wp_kses_post($item['card_content']); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="ibaw-glass-nav">
					<button class="ibaw-glass-prev" aria-label="Previous"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg></button>
					<button class="ibaw-glass-next" aria-label="Next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
				</div>
			</div>
		</div>
		<?php
	}
}