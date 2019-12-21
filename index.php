<?php
/**
 * Plugin Name: JR Image Upload
 * Plugin URI: http://www.mywebsite.com/my-first-plugin
 * Description: Allows admin to upload images to S3
 * Version: 1.1
 * Author: Joey Rivera
 * Author URI: http://www.joeyrivera.com
 */

require_once 'Jr_S3_Image_List.php';

// register My_Widget
add_action( 'widgets_init', function(){
	register_widget( 'Jr_S3_Image_List' );
});