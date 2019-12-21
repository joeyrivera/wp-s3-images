<?php

class Jr_S3_Image_List extends WP_Widget
{
    protected $default_title = 'Images';
    protected $default_number_of_images = 5;

    // class constructor
    public function __construct()
    {
        $widget_ops = array(
            'classname' => 'jr_s3_image_list',
            'description' => 'A plugin to list images from an S3 bucket',
        );
        parent::__construct('jr_s3_image_list', 'Jr S3 Image List', $widget_ops);
    }

    // output the widget content on the front-end
    public function widget($args, $instance)
    {
        wp_enqueue_script('awssdk', 'https://sdk.amazonaws.com/js/aws-sdk-2.283.1.min.js');
        wp_register_script('jrimageuploadjs', plugin_dir_url(__FILE__) . 'functions.js');
        $variables = [
            'bucket_name' => "",
            'bucket_region' => "",
            'identity_pool_id' => "",
            'number_of_images' => ($instance['number_of_images'] ?? $this->default_number_of_images)
        ];
        wp_localize_script('jrimageuploadjs', 'document_obj', $variables);
        wp_enqueue_script('jrimageuploadjs');

        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            $instance['title'] = apply_filters('widget_title', $instance['title']);
            $title = $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
        } else {
            $title = $args['before_title'] . esc_html($this->default_title) . $args['after_title'];
        }

        echo $title;
        echo "<ul id='app' style='list-style-type: none; margin: 0px'></ul>";

        echo $args['after_widget'];
    }

    // output the option form field in admin Widgets screen
    public function form($instance)
    {
        $title = (isset($instance['title'])) ? $instance['title'] : __($this->default_title, 'jetpack');
        $number_of_images = !empty($instance['number_of_images']) ? $instance['number_of_images'] : $this->default_number_of_images;
?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'jetpack'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_of_images')); ?>"><?php esc_attr_e('Number of images to show:', 'text_domain'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number_of_images')); ?>" name="<?php echo esc_attr($this->get_field_name('number_of_images')); ?>" type="number" value="<?php echo esc_attr($number_of_images); ?>" step="1" min="1" size="3">
        </p>
<?php
    }

    // save options
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['number_of_images'] = (!empty($new_instance['number_of_images'])) ? intval($new_instance['number_of_images']) : '';

        return $instance;
    }
}
