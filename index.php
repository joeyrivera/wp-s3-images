<?php

/**
 * Plugin Name: JR S3 Images
 * Plugin URI: http://www.mywebsite.com/my-first-plugin
 * Description: View and upload images in S3 bucket
 * Version: 1.0
 * Author: Joey Rivera
 * Author URI: http://www.joeyrivera.com
 */

require_once 'widget.php';

$widget = new \Jr\S3\Images\Widget();

// register widget
add_action('widgets_init', function () use ($widget) {
    register_widget($widget);
});

add_action('admin_init', function () use ($widget) {
    register_setting('jrs3images_option_group', 'bucket_name');
    register_setting('jrs3images_option_group', 'bucket_region');
    register_setting('jrs3images_option_group', 'identity_pool_id_public');
    register_setting('jrs3images_option_group', 'identity_pool_id_admin');
});

// register settings
add_action('admin_menu', function () use ($widget) {
    add_options_page(
        'Jr S3 Images',
        'Jr S3 Images',
        'administrator',
        'jrs3imagessettings',
        [$widget, 'create_plugin_settings_page']
    );
});
