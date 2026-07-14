<?php
/**
 * Plugin Name: IBAW- Email Stacking Formatter
 * Description: Automatically stacks inline form submissions into a clean, labeled HTML format for Cornerstone Landscape Supply.
 * Version: 1.0
 * Author: Erick Villeta
 * Author URI: https://ericksonvilleta.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IBAW_Email_Stacker {

    public function __construct() {
        // Intercept all outgoing WordPress mail to format it
        add_filter( 'wp_mail', array( $this, 'format_to_stacked_html' ) );
    }

    /**
     * Formats plain text email strings into stacked HTML blocks
     */
    public function format_to_stacked_html( $args ) {
        
        // 1. Identify relevant forms by subject line keywords
        $subjects_to_format = array('Parts Request', 'Quote Request', 'Contact', 'Inquiry');
        $should_format = false;

        foreach ( $subjects_to_format as $keyword ) {
            if ( stripos( $args['subject'], $keyword ) !== false ) {
                $should_format = true;
                break;
            }
        }

        if ( $should_format ) {
            
            // 2. Set headers to HTML to support stacking and styling
            $args['headers'][] = 'Content-Type: text/html; charset=UTF-8';

            $message = $args['message'];

            // 3. Define the "Master List" of labels to find and stack
            // These cover Parts, Directory, and standard Contact forms
            $labels = array(
                'CUSTOMER NAME:', 'CUSTOMER:', 'NAME:',
                'CUSTOMER EMAIL:', 'EMAIL:', 
                'PHONE:', 'PHONE NUMBER:',
                'SELECTED CONTRACTORS:', 'CONTRACTOR:',
                'PROJECT DETAILS:', 'MESSAGE:', 'COMMENTS:',
                'MODEL:', 'MODEL NUMBER:',
                'SERIAL:', 'SERIAL NUMBER:',
                'DESCRIPTION:', 'PART DESCRIPTION:',
                'ADDRESS:', 'CITY:', 'STATE:', 'ZIP:'
            );

            // 4. Transform the message:
            // Convert any existing technical newlines (\n) to HTML breaks (<br>) first
            $message = nl2br($message);

            // Find each label, add a line break before it, and wrap it in a bold tag
            foreach ( $labels as $label ) {
                $replacement = '<br><strong style="color: #1a2b49; text-transform: uppercase; font-size: 12px; display: inline-block; margin-top: 10px;">' . $label . '</strong><br>';
                $message = str_ireplace( $label, $replacement, $message );
            }

            // 5. Wrap everything in a Cornerstone-branded HTML Container
            $args['message'] = "
                <div style='background-color: #f4f4f4; padding: 40px 20px; font-family: Helvetica, Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e1e1e1;'>
                        
                        <div style='background: #1a2b49; padding: 25px; border-bottom: 4px solid #c5a059;'>
                            <h2 style='color: #ffffff; margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px;'>
                                {$args['subject']}
                            </h2>
                        </div>

                        <div style='padding: 35px; line-height: 1.6; color: #333333; font-size: 15px;'>
                            {$message}
                        </div>

                        <div style='background: #f9f9f9; padding: 20px; border-top: 1px solid #eeeeee; text-align: center;'>
                            <p style='margin: 0; font-size: 11px; color: #999999; text-transform: uppercase; letter-spacing: 0.5px;'>
                                Notification from Cornerstone Landscape Supply
                            </p>
                        </div>

                    </div>
                </div>
            ";
        }

        return $args;
    }
}

// Initialize the plugin
new IBAW_Email_Stacker();