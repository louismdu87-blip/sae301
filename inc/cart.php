<?php
/* Itération 5. */
defined( 'ABSPATH' ) || exit;

function buissiere_cart_title( $title, $post_id ) {
    if ( function_exists( 'is_cart' ) && is_cart() && in_the_loop() && is_main_query() && wc_get_page_id( 'cart' ) === (int) $post_id ) {
        return 'Votre panier';
    }
    return $title;
}
add_filter( 'the_title', 'buissiere_cart_title', 10, 2 );

function buissiere_cart_progress() {
    ?>
    <ol class="cart-page__steps" aria-label="Étapes de la commande">
        <li class="cart-page__step cart-page__step--current" aria-current="step"><span class="cart-page__step-number">1</span>Panier</li>
        <li class="cart-page__step"><span class="cart-page__step-number">2</span>Commander</li>
        <li class="cart-page__step"><span class="cart-page__step-number">3</span>Confirmation</li>
    </ol>
    <?php
}
add_action( 'woocommerce_cart_is_empty', 'buissiere_cart_progress', 1 );

function buissiere_cart_open() {
    buissiere_cart_progress();
    echo '<div class="cart-page__layout">';
}
add_action( 'woocommerce_before_cart', 'buissiere_cart_open' );

function buissiere_cart_close() {
    ?>
    </div>
    <p class="cart-page__support">Une question sur votre commande ? <a href="tel:+33555341044">05 55 34 10 44</a></p>
    <?php
}
add_action( 'woocommerce_after_cart', 'buissiere_cart_close' );

function buissiere_cart_return() {
    ?>
    <a class="cart-page__return" href="<?php echo esc_url( home_url( '/#nos-produits' ) ); ?>">← Retour aux produits : Accueil · Nos produits</a>
    <?php
}
add_action( 'woocommerce_after_cart_table', 'buissiere_cart_return' );
add_action( 'woocommerce_after_cart_totals', function () {
    if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) {
        echo '<p class="cart-page__guest">Commande possible sans créer de compte.</p>';
    }
} );
add_action( 'woocommerce_proceed_to_checkout', function () {
    echo '<p class="cart-page__delivery">Retrait gratuit en chocolaterie ou livraison à domicile.<br>L’adresse et le mode de réception sont à choisir à l’étape suivante.</p>';
}, 5 );
add_action( 'wp', function () {
    if ( is_cart() ) {
        remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
    }
} );

add_filter( 'woocommerce_cart_item_quantity', function ( $html, $key, $item ) {
    if ( ! is_cart() || $item['data']->is_sold_individually() ) {
        return $html;
    }
    $name = $item['data']->get_name();
    return '<div class="cart-quantity">'
        . '<button class="cart-quantity__button" type="button" data-cart-step="-1" aria-label="' . esc_attr( 'Diminuer la quantité de ' . $name ) . '">−</button>'
        . $html
        . '<button class="cart-quantity__button" type="button" data-cart-step="1" aria-label="' . esc_attr( 'Augmenter la quantité de ' . $name ) . '">+</button>'
        . '</div>';
}, 10, 3 );
add_filter( 'woocommerce_cart_item_remove_link', function ( $html, $key ) {
    if ( ! is_cart() ) {
        return $html;
    }
    $item = WC()->cart->get_cart_item( $key );
    return '<a class="remove cart-page__remove" role="button" href="' . esc_url( wc_get_cart_remove_url( $key ) ) . '" aria-label="' . esc_attr( 'Supprimer ' . $item['data']->get_name() . ' du panier' ) . '">× Supprimer</a>';
}, 10, 2 );

add_action( 'woocommerce_cart_coupon', function () {
    echo '<span class="cart-page__coupon-label" aria-hidden="true">Code promo</span>';
} );

/* Itération 10. */
add_filter( 'gettext_woocommerce', function ( $translation, $text ) {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
        return $translation;
    }
    if ( 'Coupon code' === $text ) {
        return 'Saisir votre code';
    }
    if ( 'Coupon:' === $text ) {
        return 'Code promo';
    }
    return $translation;
}, 10, 2 );

add_filter( 'woocommerce_cart_totals_coupon_label', function ( $label, $coupon ) {
    if ( ! is_cart() ) {
        return $label;
    }
    return '<span class="cart-page__discount-label">Code promo : <span class="cart-page__discount-code">' . esc_html( $coupon->get_code() ) . '</span></span>';
}, 10, 2 );

function buissiere_cart_checkout_button() {
    echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="checkout-button button alt wc-forward">Passer commande</a>';
}
add_action( 'after_setup_theme', function () {
    remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
    add_action( 'woocommerce_proceed_to_checkout', 'buissiere_cart_checkout_button', 20 );
}, 100 );

add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
    if ( ! is_cart() || ! $item['data']->is_type( 'variation' ) ) {
        return $name;
    }
    $product = wc_get_product( $item['product_id'] );
    $format = $item['data']->get_attribute( 'format' );
    if ( false === strpos( $name, '<a ' ) ) {
        return esc_html( $product->get_name() );
    }
    $html = '<a href="' . esc_url( $item['data']->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
    if ( $format ) {
        $html .= '<ul class="cart-page__options"><li>Format : ' . esc_html( $format ) . '</li></ul>';
    }
    return $html;
}, 10, 2 );
