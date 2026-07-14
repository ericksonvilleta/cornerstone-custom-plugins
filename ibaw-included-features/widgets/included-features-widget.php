<?php
class IBAW_Included_Features_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ibaw_included_features'; }
    public function get_title() { return 'IBAW What\'s Included'; }
    public function get_icon() { return 'eicon-post-list'; }

    // Enqueue styles directly within the widget
    public function get_style_depends() {
        return ['ibaw-features-style'];
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', ['label' => 'Features']);
        
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('number', ['label' => 'Number', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '01']);
        $repeater->add_control('title', ['label' => 'Title', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Feature Title']);
        $repeater->add_control('desc', ['label' => 'Description', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Description text.']);
        
        $this->add_control('features_list', [
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [
                ['number' => '01', 'title' => 'Custom design', 'desc' => 'Layout, railing, stairs, and finish details tailored to your home, yard, and how you\'ll actually use the space.'],
                ['number' => '02', 'title' => 'Coast-ready materials', 'desc' => 'Pressure-treated, composite, and hardwood options selected for weather resistance and low ongoing maintenance.'],
                ['number' => '03', 'title' => 'Built to code', 'desc' => 'Every deck is built to local code with proper footings, structure, and railing heights — safe, permitted, inspection-ready.'],
            ]
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Inline CSS for the exact look of image_fba5a1.png
        echo '<style>
            .ibaw-features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
            .ibaw-feature-card { background: #dbe6ef; padding: 40px; border-radius: 10px; }
            .ibaw-card-number { color: #2d85b1; font-weight: bold; margin-bottom: 15px; }
            .ibaw-card-title { margin: 0 0 15px 0; font-size: 20px; }
            .ibaw-card-desc { margin: 0; line-height: 1.6; }
            @media (max-width: 768px) { .ibaw-features-grid { grid-template-columns: 1fr; } }
        </style>';

        echo '<div class="ibaw-features-grid">';
        foreach ($settings['features_list'] as $item) {
            echo '<div class="ibaw-feature-card">';
            echo '<div class="ibaw-card-number">' . esc_html($item['number']) . '</div>';
            echo '<h3 class="ibaw-card-title">' . esc_html($item['title']) . '</h3>';
            echo '<p class="ibaw-card-desc">' . esc_html($item['desc']) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }
}