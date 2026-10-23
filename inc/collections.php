<?php
/* Itération 12. */
defined( 'ABSPATH' ) || exit;

function buissiere_collection_navigation() {
    if ( is_tax( 'product_collection' ) ) {
        get_template_part( 'template-parts/collections-navigation' );
    }
}
add_action( 'woocommerce_archive_description', 'buissiere_collection_navigation', 20 );
