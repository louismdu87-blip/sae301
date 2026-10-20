<?php
/** Catalogue de l’itération 2 : les données restent gérées dans WooCommerce. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="catalogue">
    <div class="catalogue__hero">
        <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/hero-buissiere.jpg' ); ?>" alt="Assortiment de chocolats Buissière" fetchpriority="high" width="5616" height="2551">
        <p class="catalogue__intro">Buissière, mythique chocolaterie-confiserie de Limoges, depuis 1948</p>
    </div>
    <div class="catalogue__content" id="nos-produits">
        <div class="catalogue__heading">
            <h1 class="catalogue__title">Nos produits</h1>
        </div>
        <?php
        $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
        $order = array( 'le-chocolat', 'la-confiserie', 'noel' );
        usort( $categories, function ( $a, $b ) use ( $order ) {
            $a_index = array_search( $a->slug, $order, true );
            $b_index = array_search( $b->slug, $order, true );
            return ( false === $a_index ? 99 : $a_index ) <=> ( false === $b_index ? 99 : $b_index );
        } );
        $displayed = array();
        foreach ( $categories as $category ) :
            $products = wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'category' => array( $category->slug ), 'orderby' => 'ID', 'order' => 'ASC' ) );
            $products = array_filter( $products, function ( $item ) use ( $displayed ) {
                return ! in_array( $item->get_id(), $displayed, true ) && in_array( $item->get_catalog_visibility(), array( 'visible', 'catalog' ), true ) && $item->is_visible();
            } );
            if ( ! $products ) { continue; }
            ?>
            <section class="catalogue__category" aria-labelledby="category-<?php echo esc_attr( $category->term_id ); ?>">
                <h2 class="catalogue__category-title" id="category-<?php echo esc_attr( $category->term_id ); ?>"><a href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a></h2>
                <ul class="catalogue__grid">
                    <?php foreach ( $products as $item ) {
                        $displayed[] = $item->get_id();
                        $GLOBALS['post'] = get_post( $item->get_id() );
                        setup_postdata( $GLOBALS['post'] );
                        $GLOBALS['product'] = $item;
                        wc_get_template_part( 'content', 'product' );
                    }
                    wp_reset_postdata();
                    unset( $GLOBALS['product'] ); ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php get_footer(); ?>
