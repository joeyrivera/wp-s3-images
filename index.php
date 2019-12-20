<?php
/**
 * Plugin Name: JR Image Upload
 * Plugin URI: http://www.mywebsite.com/my-first-plugin
 * Description: Allows admin to upload images to S3
 * Version: 1.0
 * Author: Joey Rivera
 * Author URI: http://www.joeyrivera.com
 */

add_action( 'the_content', 'my_thank_you_text' );

function my_thank_you_text ( $content ) {
    return $content .= '<p>Thank you for reading!</p>';
}