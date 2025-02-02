<?php
// functions.php

function my_wordpress_theme_enqueue_styles() {
    wp_enqueue_style('my-theme-style', get_template_directory_uri() . '/assets/css/style.css');
}

add_action('wp_enqueue_scripts', 'my_wordpress_theme_enqueue_styles');

add_action('after_setup_theme', function() {
    // Enable featured images
    add_theme_support('post-thumbnails');
    
    // Add custom image sizes if needed
    add_image_size('featured-large', 1200, 800, true);
    add_image_size('featured-medium', 800, 600, true);
    
    // Add RSS feed support
    add_theme_support('automatic-feed-links');
    
    // Add site icon support
    add_theme_support('site-icon');
    
    // Add title tag support
    add_theme_support('title-tag');
});
?>