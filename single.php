<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo get_the_title() . ' | ' . wp_get_document_title(); ?></title>
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
                        <?php the_post_thumbnail('large'); ?>
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
