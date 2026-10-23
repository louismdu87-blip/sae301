<?php
/* Itération 7. */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', function ( $classes ) {
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        $classes[] = 'account-page';
        $classes[] = is_user_logged_in() ? 'account-page--signed-in' : 'account-page--signed-out';
    }
    return $classes;
} );

function buissiere_account_title( $title, $post_id ) {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! in_the_loop() || ! is_main_query() || wc_get_page_id( 'myaccount' ) !== (int) $post_id ) {
        return $title;
    }
    if ( is_wc_endpoint_url( 'lost-password' ) ) { return 'Compte — Mot de passe oublié'; }
    if ( ! is_user_logged_in() ) { return 'Compte — Connexion et inscription'; }
    foreach ( array(
        'orders' => 'Historique des commandes',
        'view-order' => 'Détail de la commande',
        'edit-address' => 'Mes adresses',
        'edit-account' => 'Détails du compte',
    ) as $endpoint => $label ) {
        if ( is_wc_endpoint_url( $endpoint ) ) { return 'Compte — ' . $label; }
    }
    return 'Compte — Tableau de bord';
}
add_filter( 'the_title', 'buissiere_account_title', 10, 2 );

function buissiere_account_progress() {
    $current = ! is_user_logged_in() ? 1 : ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ? 3 : 2 );
    $steps = array( 1 => 'Compte', 2 => 'Mon compte', 3 => 'Historique des commandes' );
    echo '<ol class="account-page__steps" aria-label="Parcours du compte client">';
    foreach ( $steps as $index => $label ) {
        echo '<li class="account-page__step' . ( $index === $current ? ' account-page__step--current' : '' ) . '"' . ( $index === $current ? ' aria-current="step"' : '' ) . '><span class="account-page__step-number">' . esc_html( $index ) . '</span>' . esc_html( $label ) . '</li>';
    }
    echo '</ol>';
}
add_action( 'woocommerce_before_customer_login_form', 'buissiere_account_progress' );

function buissiere_account_sidebar_open() {
    buissiere_account_progress();
    $user = wp_get_current_user();
    echo '<aside class="account-page__sidebar" aria-label="Espace client"><div class="account-page__identity"><p class="account-page__name">' . esc_html( $user->display_name ) . '</p><p class="account-page__caption">Compte client</p></div>';
}
add_action( 'woocommerce_before_account_navigation', 'buissiere_account_sidebar_open' );
add_action( 'woocommerce_after_account_navigation', function () { echo '</aside>'; } );

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
    return array_intersect_key( $items, array_flip( array( 'dashboard', 'orders', 'edit-address', 'edit-account', 'customer-logout' ) ) );
} );

function buissiere_account_required_hint() {
    echo '<p class="account-page__required">Les champs marqués * sont obligatoires.</p>';
}
add_action( 'woocommerce_login_form_start', 'buissiere_account_required_hint' );
add_action( 'woocommerce_register_form_start', 'buissiere_account_required_hint' );

function buissiere_account_content() {
    if ( ! is_wc_endpoint_url() ) {
        get_template_part( 'template-parts/account-dashboard' );
    } else {

        woocommerce_account_content();
    }
}
add_action( 'after_setup_theme', function () {
    remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
    add_action( 'woocommerce_account_content', 'buissiere_account_content' );
}, 100 );
