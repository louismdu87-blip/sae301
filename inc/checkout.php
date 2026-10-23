<?php
/* Itération 6. */
defined( 'ABSPATH' ) || exit;

function buissiere_enqueue_pickup_instructions() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() || ! function_exists( 'get_field' ) ) {
        return;
    }
    $instructions = get_field( 'instructions_retrait', wc_get_page_id( 'checkout' ) );
    if ( ! is_string( $instructions ) || '' === trim( $instructions ) ) {
        return;
    }
    wp_enqueue_script(
        'buissiere-pickup-instructions',
        get_stylesheet_directory_uri() . '/assets/js/pickup-instructions.js',
        array( 'wp-element', 'wp-plugins', 'wc-blocks-checkout' ),
        filemtime( get_stylesheet_directory() . '/assets/js/pickup-instructions.js' ),
        array( 'in_footer' => true, 'strategy' => 'defer' )
    );
    wp_localize_script( 'buissiere-pickup-instructions', 'buissierePickupInstructions', array(
        'title' => 'Instructions pour le retrait',
        'text'  => $instructions,
    ) );
}
add_action( 'wp_enqueue_scripts', 'buissiere_enqueue_pickup_instructions', 150 );
