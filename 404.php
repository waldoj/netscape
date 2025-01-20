<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <?php get_header(); ?>

    <div class="container">
        <h1><?php _e( '404 - Page Not Found', 'textdomain' ); ?></h1>
        <p><?php _e( 'Sorry, the page you are looking for does not exist.', 'textdomain' ); ?></p>
    </div>

    <?php get_footer(); ?>
</body>
</html>
