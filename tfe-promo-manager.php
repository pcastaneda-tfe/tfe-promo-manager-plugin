<?php

/*

 * Plugin Name: TFE Promo Manager
 * Plugin URI: https://pedroc.dev/
 * Description: A plugin to manage and schedule promotional content.
 * Version: 1.0.0
 * Author: Pedro Castaneda
 * Author URI: https://pedroc.dev/
 * Text Domain: tfe-promo-manager

 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    die;
}

// Create promo manger class
class PromoManager
{
    private $options;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Promo Manager Settings',
            'Promo Manager',
            'manage_options',
            'tfe-promo-manager-page',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page()
    {
        // Render settings page content
        $this->options = get_option('promomanager_settings'); // get the options from the database
        ?>
        <div class="wrap">
            <h1>Promo Manager</h1>
            <div class="notice notice-info">
                <p>
                    <strong>Using Advanced Custom Fields (ACF) with Promo Manager:</strong><br>
                    To display your promo content via ACF, please follow these steps:
                <ol>
                    <li>Create a new field in ACF and choose a suitable location for it to appear.</li>
                    <li>In your ACF field group, add a 'Text' field type.</li>
                    <li>Name this field appropriately (e.g., 'Promo Content').</li>
                    <li>In the post or page editor where you want the promo to appear, insert the following shortcode:
                        <code>[promo_display]</code> into your ACF text field.
                    </li>
                    <li>Ensure that your Promo Manager plugin is configured with the desired promo content and schedule.</li>
                    <li>The promo content will automatically display on your site based on the settings you’ve configured in the
                        Promo Manager.</li>
                </ol>
                <strong>Note:</strong> This message assumes that you have the ACF plugin installed and active on your WordPress
                site.
                </p>
            </div>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields('promomanager_option_group');
                do_settings_sections('tfe-promo-manager-page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init()
    {
        // Register and define the settings
        register_setting(
            'promomanager_option_group', // Option group
            'promomanager_settings', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Promo Manager Settings', // Title
            array($this, 'print_section_info'), // Callback
            'tfe-promo-manager-page' // Page
        );

        add_settings_field(
            'promo_content', // ID
            'Promo Content', // Title
            array($this, 'promo_content_callback'), // Callback
            'tfe-promo-manager-page', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'promo_start_date', // ID
            'Promo Start Date', // Title
            array($this, 'promo_start_date_callback'), // Callback
            'tfe-promo-manager-page', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'promo_end_date', // ID
            'Promo End Date', // Title
            array($this, 'promo_end_date_callback'), // Callback
            'tfe-promo-manager-page', // Page
            'setting_section_id' // Section
        );
    }

    public function sanitize($input)
    {
        // Sanitize the input
        $new_input = array(); // create an empty array
        if (isset($input['promo_content'])) // check if the promo content is set
            $new_input['promo_content'] = wp_kses_post($input['promo_content']); // sanitize the promo content
        //TODO: Check if promo content is empty reset the date inputs

        if (isset($input['promo_start_date']))
            $new_input['promo_start_date'] = sanitize_text_field($input['promo_start_date']);

        if (isset($input['promo_end_date']))
            $new_input['promo_end_date'] = sanitize_text_field($input['promo_end_date']);

        if (isset($input['promo_display']))
            $new_input['promo_display'] = sanitize_text_field($input['promo_display']);

        return $new_input;
    }

    public function print_section_info()
    {
        // Print the section text
        print '<p>Enter promotional content below:</p>';
    }

    // TODO: Add feature to select specific pages to display the promo on
    // TODO: Add feature to select specific start and end times for the promo in 12 hour format

    public function promo_content_callback()
    {
        // TODO: Create a WYSIWYG editor for the promo content instead of a text area
        // Create a text area field
        printf(
            '<textarea id="promo_content" name="promomanager_settings[promo_content]" rows="5" cols="50">%s</textarea>',
            isset($this->options['promo_content']) ? esc_attr($this->options['promo_content']) : ''
        );
    }

    public function promo_start_date_callback()
    {
        // Create a date picker field
        printf(
            '<input type="date" id="promo_start_date" name="promomanager_settings[promo_start_date]" value="%s" />',
            isset($this->options['promo_start_date']) ? esc_attr($this->options['promo_start_date']) : ''
        );
    }

    public function promo_end_date_callback()
    {
        // TODO: Add validation to ensure that the end date is not before the start date
        printf(
            '<input type="date" id="promo_end_date" name="promomanager_settings[promo_end_date]" value="%s" />',
            isset($this->options['promo_end_date']) ? esc_attr($this->options['promo_end_date']) : ''
        );
    }
}

// Check if the user is an admin
if (is_admin()) {
    // If so, create an instance of the class
    $promomanager = new PromoManager();
}

// Create the shortcode
function promo_display_shortcode()
{
    $options = get_option('promomanager_settings'); // get the options from the database
    $promo_content = $options['promo_content']; // get the promo content from the options
    $promo_start_date = strtotime($options['promo_start_date']); // convert datetimes to unix timestamps 
    $promo_end_date = strtotime($options['promo_end_date']); // convert datetimes to unix timestamps
    $current_time = current_time('timestamp'); // get the current time as a unix timestamp

    //check if the current time is between the start and end dates
    if ($current_time > $promo_start_date && $current_time < $promo_end_date) {
        return $promo_content; // if it is, then return the promo content
    } else {
        return ''; // if not, then return empty string
    }
}

add_shortcode('promo_display', 'promo_display_shortcode'); // register the shortcode

?>