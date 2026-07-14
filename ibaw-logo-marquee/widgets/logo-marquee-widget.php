<?php
class IBAW_Logo_Marquee_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'ibaw_logo_marquee'; }
    public function get_title() { return 'IBAW Logo Marquee'; }
    public function get_icon() { return 'eicon-slider-push'; }

    protected function register_controls() {
        $this->start_controls_section('content_section', ['label' => 'Logos']);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('logo_image', ['label' => 'Logo', 'type' => \Elementor\Controls_Manager::MEDIA]);
        $repeater->add_control('logo_link', ['label' => 'Link', 'type' => \Elementor\Controls_Manager::URL]);
        
        $this->add_control('logo_list', [
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
        ]);
        
        $this->add_control('speed', ['label' => 'Speed (Seconds)', 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 20]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $speed = $settings['speed'];
        ?>
        <div class="ibaw-marquee-container">
            <div class="ibaw-marquee-track" style="animation-duration: <?php echo esc_attr($speed); ?>s;">
                <?php foreach ($settings['logo_list'] as $item) : 
                    $this->add_link_attributes('link_' . $item['_id'], $item['logo_link']); ?>
                    <a <?php echo $this->get_render_attribute_string('link_' . $item['_id']); ?> class="ibaw-logo-item">
                        <img src="<?php echo esc_url($item['logo_image']['url']); ?>" alt="Logo">
                    </a>
                <?php endforeach; ?>
                <!-- Duplicate for seamless loop -->
                <?php foreach ($settings['logo_list'] as $item) : ?>
                    <div class="ibaw-logo-item"><img src="<?php echo esc_url($item['logo_image']['url']); ?>" alt="Logo"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .ibaw-marquee-container { overflow: hidden; width: 100%; display: flex; }
            .ibaw-marquee-track { display: flex; animation: ibaw-scroll linear infinite; }
            .ibaw-marquee-track:hover { animation-play-state: paused; } /* Pause on hover */
            .ibaw-logo-item { padding: 0 40px; flex-shrink: 0; }
            .ibaw-logo-item img { height: 60px; width: auto; }
            @keyframes ibaw-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        </style>
        <?php
    }
}