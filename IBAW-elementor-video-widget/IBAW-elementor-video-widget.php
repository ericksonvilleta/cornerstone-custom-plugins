<?php
/**
 * Plugin Name: IBAW- Elementor YouTube Widget
 * Description: Custom Elementor widget to display a YouTube video, with dynamic ACF support.
 * Version:     1.1.0
 * Author:      Erick Villeta
 * Plugin URI:  https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Register the widget
function ibaw_register_elementor_video_widget( $widgets_manager ) {
    
    class IBAW_Video_Widget extends \Elementor\Widget_Base {

        public function get_name() { return 'ibaw_video'; }
        public function get_title() { return 'IBAW- YouTube Video'; }
        public function get_icon() { return 'eicon-youtube'; }
        public function get_categories() { return [ 'general' ]; }

        protected function register_controls() {
            $this->start_controls_section( 'content_section', [ 'label' => 'Video Settings', 'tab' => \Elementor\Controls_Manager::TAB_CONTENT ] );
            
            $this->add_control( 'youtube_url', [
                'label'       => 'YouTube Link',
                'type'        => \Elementor\Controls_Manager::TEXT,
                'description' => 'If left blank, it will attempt to use the ACF field "youtube_link" from the current post.',
                'placeholder' => 'https://youtu.be/vBBt5L3a8GA',
            ]);
            
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            
            // Priority 1: Widget input, Priority 2: ACF field 'youtube_link'
            $url = !empty($settings['youtube_url']) ? $settings['youtube_url'] : get_field('youtube_link', get_the_ID());

            if (empty($url)) {
                echo '<p>Please set a YouTube link in the widget or ACF field.</p>';
                return;
            }

            // Enqueue Assets
            wp_enqueue_style( 'fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.css' );
            wp_enqueue_script( 'fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js', ['jquery'], null, true );

            echo '<div class="block-video">
                    <a data-fancybox href="' . esc_url($url) . '">
                        <div class="play">
                            <svg id="play-youtube" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 721">
                                <path id="Triangle" fill="#FFFFFF" d="M407,493l276-143L407,206V493z"/>
                                <path id="The_Sharpness" opacity="0.12" fill="#420000" d="M407,206l242,161.6l34-17.6L407,206z"/>
                                <g id="Button_bg">
                                    <linearGradient id="gradient_play" x1="512.5" y1="719.7" x2="512.5" y2="1.2" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1 0 0 -1 0 721)">
                                        <stop offset="0" style="stop-color:#E52D27"/>
                                        <stop offset="1" style="stop-color:#BF171D"/>
                                    </linearGradient>
                                    <path fill="url(#gradient_play)" d="M1013,156.3c0,0-10-70.4-40.6-101.4C933.6,14.2,890,14,870.1,11.6C727.1,1.3,512.7,1.3,512.7,1.3 h-0.4c0,0-214.4,0-357.4,10.3C135,14,91.4,14.2,52.6,54.9C22,85.9,12,156.3,12,156.3S1.8,238.9,1.8,321.6v77.5 C1.8,481.8,12,564.4,12,564.4s10,70.4,40.6,101.4c38.9,40.7,89.9,39.4,112.6,43.7c81.7,7.8,347.3,10.3,347.3,10.3 s214.6-0.3,357.6-10.7c20-2.4,63.5-2.6,102.3-43.3c30.6-31,40.6-101.4,40.6-101.4s10.2-82.7,10.2-165.3v-77.5 C1023.2,238.9,1013,156.3,1013,156.3z M407,493V206l276,144L407,493z"/>
                                </g>
                            </svg>
                        </div>
                    </a>
                </div>
                <style>
                    .block-video { width:300px; text-align:center; margin:30px auto; }
                    .play { width:80px; height:auto; margin:0 auto; cursor:pointer; }
                    .play #play-youtube { opacity:.3; filter: grayscale(100%); transition: all 250ms ease-out; }
                    .play:hover #play-youtube { opacity:1; filter: grayscale(0%); transition: all 250ms ease-in; }
                </style>';
        }
    }

    $widgets_manager->register( new \IBAW_Video_Widget() );
}

add_action( 'elementor/widgets/register', 'ibaw_register_elementor_video_widget' );