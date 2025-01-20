<header>
    <div class="container">
        <h1><a class="blog-name" href="/"><?php bloginfo('name'); ?></a></h1>
        
        <nav>
            <?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
        </nav>
        
        <div class="search-form">
            <?php get_search_form(); ?>
        </div>
    </div>
</header>
