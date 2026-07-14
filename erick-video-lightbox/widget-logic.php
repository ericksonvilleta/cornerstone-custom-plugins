<?php
class Erick_Video_Lightbox_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'erick_video_lightbox'; }
    public function get_title() { return 'Erick Video Lightbox'; }
    public function get_icon() { return 'eicon-play-button'; }
    public function get_categories() { return [ 'general' ]; }

    protected function register_controls() {
        $this->start_controls_section('section_content', ['label' => 'Content']);

        $this->add_control('button_text', [
            'label' => 'Button Text',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'WATCH VIDEO',
        ]);

        $this->add_control('video_url', [
            'label' => 'YouTube URL',
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://www.youtube.com/watch?v=XXXXX',
        ]);

        $this->add_control('selected_icon', [
            'label' => 'Icon',
            'type' => \Elementor\Controls_Manager::ICONS,
            'default' => [ 'value' => 'fas fa-play', 'library' => 'fa-solid' ],
        ]);

        $this->add_control('icon_align', [
            'label' => 'Icon Position',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'left',
            'options' => [ 'left' => 'Before', 'right' => 'After' ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => 'Style', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        
        $this->add_control('bg_color', [
            'label' => 'Background Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .erick-v-btn' => 'background-color: {{VALUE}};' ],
        ]);

        $this->add_control('txt_color', [
            'label' => 'Text Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .erick-v-btn' => 'color: {{VALUE}};' ],
        ]);

        $this->add_responsive_control('br_radius', [
            'label' => 'Border Radius',
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%' ],
            'selectors' => [ '{{WRAPPER}} .erick-v-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);

        $this->add_responsive_control('btn_padding', [
            'label' => 'Padding',
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em' ],
            'selectors' => [ '{{WRAPPER}} .erick-v-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);

        // NEW: Native Elementor Hover Animation Control
        $this->add_control('hover_animation', [
            'label' => 'Hover Animation',
            'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $video_url = $settings['video_url'];

        // Convert to Embed format (YouTube blocks standard links in iframes)
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match)) {
            $video_url = "https://www.youtube.com/embed/" . $match[1] . "?autoplay=1";
        }

        // Create the action configuration
        $settings_json = [
            'type' => 'video',
            'videoType' => 'youtube',
            'url' => $video_url,
        ];

        // This is the "Magic String" Elementor uses for all lightboxes
        $action_url = '#elementor-action:action=lightbox&settings=' . base64_encode(wp_json_encode($settings_json));
        
        // NEW: Grab the selected animation and build the correct Elementor class
        $hover_class = !empty($settings['hover_animation']) ? 'elementor-animation-' . $settings['hover_animation'] : '';
        ?>
        
        <div class="erick-video-container">
            <a href="<?php echo esc_attr($action_url); ?>" 
               class="erick-v-btn elementor-button <?php echo esc_attr($hover_class); ?>" 
               role="button"
               onclick="if(window.elementorFrontend && elementorFrontend.utils && elementorFrontend.utils.urls) { 
                            elementorFrontend.utils.urls.handleAction(this.href); 
                            return false; 
                        }"
               style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; cursor: pointer;">
               
                <span class="elementor-button-content-wrapper" style="display: flex; align-items: center; gap: 10px;">
                    <?php if ( ! empty( $settings['selected_icon']['value'] ) && $settings['icon_align'] === 'left' ) : ?>
                        <?php \Elementor\Icons_Manager::render_icon( $settings['selected_icon'] ); ?>
                    <?php endif; ?>

                    <span class="elementor-button-text">
                        <?php echo esc_html($settings['button_text']); ?>
                    </span>

                    <?php if ( ! empty( $settings['selected_icon']['value'] ) && $settings['icon_align'] === 'right' ) : ?>
                        <?php \Elementor\Icons_Manager::render_icon( $settings['selected_icon'] ); ?>
                    <?php endif; ?>
                </span>
            </a>
        </div>
        <?php
    }
}