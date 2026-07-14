<?php
/**
 * Plugin Name: IBAW- Elite Accordion Menu
 * Plugin URI:  https://ericksonvilleta.com
 * Description: Accordion-styled menu for Elementor with SVG scaling logic for Quick Links.
 * Version:     1.0
 * Author:      Erick Villeta
 * Author URI:  https://ericksonvilleta.com
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    
    class IBAW_Elite_Accordion_Menu extends \Elementor\Widget_Base {

        public function get_name() { return 'ibaw_elite_accordion'; }
        public function get_title() { return 'IBAW Elite Accordion Menu'; }
        public function get_icon() { return 'eicon-accordion'; }
        public function get_categories() { return [ 'general' ]; }

        protected function register_controls() {
            // Burger Icon Trigger Settings
            $this->start_controls_section('section_trigger', ['label' => 'Burger Trigger']);
            $this->add_control('burger_icon', [
                'label' => 'Menu Icon',
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => ['value' => 'fas fa-bars', 'library' => 'fa-solid'],
            ]);
            $this->add_control('icon_color', [
                'label' => 'Icon Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .ibaw-elite-burger i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .ibaw-elite-burger svg' => 'fill: {{VALUE}} !important; color: {{VALUE}} !important;',
                ],
            ]);
            $this->add_control('icon_size_trigger', [
                'label' => 'Burger Size',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 10, 'max' => 200 ] ],
                'default' => [ 'unit' => 'px', 'size' => 30 ],
                'selectors' => [
                    '{{WRAPPER}} .ibaw-elite-burger i' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ibaw-elite-burger svg' => 'width: {{SIZE}}{{UNIT}} !important; height: auto !important;',
                ],
            ]);
            $this->end_controls_section();

            // Menu Content Settings
            $this->start_controls_section('section_content', ['label' => 'Menu Content']);
            $this->add_control('custom_logo', ['label' => 'Upload Menu Logo', 'type' => \Elementor\Controls_Manager::MEDIA]);
            $this->add_control('logo_width', [
                'label' => 'Logo Width (px)',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 50, 'max' => 500 ] ],
                'default' => [ 'unit' => 'px', 'size' => 150 ],
                'selectors' => [ '{{WRAPPER}} .ibaw-elite-logo' => 'width: {{SIZE}}{{UNIT}} !important; max-width: {{SIZE}}{{UNIT}} !important;' ],
            ]);

            // HEADER ICONS REPEATER
            $repeater = new \Elementor\Repeater();
            $repeater->add_control('item_icon', [
                'label' => 'Icon',
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [ 'value' => 'fas fa-home', 'library' => 'fa-solid' ],
            ]);
            $repeater->add_control('individual_size', [
                'label' => 'Icon Size (px)',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 10, 'max' => 150 ] ],
                'default' => [ 'unit' => 'px', 'size' => 24 ],
            ]);
            $repeater->add_control('item_link', [
                'label' => 'Link',
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => 'https://your-link.com',
                'default' => [ 'url' => '' ],
            ]);

            $this->add_control('header_icons', [
                'label' => 'Header Quick Links',
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => 'Header Icon',
            ]);

            $menus = wp_get_nav_menus();
            $menu_options = ['' => 'Select a Menu'];
            if ($menus) { foreach ($menus as $menu) { $menu_options[$menu->slug] = $menu->name; } }
            $this->add_control('select_menu', ['label' => 'Select WP Menu', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $menu_options]);
            $this->end_controls_section();

            // Styling
            $this->start_controls_section('section_style', ['label' => 'Style Settings', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_control('accent_color', ['label' => 'Accent Color', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#79b42d']);
            $this->add_control('header_icon_color', [
                'label' => 'Header Icons Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '.ibaw-elite-panel .ibaw-elite-header-icon i' => 'color: {{VALUE}} !important;',
                    '.ibaw-elite-panel .ibaw-elite-header-icon svg' => 'fill: {{VALUE}} !important; color: {{VALUE}} !important;',
                ],
            ]);
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $all_items = wp_get_nav_menu_items($settings['select_menu']);
            if (!$all_items) return;

            $menu_tree = [];
            foreach ($all_items as $item) {
                if ($item->menu_item_parent == 0) { $menu_tree[$item->ID] = ['item' => $item, 'children' => []]; } 
                else { if (isset($menu_tree[$item->menu_item_parent])) { $menu_tree[$item->menu_item_parent]['children'][] = $item; } }
            }

            $logo_url = !empty($settings['custom_logo']['url']) ? $settings['custom_logo']['url'] : '';
            $unique_id = 'ibaw-elite-m-' . $this->get_id();
            ?>
            
            <div class="ibaw-elite-burger" data-target="<?php echo esc_attr($unique_id); ?>" style="cursor:pointer; display:inline-block; line-height: 0;">
                <?php \Elementor\Icons_Manager::render_icon( $settings['burger_icon'], [ 'aria-hidden' => 'true' ] ); ?>
            </div>

            <div id="<?php echo esc_attr($unique_id); ?>" class="ibaw-elite-panel" style="--ibaw-elite-accent: <?php echo esc_attr($settings['accent_color']); ?>;">
                <div class="ibaw-elite-head">
                    <?php if ($logo_url) : ?>
                        <img src="<?php echo esc_url($logo_url); ?>" class="ibaw-elite-logo" style="width: <?php echo esc_attr($settings['logo_width']['size']); ?>px;">
                    <?php endif; ?>
                    
                    <div class="ibaw-elite-header-actions">
                        <?php foreach ( $settings['header_icons'] as $index => $item ) : 
                            $link_key = 'link_' . $index;
                            $this->add_link_attributes( $link_key, $item['item_link'] );
                            $size = $item['individual_size']['size'] . 'px';
                            $icon_id = $unique_id . '-icon-' . $index;
                        ?>
                            <style>
                                #<?php echo $icon_id; ?> svg, #<?php echo $icon_id; ?> i { 
                                    width: <?php echo $size; ?> !important; 
                                    height: <?php echo $size; ?> !important; 
                                    font-size: <?php echo $size; ?> !important;
                                    max-width: none !important;
                                }
                            </style>
                            <a id="<?php echo $icon_id; ?>" class="ibaw-elite-header-icon" <?php echo $this->get_render_attribute_string( $link_key ); ?>>
                                <?php \Elementor\Icons_Manager::render_icon( $item['item_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            </a>
                        <?php endforeach; ?>
                        
                        <div class="ibaw-elite-close">&times;</div>
                    </div>
                </div>
                
                <nav class="ibaw-elite-nav">
                    <?php foreach ($menu_tree as $mid => $node) : $has = !empty($node['children']); ?>
                        <div class="ibaw-elite-row <?php echo $has ? 'ibaw-has-kids' : ''; ?>">
                            <div class="ibaw-elite-trigger">
                                <a href="<?php echo esc_url($node['item']->url); ?>"><?php echo esc_html($node['item']->title); ?></a>
                                <?php if ($has) : ?><span class="ibaw-elite-arrow"></span><?php endif; ?>
                            </div>
                            <?php if ($has) : ?>
                                <div class="ibaw-elite-sub">
                                    <?php foreach ($node['children'] as $child) : ?>
                                        <a href="<?php echo esc_url($child->url); ?>"><?php echo esc_html($child->title); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
            </div>

            <style>
            .ibaw-elite-panel { position: fixed; top: 0; right: -105%; width: 100%; max-width: 400px; height: 100vh; background: #fff; z-index: 9999999; transition: right 0.3s ease; box-shadow: -10px 0 30px rgba(0,0,0,0.1); display: flex; flex-direction: column; font-family: inherit; box-sizing: border-box; }
            .ibaw-elite-panel.active { right: 0; }
            .ibaw-elite-head { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; border-bottom: 1px solid #eee; }
            .ibaw-elite-logo { height: auto; object-fit: contain; }
            .ibaw-elite-header-actions { display: flex; align-items: center; gap: 15px; }
            .ibaw-elite-header-icon { cursor: pointer; display: flex; align-items: center; line-height: 0; text-decoration: none; }
            .ibaw-elite-header-icon svg { display: block; fill: currentColor; }
            .ibaw-elite-close { font-size: 35px; cursor: pointer; line-height: 1; color: #333; margin-left: 5px; }
            .ibaw-elite-nav { flex-grow: 1; overflow-y: auto; padding: 10px 0; }
            .ibaw-elite-row { border-bottom: 1px solid #f7f7f7; }
            .ibaw-elite-trigger { display: flex; justify-content: space-between; align-items: center; padding: 18px 25px; cursor: pointer; }
            .ibaw-elite-trigger a { text-decoration: none; color: #111; font-weight: 700; font-size: 16px; flex-grow: 1; }
            .ibaw-elite-arrow { width: 8px; height: 8px; border-right: 2px solid #555; border-bottom: 2px solid #555; transform: rotate(45deg); transition: 0.3s; margin-top: -4px; }
            .ibaw-elite-row.is-active .ibaw-elite-arrow { transform: rotate(-135deg); margin-top: 4px; border-color: var(--ibaw-elite-accent); }
            .ibaw-elite-sub { display: none; background: #f9f9f9; padding: 5px 0 15px 45px; border-top: 1px solid #eee; }
            .ibaw-elite-sub a { display: block; padding: 12px 0; color: #555; text-decoration: none; font-size: 15px; font-weight: 500; transition: 0.2s; }
            body.ibaw-elite-locked { overflow: hidden !important; }
            </style>
            <?php
        }
    }
    $widgets_manager->register( new IBAW_Elite_Accordion_Menu() );
});

add_action( 'wp_footer', function() {
    ?>
    <script>
    (function($) {
        $(document).ready(function() {
            function closeIBAW() {
                $('.ibaw-elite-panel').removeClass('active');
                $('body').removeClass('ibaw-elite-locked');
            }
            $(document).on('click', '.ibaw-elite-burger', function(e) {
                e.preventDefault(); e.stopPropagation();
                var $p = $('#' + $(this).data('target'));
                if (!$p.parent().is('body')) { $p.appendTo('body'); }
                setTimeout(function(){ $p.addClass('active'); $('body').addClass('ibaw-elite-locked'); }, 10);
            });
            $(document).on('click', '.ibaw-elite-close', function() { closeIBAW(); });
            $(document).on('click', '.ibaw-elite-nav a', function(e) {
                var href = $(this).attr('href');
                var isAnchor = href && href.startsWith('#');
                var hasSub = $(this).closest('.ibaw-has-kids').length > 0;
                if (hasSub && (href === '#' || isAnchor)) {
                    if(href === '#') e.preventDefault();
                    var $row = $(this).closest('.ibaw-elite-row');
                    $row.toggleClass('is-active').find('.ibaw-elite-sub').stop().slideToggle(250);
                    $row.siblings('.ibaw-has-kids').removeClass('is-active').find('.ibaw-elite-sub').slideUp(250);
                    if(isAnchor && href !== '#') { setTimeout(closeIBAW, 300); }
                } else if (isAnchor) { closeIBAW(); }
            });
        });
    })(jQuery);
    </script>
    <?php
}, 999 );