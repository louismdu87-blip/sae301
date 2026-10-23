<?php
/* Itération 15. */
defined( 'ABSPATH' ) || exit;

function buissiere_cart_preparation_fee( $cart ) {
    if ( ( is_admin() && ! wp_doing_ajax() ) || ! $cart instanceof WC_Cart || $cart->is_empty() || ! function_exists( 'get_field' ) ) {
        return;
    }

    $threshold = get_field( 'seuil_frais_preparation', 'option' );
    $amount    = get_field( 'montant_frais_preparation', 'option' );
    if ( ! is_numeric( $threshold ) || ! is_numeric( $amount ) ) {
        return;
    }

    $decimals  = wc_get_price_decimals();
    $threshold = round( (float) $threshold, $decimals );
    $amount    = round( (float) $amount, $decimals );
    if ( $threshold <= 0 || $amount <= 0 ) {
        return;
    }

    $products_total = round( (float) $cart->get_cart_contents_total(), $decimals );
    if ( $products_total < $threshold ) {
        $cart->add_fee( __( 'Frais de préparation', 'buissiere' ), $amount, false );
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'buissiere_cart_preparation_fee' );
