<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IBAW_Product_Card_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ibaw_product_card'; }
    public function get_title() { return 'IBAW Product Card'; }
    public function get_icon() { return 'eicon-product-card'; }

    protected function register_controls() {
        // --- CONTENT TAB ---
        $this->start_controls_section('content_section', ['label' => 'Content']);
        $this->add_control('image', ['label' => 'Product Image', 'type' => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control('title', ['label' => 'Title', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'DPB-2500']);
        $this->add_control('category', ['label' => 'Category', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Echo Blowers']);
        $this->add_control('description', ['label' => 'Description', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'The battery-powered blowing force...']);
        
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('btn_text', ['label' => 'Button Text', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'BUTTON']);
        $repeater->add_control('btn_link', ['label' => 'Link', 'type' => \Elementor\Controls_Manager::URL]);
        $this->add_control('buttons_list', ['type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls()]);
        $this->end_controls_section();

        // --- STYLE TAB ---
        $this->start_controls_section('style_section', ['label' => 'Typography & Colors', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        
        $this->add_control('title_color', ['label' => 'Title Color', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ibaw-card h2' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'title_typo', 'selector' => '{{WRAPPER}} .ibaw-card h2']);

        $this->add_control('cat_color', ['label' => 'Category Color', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ibaw-card h3' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'cat_typo', 'selector' => '{{WRAPPER}} .ibaw-card h3']);

        $this->add_control('desc_color', ['label' => 'Description Color', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .ibaw-card p' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'desc_typo', 'selector' => '{{WRAPPER}} .ibaw-card p']);

        $this->add_control('btn_style_heading', ['label' => 'Button Typography', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'btn_typo', 
            'selector' => '{{WRAPPER}} .ibaw-btn'
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $image_url = !empty($settings['image']['url']) ? $settings['image']['url'] : '';
        ?>
        <div class="ibaw-card">
            <?php if ($image_url) : ?>
                <div class="ibaw-card-image"><img src="<?php echo esc_url($image_url); ?>" alt="Product"></div>
            <?php endif; ?>
            <div class="ibaw-card-content">
                <h2><?php echo esc_html($settings['title']); ?></h2>
                <h3><?php echo esc_html($settings['category']); ?></h3>
                <p><?php echo esc_html($settings['description']); ?></p>
                <div class="ibaw-buttons">
                    <?php if (!empty($settings['buttons_list'])) : 
                        foreach ($settings['buttons_list'] as $index => $btn) : 
                            $this->add_link_attributes('link_' . $index, $btn['btn_link']); ?>
                            <a <?php echo $this->get_render_attribute_string('link_' . $index); ?> class="ibaw-btn"><?php echo esc_html($btn['btn_text']); ?></a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <style>
    /* Force cards to have a consistent minimum height */
    .ibaw-card { 
        display: flex; 
        flex-direction: row; 
        padding: 30px; 
        border-radius: 15px; 
        align-items: center; 
        gap: 30px; 
        background: #f0f4f8; 
        border: 3px solid #f4531d; 
        height: 100%; 
        min-height: 450px; /* Adjust this value to be taller than your longest content */
    }
    
    .ibaw-card-image { flex: 1; display: flex; justify-content: center; }
    
    .ibaw-card-content { 
        flex: 2; 
        display: flex; 
        flex-direction: column; 
        height: 100%; 
    }
    
    .ibaw-buttons { 
        display: flex; 
        flex-direction: column; 
        gap: 10px; 
        margin-top: auto; /* Pushes buttons to the bottom of the card */
        padding-top: 20px;
    }
    
    .ibaw-btn { 
        padding: 12px; 
        color: #fff; 
        text-align: center; 
        border-radius: 5px; 
        text-decoration: none; 
        font-weight: bold; 
        background: #f4531d; 
        transition: 0.3s; 
    }
    
    .ibaw-btn:hover { transform: scale(1.05); color: #fff; }
    
    @media (max-width: 768px) { .ibaw-card { flex-direction: column; min-height: auto; } }
</style>
        <?php
    }
}