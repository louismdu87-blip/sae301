<?php
/* Itération 8. */
defined( 'ABSPATH' ) || exit;

function buissiere_email_pickup_instructions( $order, $sent_to_admin, $plain_text, $email ) {
    if ( $sent_to_admin || ! $order instanceof WC_Order || ! $email instanceof WC_Email || ! function_exists( 'get_field' ) ) {
        return;
    }
    $email_ids = array( 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice' );
    if ( ! $email->is_customer_email() || ! in_array( $email->id, $email_ids, true ) ) {
        return;
    }
    $has_pickup = false;
    foreach ( $order->get_shipping_methods() as $shipping ) {
        if ( in_array( $shipping->get_method_id(), array( 'pickup_location', 'local_pickup' ), true ) ) {
            $has_pickup = true;
            break;
        }
    }
    if ( ! $has_pickup ) {
        return;
    }
    $instructions = get_field( 'instructions_retrait', wc_get_page_id( 'checkout' ) );
    if ( ! is_string( $instructions ) || '' === trim( $instructions ) ) {
        return;
    }
    if ( $plain_text ) {
        echo "\nInstructions pour le retrait\n" . wp_strip_all_tags( $instructions ) . "\n\n";
    } else {
        echo '<div class="email-pickup"><h2>Instructions pour le retrait</h2><p>' . nl2br( esc_html( $instructions ) ) . '</p></div>';
    }
}
add_action( 'woocommerce_email_after_order_table', 'buissiere_email_pickup_instructions', 20, 4 );

/* Itération 9. */
function buissiere_completed_pickup_email_subject( $subject, $order ) {
    if ( ! $order instanceof WC_Order ) {
        return $subject;
    }
    foreach ( $order->get_shipping_methods() as $shipping ) {
        if ( in_array( $shipping->get_method_id(), array( 'pickup_location', 'local_pickup' ), true ) ) {
            return sprintf( 'Votre commande n°%s a été retirée en boutique — Buissière', $order->get_order_number() );
        }
    }
    return $subject;
}
add_filter( 'woocommerce_email_subject_customer_completed_order', 'buissiere_completed_pickup_email_subject', 10, 2 );
