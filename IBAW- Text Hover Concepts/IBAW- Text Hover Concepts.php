<?php
/**
 * Plugin Name: IBAW- Text Hover Concepts
 * Description: Elementor Addon for various text hover concepts.
 * Plugin URI: https://ericksonvilleta.com
 * Author: Erick Villeta
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function register_ibaw_hover_concepts_widget( $widgets_manager ) {
    class IBAW_Hover_Concepts_Widget extends \Elementor\Widget_Base {

        public function get_name() { return 'ibaw-hover-concepts'; }
        public function get_title() { return 'IBAW Hover Concepts'; }
        public function get_icon() { return 'eicon-text-area'; }
        public function get_categories() { return [ 'general' ]; }

        protected function render() {
            ?>
            <div class="ibaw-main-content">
                <!-- Concept One: POOL DECKS -->
                <div class="concept concept-one">
                    <div class="hover hover-1"></div>
                    <div class="hover hover-2"></div>
                    <div class="hover hover-3"></div>
                    <div class="hover hover-4"></div>
                    <div class="hover hover-5"></div>
                    <div class="hover hover-6"></div>
                    <div class="hover hover-7"></div>
                    <div class="hover hover-8"></div>
                    <div class="hover hover-9"></div>
                    <h1>POOL DECKS</h1>
                </div>
                
                <!-- Concept Two: HOUSE DECKS -->
                <div class="concept concept-two">
                    <div class="hover"><h1>H</h1></div>
                    <div class="hover"><h1>O</h1></div>
                    <div class="hover"><h1>U</h1></div>
                    <div class="hover"><h1>S</h1></div>
                    <div class="hover"><h1>E</h1></div>
                    <div class="hover"><h1>&nbsp;</h1></div>
                    <div class="hover"><h1>D</h1></div>
                    <div class="hover"><h1>E</h1></div>
                    <div class="hover"><h1>C</h1></div>
                    <div class="hover"><h1>K</h1></div>
                    <div class="hover"><h1>S</h1></div>
                </div>
                
                <!-- Concept Three: RV DECKS -->
                <div class="concept concept-three">
                    <div class="word">
                        <div class="hover"><div></div><div></div><h1>R</h1></div>
                        <div class="hover"><div></div><div></div><h1>V</h1></div>
                        <div class="hover"><div></div><div></div><h1>&nbsp;</h1></div>
                        <div class="hover"><div></div><div></div><h1>D</h1></div>
                        <div class="hover"><div></div><div></div><h1>E</h1></div>
                        <div class="hover"><div></div><div></div><h1>C</h1></div>
                        <div class="hover"><div></div><div></div><h1>K</h1></div>
                        <div class="hover"><div></div><div></div><h1>S</h1></div>
                    </div>
                </div>
                
                <!-- Concept Four: LANDINGS -->
                <div class="concept concept-four">
                    <h1>LANDINGS</h1>
                </div>
                
                <!-- Concept Five: RAMPS -->
                <div class="concept concept-five">
                    <h1 class="word">
                        <span class="char">R</span><span class="char">A</span><span class="char">M</span><span class="char">P</span><span class="char">S</span>
                    </h1>
                </div>
            </div>

            <style>
                @import url('https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;700&family=Montserrat:wght@900&display=swap');

                .ibaw-main-content {
                    text-align: center;
                    text-transform: uppercase;
                    scroll-snap-type: y mandatory;
                    position: relative;
                    height: 100vh;
                    overflow-y: scroll;
                    background: #fff;
                    font-family: "Comfortaa", sans-serif;
                }
                
                .ibaw-main-content * { box-sizing: border-box; }
                .ibaw-main-content .hover, .ibaw-main-content .word, .ibaw-main-content h1 { cursor: pointer; }
                
                .ibaw-main-content h1 { 
                    position: relative; 
                    color: #fff; 
                    font: 900 60px Montserrat, sans-serif; 
                    text-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); 
                    margin: 0; 
                }
                
                .ibaw-main-content .concept { 
                    position: relative; 
                    padding: 5em; 
                    height: 100vh; 
                    overflow: hidden; 
                    scroll-snap-align: center; 
                }
                
                .ibaw-main-content .concept:before { 
                    content: ""; 
                    position: absolute; 
                    width: 100%; 
                    height: 100%; 
                    top: 0; 
                    left: 0; 
                    background: radial-gradient(rgba(0, 0, 0, 0.3), transparent); 
                    opacity: 0; 
                    transition: all 1s cubic-bezier(0.19, 1, 0.22, 1); 
                }
                
                .ibaw-main-content .concept:hover:before { opacity: 0.5; }

                /* Concept One */
                .ibaw-main-content .concept-one { 
                    display: grid; 
                    grid: repeat(3, 1fr) / repeat(3, 1fr); 
                    background: url("https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/New-Decks-Template_0003_POOL-DECKS.webp") no-repeat center center / cover; 
                }
                .ibaw-main-content .concept-one h1 { position: absolute; margin: auto; left: 0; right: 0; top: 50%; margin-top: -30px; transition: 0.5s ease; z-index: 0; letter-spacing: 25px; }
                .ibaw-main-content .concept-one .hover { z-index: 1; }
                .ibaw-main-content .concept-one .hover-1:hover ~ h1 { left: 30px; margin-top: -10px; }
                .ibaw-main-content .concept-one .hover-2:hover ~ h1 { margin-top: -10px; }
                .ibaw-main-content .concept-one .hover-3:hover ~ h1 { right: 30px; margin-top: -10px; }
                .ibaw-main-content .concept-one .hover-4:hover ~ h1 { left: 30px; }
                .ibaw-main-content .concept-one .hover-6:hover ~ h1 { right: 30px; }
                .ibaw-main-content .concept-one .hover-7:hover ~ h1 { left: 30px; margin-top: -50px; }
                .ibaw-main-content .concept-one .hover-8:hover ~ h1 { margin-top: -50px; }
                .ibaw-main-content .concept-one .hover-9:hover ~ h1 { right: 30px; margin-top: -50px; }

                /* Concept Two */
                .ibaw-main-content .concept-two { 
                    display: grid; 
                    grid: 100% / repeat(11, 1fr); /* Updated to 11 to account for 11 characters in HOUSE DECKS */
                    padding: 5em 10em; 
                    background: url("https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/New-Decks-Template_0004_HOUSE-DECKS.webp") center center / cover; 
                }
                .ibaw-main-content .concept-two .hover { position: relative; display: grid; place-items: center; }
                .ibaw-main-content .concept-two h1 { color: transparent; -webkit-text-stroke: 2px #fff; text-shadow: none; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
                .ibaw-main-content .concept-two .hover:hover h1 { transform: scale(1.5); color: #fff; -webkit-text-stroke: 2px transparent; text-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }

                /* Concept Three */
                .ibaw-main-content .concept-three { 
                    padding: 5em; 
                    background: url("https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/New-Decks-Template_0002_RV-DECKS.webp") center center / cover; 
                }
                .ibaw-main-content .concept-three .word { display: flex; align-items: center; max-width: 800px; margin: auto; height: 100%; }
                .ibaw-main-content .concept-three .hover { flex: 1; display: grid; height: calc(100vh - 10em); grid: repeat(2, 2fr) / 100%; position: relative; }
                .ibaw-main-content .concept-three .hover div { position: relative; z-index: 5; }
                .ibaw-main-content .concept-three .hover div:nth-child(1):hover ~ h1 { margin-top: -10px; }
                .ibaw-main-content .concept-three .hover div:nth-child(2):hover ~ h1 { margin-top: -50px; }
                .ibaw-main-content .concept-three h1 { position: absolute; margin: auto; left: 0; right: 0; top: 50%; margin-top: -30px; transition: 0.3s cubic-bezier(0.23, 1, 0.32, 1); z-index: 0; }

                /* Concept Four */
                .ibaw-main-content .concept-four { 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    letter-spacing: 2em; 
                    background: url("https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/New-Decks-Template_0001_LANDINGS.webp") no-repeat center center / cover; 
                }
                .ibaw-main-content .concept-four h1 { display: inline-block; transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
                .ibaw-main-content .concept-four:hover h1 { letter-spacing: 25px; transform: scale(1.3); }

                /* Concept Five */
                .ibaw-main-content .concept-five { 
                    background: url("https://cornerstonelandscapesupply.com/wp-content/uploads/2026/06/New-Decks-Template_0000_RAMPS.webp") center center / cover; 
                    display: flex; 
                    align-items: center; 
                }
                .ibaw-main-content .concept-five .word { width: 600px; margin: auto; display: flex; align-items: center; justify-content: center; height: 80%; }
                .ibaw-main-content .concept-five .word:hover .char:nth-child(even) { top: 20px; }
                .ibaw-main-content .concept-five .word:hover .char:nth-child(odd) { top: -20px; }
                .ibaw-main-content .concept-five .char { flex: 1; position: relative; display: inline-block; top: 0; transition: 0.5s cubic-bezier(0.19, 1, 0.22, 1); }
            </style>
            <?php
        }
    }
    $widgets_manager->register( new IBAW_Hover_Concepts_Widget() );
}
add_action( 'elementor/widgets/register', 'register_ibaw_hover_concepts_widget' );