<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IBAW_Content_Block_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ibaw_content_block'; }
    public function get_title() { return 'IBAW Content Block'; }
    public function get_icon() { return 'eicon-editor-h1'; }

    protected function register_controls() {
        
        // --- CONTENT TAB ---
        $this->start_controls_section('content_section', ['label' => 'Content']);
        $this->add_control('sub_heading', ['label' => 'Sub-heading', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'BUILT FOR YOUR SPACE']);
        $this->add_control('heading', ['label' => 'Heading', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Pool Decks Built for Comfort and Durability']);
        $this->add_control('description', ['label' => 'Description', 'type' => \Elementor\Controls_Manager::WYSIWYG, 'default' => 'A well-designed pool deck...']);
        $this->end_controls_section();

        // --- STYLE TAB ---
        $this->start_controls_section('style_section', ['label' => 'Style', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);

        // Sub-heading Styling
        $this->add_control('sub_heading_color', ['label' => 'Sub-heading Color', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ibaw-sub-heading' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'sub_heading_typo', 'selector' => '{{WRAPPER}} .ibaw-sub-heading']);

        // Heading Styling
        $this->add_control('heading_color', ['label' => 'Heading Color', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ibaw-heading' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'heading_typo', 'selector' => '{{WRAPPER}} .ibaw-heading']);

        // Description Styling
        $this->add_control('desc_color', ['label' => 'Description Color', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ibaw-description' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'desc_typo', 'selector' => '{{WRAPPER}} .ibaw-description']);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo '<div class="ibaw-content-block">';
        echo '<div class="ibaw-sub-heading">' . esc_html($settings['sub_heading']) . '</div>';
        echo '<h2 class="ibaw-heading">' . esc_html($settings['heading']) . '</h2>';
        echo '<div class="ibaw-description">' . $settings['description'] . '</div>';
        echo '</div>';
    }
}