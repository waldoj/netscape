<?php
// functions.php

function my_wordpress_theme_enqueue_styles() {
    wp_enqueue_style('my-theme-style', get_template_directory_uri() . '/assets/css/style.css');
}

add_action('wp_enqueue_scripts', 'my_wordpress_theme_enqueue_styles');
?>