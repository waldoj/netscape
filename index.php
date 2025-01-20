<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo wp_get_document_title(); ?></title>
    <?php wp_site_icon(); ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <?php get_header(); ?>

    <div class="container">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="post">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
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
                </div>
                <hr />
            <?php endwhile; ?>
        <?php else : ?>
            <p><?php _e( 'Sorry, no posts matched your criteria.', 'textdomain' ); ?></p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php
        echo paginate_links( array(
            'total' => $wp_query->max_num_pages
        ) );
        ?>
    </div>

    <?php get_footer(); ?>
</body>
</html>
