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
        $variables = [
            'bucket_name' => get_option('bucket_name'),
            'bucket_region' => get_option('bucket_region'),
            'identity_pool_id' => get_option('identity_pool_id_public'),
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
?>
            <style>
                #drop-area {
                    border: 2px dashed #ccc;
                    border-radius: 20px;
                    width: 100%;
                    font-family: sans-serif;
                    margin: 0 0 20px 0;
                    padding: 20px;
                }

                #drop-area.highlight {
                    border-color: purple;
                }

                #progress-bar {
                    width: 100%;
                }

                #drop-zone .hidden {
                    display: none;
                }
            </style>

            <div id="drop-zone">
                <div id="drop-area" ondrop="dropHandler(event);" ondragover="dragOverHandler(event);">
                    <p>Drag images here to upload</p>
                </div>

                <div class="hidden">
                    <span id="progress-text">Upload Progress:</span>
                    <progress id="progress-bar" max=100 value=0></progress>
                </div>
            </div>
        <?php

            // use elevated policy if admin
            $variables['identity_pool_id'] = get_option('identity_pool_id_admin');
        }

        // template for images
        echo "<ul id='app' style='list-style-type: none; margin: 0px'></ul>";

        echo $args['after_widget'];

        // register JS files
        wp_enqueue_script('awssdk', 'https://sdk.amazonaws.com/js/aws-sdk-2.283.1.min.js');
        wp_register_script('jrs3imagesjs', plugin_dir_url(__FILE__) . 'functions.js');
        wp_localize_script('jrs3imagesjs', 'document_obj', $variables);
        wp_enqueue_script('jrs3imagesjs');
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
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_of_images')); ?>"><?php esc_attr_e('Number of images to show:', 'text_domain'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number_of_images')); ?>" name="<?php echo esc_attr($this->get_field_name('number_of_images')); ?>" type="number" value="<?php echo esc_attr($number_of_images); ?>" step="1" min="1" size="3">
        </p>
    <?php
    }

    /**
     * show a form under settings to set params
     */
    public function create_plugin_settings_page()
    {
    ?>
        <div>
            <h2>Jr S3 Images</h2>
            <form method="post" action="options.php">
                <?php settings_fields('jrs3images_option_group'); ?>
                <h3>Settings</h3>
                <p>Configure the following to load and upload images to the correct S3 buckets.</p>
                <table>
                    <tr valign="top">
                        <th scope="row"><label for="bucket_name">S3 Bucket Name</label></th>
                        <td><input type="text" id="bucket_name" name="bucket_name" value="<?php echo get_option('bucket_name'); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="bucket_region">S3 Bucket Region</label></th>
                        <td><input type="text" id="bucket_region" name="bucket_region" value="<?php echo get_option('bucket_region'); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="identity_pool_id_public">Identity Pool Id Public</label></th>
                        <td><input class="regular-text" type="text" id="identity_pool_id_public" name="identity_pool_id_public" value="<?php echo get_option('identity_pool_id_public'); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="identity_pool_id_admin">Identity Pool Id Private</label></th>
                        <td><input class="regular-text" type="text" id="identity_pool_id_admin" name="identity_pool_id_admin" value="<?php echo get_option('identity_pool_id_admin'); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }
}
