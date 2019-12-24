<?php

namespace Jr\S3\Images;

/**
 * 
 */
class Widget extends \WP_Widget
{
    /**
     * default title to use for the widget
     */
    protected $default_title = 'Images';

    /**
     * default number of images to display in the widget
     */
    protected $default_number_of_images = 5;

    /**
     * init the widget
     */
    public function __construct()
    {
        $widget_ops = array(
            'description' => 'A plugin to list and upload images in S3 bucket',
        );

        parent::__construct('jrs3images', 'Jr S3 Images', $widget_ops);
    }

    /**
     * Echos the widget content to the screen
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        wp_enqueue_script('awssdk', 'https://sdk.amazonaws.com/js/aws-sdk-2.283.1.min.js');

        $variables = [
            'bucket_name' => get_option('bucket_name'),
            'bucket_region' => get_option('bucket_region'),
            'identity_pool_id' => get_option('identity_pool_id_public'),
            'queueURL' => null,
            'number_of_images' => ($instance['number_of_images'] ?? $this->default_number_of_images)
        ];

        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            $instance['title'] = apply_filters('widget_title', $instance['title']);
            echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
        } else {
            echo $args['before_title'] . esc_html($this->default_title) . $args['after_title'];
        }

        // upload for admin
        if (current_user_can('administrator')) {
            require_once 'upload.phtml';

            // use elevated policy if admin
            $variables['queue_url'] = get_option('queue_url');
            $variables['identity_pool_id'] = get_option('identity_pool_id_admin');
        }

        // template for images
        echo "<ul id='app' style='list-style-type: none; margin: 0px'></ul>";

        echo $args['after_widget'];

        wp_register_script('jrs3imagesjs', plugin_dir_url(__FILE__) . 'functions.js');
        wp_localize_script('jrs3imagesjs', 'document_obj', $variables);
        wp_enqueue_script('jrs3imagesjs');

        wp_enqueue_script( 'jrs3imagesadminjs', plugin_dir_url(__FILE__) . 'functions.admin.js');
    }

    /**
     * display the widget options in the Admin -> Appearance page
     */
    public function form($instance)
    {
        $title = (isset($instance['title'])) ? $instance['title'] : __($this->default_title,);
        $number_of_images = !empty($instance['number_of_images']) ? $instance['number_of_images'] : $this->default_number_of_images;
        
        require_once 'widget.form.phtml';
    }

    /**
     * show a form under settings to set params
     */
    public function create_plugin_settings_page()
    {
        require_once 'settings.phtml';
    }
}
