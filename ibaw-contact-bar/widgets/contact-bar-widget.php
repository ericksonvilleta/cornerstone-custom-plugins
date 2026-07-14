<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class IBAW_Contact_Bar_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ibaw_contact_bar';
	}

	public function get_title() {
		return esc_html__( 'IBAW- Contact Bar', 'ibaw-contact-bar' );
	}

	public function get_icon() {
		return 'eicon-contact-info';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Contact Items', 'ibaw-contact-bar' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'item_icon',
			[
				'label' => esc_html__( 'Icon', 'ibaw-contact-bar' ),
				'type' => \Elementor\Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-map-marker-alt',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'item_title',
			[
				'label' => esc_html__( 'Title', 'ibaw-contact-bar' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Address' , 'ibaw-contact-bar' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'item_description',
			[
				'label' => esc_html__( 'Description', 'ibaw-contact-bar' ),
				'type' => \Elementor\Controls_Manager::WYSIWYG,
				'default' => esc_html__( '154 Wolfcraft Way, Charles Town, WV, United States 25414' , 'ibaw-contact-bar' ),
				'show_label' => false,
			]
		);

		$this->add_control(
			'contact_items',
			[
				'label' => esc_html__( 'Items', 'ibaw-contact-bar' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'item_title' => esc_html__( 'Address', 'ibaw-contact-bar' ),
						'item_description' => '154 Wolfcraft Way, Charles Town,<br>WV, United States 25414',
						'item_icon' => [
							'value' => 'fas fa-map-marker-alt',
							'library' => 'fa-solid',
						],
					],
					[
						'item_title' => esc_html__( 'Phone', 'ibaw-contact-bar' ),
						'item_description' => '304-707-0437',
						'item_icon' => [
							'value' => 'fas fa-phone-alt',
							'library' => 'fa-solid',
						],
					],
					[
						'item_title' => esc_html__( 'Email', 'ibaw-contact-bar' ),
						'item_description' => 'info@cornerstonelandscapesupply.com',
						'item_icon' => [
							'value' => 'far fa-envelope',
							'library' => 'fa-regular',
						],
					],
				],
				'title_field' => '{{{ item_title }}}',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		
		if ( empty( $settings['contact_items'] ) ) {
			return;
		}
		?>
		<style>
			.ibaw-contact-wrapper {
				background-color: #f7f8f9;
				border-radius: 8px;
				padding: 40px;
				display: flex;
				justify-content: space-between;
				align-items: center;
				width: 100%;
				box-sizing: border-box;
			}
			.ibaw-contact-item {
				display: flex;
				align-items: center;
				gap: 20px;
				flex: 1;
				justify-content: center;
				position: relative;
			}
			/* Vertical Dividers */
			.ibaw-contact-item:not(:last-child)::after {
				content: '';
				position: absolute;
				right: 0;
				top: 50%;
				transform: translateY(-50%);
				width: 1px;
				height: 60px;
				background-color: #d8d8d8;
			}
			.ibaw-contact-icon {
				width: 60px;
				height: 60px;
				background-color: #eaf5d8;
				border-radius: 50%;
				display: flex;
				justify-content: center;
				align-items: center;
				flex-shrink: 0;
			}
			.ibaw-contact-icon i, .ibaw-contact-icon svg {
				font-size: 22px;
				color: #000;
				width: 22px;
				height: 22px;
				fill: #000;
			}
			.ibaw-contact-text {
				display: flex;
				flex-direction: column;
				text-align: left;
			}
			.ibaw-contact-title {
				font-size: 18px;
				font-weight: 600;
				color: #000;
				margin-bottom: 5px;
				line-height: 1.2;
				font-family: inherit;
			}
			.ibaw-contact-desc {
				font-size: 15px;
				color: #888;
				line-height: 1.5;
				margin: 0;
				font-family: inherit;
			}
			.ibaw-contact-desc p {
				margin: 0;
				color: inherit;
			}

			/* Mobile Responsiveness */
			@media (max-width: 992px) {
				.ibaw-contact-wrapper {
					flex-direction: column;
					align-items: flex-start;
					padding: 30px;
					gap: 30px;
				}
				.ibaw-contact-item {
					width: 100%;
					justify-content: flex-start;
				}
				.ibaw-contact-item:not(:last-child)::after {
					/* Switch vertical divider to horizontal divider for mobile */
					width: 100%;
					height: 1px;
					right: auto;
					left: 0;
					top: auto;
					bottom: -15px;
					transform: none;
				}
			}
		</style>

		<div class="ibaw-contact-wrapper">
			<?php foreach ( $settings['contact_items'] as $item ) : ?>
				<div class="ibaw-contact-item elementor-repeater-item-<?php echo esc_attr( $item['_id'] ); ?>">
					<div class="ibaw-contact-icon">
						<?php \Elementor\Icons_Manager::render_icon( $item['item_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					</div>
					<div class="ibaw-contact-text">
						<div class="ibaw-contact-title"><?php echo esc_html( $item['item_title'] ); ?></div>
						<div class="ibaw-contact-desc"><?php echo wp_kses_post( $item['item_description'] ); ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}