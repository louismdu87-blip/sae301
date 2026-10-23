<?php
/* Itération 13. */
defined( 'ABSPATH' ) || exit;

function buissiere_product_recommendations( $ids, $context ) {
    $items = array();
    $ids = wp_parse_id_list( $ids );
    if ( $ids ) {
        _prime_post_caches( $ids );
    }
    foreach ( $ids as $id ) {
        $item = wc_get_product( $id );
        if ( $item && $item->is_visible() && $item->is_in_stock() && $item->is_purchasable() ) {
            $items[] = $item;
            if ( 2 === count( $items ) ) {
                break;
            }
        }
    }

    echo '<div class="product-recommendations product-recommendations--' . esc_attr( $context ) . '">';
    if ( $items ) {
        $original_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
        $original_loop = isset( $GLOBALS['woocommerce_loop'] ) ? $GLOBALS['woocommerce_loop'] : null;
        wc_setup_loop( array( 'name' => 'cross-sells', 'columns' => 2 ) );
        ?>
        <section class="product-recommendations__section" aria-labelledby="recommendations-<?php echo esc_attr( $context ); ?>">
            <h2 class="product-recommendations__title" id="recommendations-<?php echo esc_attr( $context ); ?>">Pour prolonger le plaisir</h2>
            <p class="product-recommendations__intro">Des gourmandises à découvrir en complément de votre sélection.</p>
            <?php
            add_filter( 'woocommerce_product_cross_sells_products_heading', '__return_false', 999 );
            wc_get_template( 'cart/cross-sells.php', array( 'cross_sells' => $items ) );
            remove_filter( 'woocommerce_product_cross_sells_products_heading', '__return_false', 999 );
            if ( 'product' === $context ) {
                echo '<p class="product-recommendations__return"><a class="back-link" href="' . esc_url( home_url( '/#nos-produits' ) ) . '"><span aria-hidden="true">←</span> Retour aux produits</a></p>';
            }
            ?>
        </section>
        <?php
        $GLOBALS['product'] = $original_product;
        if ( null === $original_loop ) {
            wc_reset_loop();
        } else {
            $GLOBALS['woocommerce_loop'] = $original_loop;
        }
    }
    echo '</div>';
}

function buissiere_single_product_recommendations() {
    global $product;
    if ( ! $product instanceof WC_Product || post_password_required() ) {
        return;
    }
    buissiere_product_recommendations( array_diff( $product->get_cross_sell_ids(), array( $product->get_id() ) ), 'product' );
}
add_action( 'woocommerce_after_single_product_summary', 'buissiere_single_product_recommendations', 25 );

function buissiere_cart_product_recommendations() {
    if ( WC()->cart && ! WC()->cart->is_empty() ) {
        buissiere_product_recommendations( WC()->cart->get_cross_sells(), 'cart' );
    }
}
add_action( 'woocommerce_after_cart', 'buissiere_cart_product_recommendations', 20 );

function buissiere_recommendation_card_open() {
    if ( 'cross-sells' === wc_get_loop_prop( 'name' ) ) {
        echo '<div class="product-card">';
    }
}
add_action( 'woocommerce_before_shop_loop_item', 'buissiere_recommendation_card_open', 5 );

function buissiere_recommendation_card_close() {
    global $product;
    if ( 'cross-sells' === wc_get_loop_prop( 'name' ) ) {
        echo '</div><p class="product-recommendations__price">' . wp_kses_post( $product->get_price_html() ) . '</p>';
    }
}
add_action( 'woocommerce_after_shop_loop_item', 'buissiere_recommendation_card_close', 20 );
