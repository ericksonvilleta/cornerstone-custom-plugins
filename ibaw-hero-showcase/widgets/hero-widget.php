<?php
if (!defined('ABSPATH')) exit;

class IBAW_Hero_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ibaw_hero_showcase';
    }

    public function get_title() {
        return esc_html__('IBAW Hero Showcase', 'ibaw-hero');
    }

    public function get_icon() {
        return 'eicon-call-to-action';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {

        // ==========================================
        // 1. CONTENT TAB
        // ==========================================
        $this->start_controls_section(
            'general_section',
            [
                'label' => esc_html__('General Settings', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'main_logo',
            [
                'label' => esc_html__('Brand Logo (Top Left)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'items_section',
            [
                'label' => esc_html__('Navigation Items', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'item_title',
            [
                'label' => esc_html__('Title', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Blowers', 'ibaw-hero'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'item_desc',
            [
                'label' => esc_html__('Description', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => esc_html__('Debris doesn\'t stand a chance...', 'ibaw-hero'),
            ]
        );

        $repeater->add_control(
            'item_image',
            [
                'label' => esc_html__('Product Image', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'item_link',
            [
                'label' => esc_html__('Link', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'ibaw-hero'),
                'default' => [
                    'url' => '',
                ],
            ]
        );

        $this->add_control(
            'nav_items',
            [
                'label' => esc_html__('Items', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'item_title' => esc_html__('Blowers', 'ibaw-hero'),
                        'item_desc' => esc_html__('Debris doesn\'t stand a chance. With ergonomic features and some of the most powerful specs ever produced.', 'ibaw-hero'),
                    ],
                    [
                        'item_title' => esc_html__('Chainsaws', 'ibaw-hero'),
                        'item_desc' => esc_html__('Power through the toughest jobs with ease and precision.', 'ibaw-hero'),
                    ],
                    [
                        'item_title' => esc_html__('Mowers', 'ibaw-hero'),
                        'item_desc' => esc_html__('Keep your lawn pristine with our reliable and efficient mowers.', 'ibaw-hero'),
                    ],
                ],
                'title_field' => '{{{ item_title }}}',
            ]
        );

        $this->end_controls_section();


        // ==========================================
        // 2. STYLE TAB
        // ==========================================

        // Master Background & Layout
        $this->start_controls_section(
            'style_section_container',
            [
                'label' => esc_html__('Master Background & Layout', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'master_bg',
                'label' => esc_html__('Master Background', 'ibaw-hero'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .ibaw-hero-container',
            ]
        );

        $this->add_responsive_control(
            'left_column_width',
            [
                'label' => esc_html__('Left Content Width (%)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => ['min' => 10, 'max' => 70],
                ],
                'default' => ['unit' => '%', 'size' => 40],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-container' => '--left-col: {{SIZE}}%;',
                ],
            ]
        );

        $this->add_responsive_control(
            'right_column_width',
            [
                'label' => esc_html__('Right Menu Width (%)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => ['min' => 10, 'max' => 50],
                ],
                'default' => ['unit' => '%', 'size' => 25],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-container' => '--right-col: {{SIZE}}%;',
                ],
            ]
        );

        $this->end_controls_section();

        // Individual Column Colors
        $this->start_controls_section(
            'style_section_columns',
            [
                'label' => esc_html__('Column Overlay Colors', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'left_bg_color',
            [
                'label' => esc_html__('Left Column', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f28b44',
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-left' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'center_bg_color',
            [
                'label' => esc_html__('Center Column', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-center' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'right_bg_color',
            [
                'label' => esc_html__('Right Column', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#d47b39',
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-right' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Product Image Controls
        $this->start_controls_section(
            'style_section_image',
            [
                'label' => esc_html__('Product Image', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_z_index',
            [
                'label' => esc_html__('Image Z-Index', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => -10,
                'max' => 999,
                'step' => 1,
                'default' => 10,
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-center' => 'z-index: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Title Style Section
        $this->start_controls_section(
            'style_section_title',
            [
                'label' => esc_html__('Title Typography', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Text Color', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .ibaw-hero-title',
            ]
        );

        $this->end_controls_section();

        // Description Style Section
        $this->start_controls_section(
            'style_section_desc',
            [
                'label' => esc_html__('Description Typography', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'desc_color',
            [
                'label' => esc_html__('Text Color', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ibaw-hero-desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'desc_typography',
                'selector' => '{{WRAPPER}} .ibaw-hero-desc',
            ]
        );

        $this->end_controls_section();

        // Navigation Menu Style Section
        $this->start_controls_section(
            'style_section_menu',
            [
                'label' => esc_html__('Navigation Menu', 'ibaw-hero'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'menu_typography',
                'selector' => '{{WRAPPER}} .ibaw-nav-item',
            ]
        );

        $this->add_control(
            'menu_transition_speed',
            [
                'label' => esc_html__('Animation Speed (Seconds)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['s'],
                'range' => [
                    's' => ['min' => 0.1, 'max' => 2, 'step' => 0.1],
                ],
                'default' => ['unit' => 's', 'size' => 0.3],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item' => '--transition-speed: {{SIZE}}s;',
                ],
            ]
        );

        $this->start_controls_tabs('menu_style_tabs');

        // Normal State Tab
        $this->start_controls_tab(
            'menu_normal',
            [
                'label' => esc_html__('Normal', 'ibaw-hero'),
            ]
        );

        $this->add_control(
            'menu_color',
            [
                'label' => esc_html__('Text Color', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover / Active State Tab
        $this->start_controls_tab(
            'menu_hover',
            [
                'label' => esc_html__('Hover / Active', 'ibaw-hero'),
            ]
        );

        $this->add_control(
            'menu_hover_color',
            [
                'label' => esc_html__('Text Color', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ibaw-nav-item.ibaw-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_hover_bg',
            [
                'label' => esc_html__('Background Strip Color', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'rgba(255, 255, 255, 0.2)',
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .ibaw-nav-item.ibaw-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_hover_scale',
            [
                'label' => esc_html__('Scale (Zoom)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
                ],
                'default' => ['unit' => '', 'size' => 1.5],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item' => '--hover-scale: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_hover_x',
            [
                'label' => esc_html__('Move X-Axis (Left/Right)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => -100, 'max' => 100],
                ],
                'default' => ['unit' => 'px', 'size' => 0],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item' => '--hover-x: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'menu_hover_y',
            [
                'label' => esc_html__('Move Y-Axis (Up/Down)', 'ibaw-hero'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => -100, 'max' => 100],
                ],
                'default' => ['unit' => 'px', 'size' => 0],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-nav-item' => '--hover-y: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        if (empty($settings['nav_items'])) {
            return;
        }

        $first_item = $settings['nav_items'][0];
        $logo_url = !empty($settings['main_logo']['url']) ? $settings['main_logo']['url'] : '';
        ?>

        <div class="ibaw-hero-container">
            <div class="ibaw-hero-left">
                <div class="ibaw-content-inner">
                    <?php if ($logo_url) : ?>
                        <img class="ibaw-brand-logo" src="<?php echo esc_url($logo_url); ?>" alt="Brand Logo">
                    <?php endif; ?>
                    <div class="ibaw-dynamic-content">
                        <h1 class="ibaw-hero-title"><?php echo wp_kses_post($first_item['item_title']); ?></h1>
                        <p class="ibaw-hero-desc"><?php echo wp_kses_post($first_item['item_desc']); ?></p>
                    </div>
                </div>
            </div>

            <div class="ibaw-hero-center">
                <div class="ibaw-image-wrapper">
                    <img class="ibaw-hero-product-img" src="<?php echo esc_url($first_item['item_image']['url']); ?>" alt="Product Image">
                </div>
            </div>

            <div class="ibaw-hero-right">
                <ul class="ibaw-hero-menu">
                    <?php foreach ($settings['nav_items'] as $index => $item) : 
                        $active_class = ($index === 0) ? 'ibaw-active' : '';
                        $link_url = !empty($item['item_link']['url']) ? esc_url($item['item_link']['url']) : 'javascript:void(0);';
                        $target = !empty($item['item_link']['is_external']) ? ' target="_blank"' : '';
                        $nofollow = !empty($item['item_link']['nofollow']) ? ' rel="nofollow"' : '';
                    ?>
                        <li class="ibaw-menu-li">
                            <a href="<?php echo $link_url; ?>" <?php echo $target . $nofollow; ?>
                               class="ibaw-nav-item <?php echo esc_attr($active_class); ?>"
                               data-title="<?php echo esc_attr($item['item_title']); ?>"
                               data-desc="<?php echo esc_attr($item['item_desc']); ?>"
                               data-img="<?php echo esc_url($item['item_image']['url']); ?>">
                               <span class="ibaw-nav-text"><?php echo esc_html($item['item_title']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }
}