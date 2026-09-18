<aside class="sidebar">
<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
    <?php dynamic_sidebar( 'sidebar-1' ); ?>
<?php else : ?>
    <div class="widget">
        <h3 class="widget-title">Recent Posts</h3>
        <ul>
            <?php
            $sidebar_recent = new WP_Query( array(
                'posts_per_page'      => 5,
                'ignore_sticky_posts' => true,
            ) );
            if ( $sidebar_recent->have_posts() ) :
                while ( $sidebar_recent->have_posts() ) : $sidebar_recent->the_post();
                    echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </ul>
    </div>
    <div class="widget">
        <h3 class="widget-title">Add widgets here</h3>
        <p>This panel shows placeholder content. Add widgets under
        <em>Appearance &rarr; Widgets &rarr; Sidebar</em> to replace it.</p>
    </div>
<?php endif; ?>
<?php
$sidebar_blogroll = get_bookmarks( array(
    'orderby' => 'name',
    'order'   => 'ASC',
) );
if ( $sidebar_blogroll ) :
?>
    <div class="widget">
        <h3 class="widget-title">Blogroll</h3>
        <ul>
            <?php foreach ( $sidebar_blogroll as $sidebar_link ) : ?>
                <li><a href="<?php echo esc_url( $sidebar_link->link_url ); ?>"><?php echo esc_html( $sidebar_link->link_name ); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
</aside>
