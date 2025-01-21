<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo wp_get_document_title(); ?></title>
    <?php wp_site_icon(); ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php the_title(); ?>">
    <meta name="twitter:description" content="<?php echo get_the_excerpt(); ?>">
    <?php if ( has_post_thumbnail() ) : ?>
        <?php $thumbnail_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' ); ?>
        <meta name="twitter:image" content="<?php echo $thumbnail_url[0]; ?>">
    <?php endif; ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php the_title(); ?>">
    <meta property="og:description" content="<?php echo get_the_excerpt(); ?>">
    <meta property="og:url" content="<?php the_permalink(); ?>">
    <?php if ( has_post_thumbnail() ) : ?>
        <?php $thumbnail_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' ); ?>
        <meta property="og:image" content="<?php echo $thumbnail_url[0]; ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?php bloginfo('name'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <?php get_header(); ?>

    <div class="container">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <div class="single-post">
                <h1><?php the_title(); ?></h1>
                <div class="post-meta">
                    <span class="date"><?php the_date(); ?></span>
                </div>
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="featured-image">
                        <?php the_post_thumbnail('featured-large'); ?>
                        <?php if ( get_the_post_thumbnail_caption() ) : ?>
                            <figcaption class="featured-image-caption">
                                <?php echo get_the_post_thumbnail_caption(); ?>
                            </figcaption>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="content">
                    <?php the_content(); ?>
                </div>
                <?php comments_template(); ?>
            </div>
        <?php endwhile; endif; ?>
    </div>
    
    <div class="last-modified">
        <p>Last modified: <?php the_modified_date(); ?></p>
    </div>

<?php get_footer(); ?>
    
</body>
</html>
