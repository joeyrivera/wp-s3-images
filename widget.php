<?php

namespace Jr\S3\Images;

/**
 * Plugin that allows uploading and listing images in an 
 * S3 bucket
 */
class Widget extends \WP_Widget
{
    /**
     * default title to use for the widget
     */
    protected $default_title = 'Recent Images';

    /**
     * default number of images to display in the widget
     */
    protected $default_number_of_images = 12;

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
        wp_enqueue_style( 'jrs3imagescss', plugins_url('public/css/styles.css', __FILE__));

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
            require_once 'views/upload.phtml';

            // use elevated policy if admin
            $variables['queue_url'] = get_option('queue_url');
            $variables['identity_pool_id'] = get_option('identity_pool_id_admin');
        }

        // template for images
        echo "<div id='app'></div>";

        echo $args['after_widget'];

        wp_register_script('jrs3imagesjs', plugins_url('public/js/functions.js', __FILE__));
        wp_localize_script('jrs3imagesjs', 'document_obj', $variables);
        wp_enqueue_script('jrs3imagesjs');

        wp_enqueue_script( 'jrs3imagesadminjs', plugins_url('public/js/functions.admin.js', __FILE__));
    }

    /**
     * display the widget options in the Admin -> Appearance page
     */
    public function form($instance)
    {
        $title = (isset($instance['title'])) ? $instance['title'] : __($this->default_title,);
        $number_of_images = !empty($instance['number_of_images']) ? $instance['number_of_images'] : $this->default_number_of_images;
        ?>
        <p>
            <label for="<?= $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?= $this->get_field_id('title'); ?>" name="<?= $this->get_field_name('title'); ?>" type="text" value="<?= esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?= esc_attr($this->get_field_id('number_of_images')); ?>"><?php esc_attr_e('Number of images to show:', 'text_domain'); ?></label>
            <input class="tiny-text" id="<?= esc_attr($this->get_field_id('number_of_images')); ?>" name="<?= esc_attr($this->get_field_name('number_of_images')); ?>" type="number" value="<?= esc_attr($number_of_images); ?>" step="1" min="1" size="3">
        </p>
        <?php
    }

    /**
     * show a form under settings to set params
     */
    public function create_plugin_settings_page()
    {
        require_once 'views/settings.phtml';
    }
}
