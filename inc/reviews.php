<?php
/* Itération 14. */
defined( 'ABSPATH' ) || exit;

function buissiere_product_reviews() {
    global $product;
    if ( ! $product instanceof WC_Product || ! comments_open() || post_password_required() || 'yes' !== get_option( 'woocommerce_enable_reviews' ) ) {
        return;
    }
    echo '<section class="product-reviews" aria-label="Avis clients">';
    add_filter( 'gettext_woocommerce', 'buissiere_product_review_labels', 10, 2 );
    comments_template();
    remove_filter( 'gettext_woocommerce', 'buissiere_product_review_labels', 10 );
    echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'buissiere_product_reviews', 20 );

function buissiere_product_review_labels( $translation, $text ) {
    $labels = array(
        'Reviews' => 'Avis clients',
        'Your rating' => 'Votre note',
        'Your review' => 'Votre commentaire',
        'There are no reviews yet.' => 'Aucun avis pour le moment.',
    );
    return isset( $labels[ $text ] ) ? $labels[ $text ] : $translation;
}

function buissiere_product_reviews_title( $title, $count, $product ) {
    $summary = sprintf( _n( '%s avis', '%s avis', $count, 'buissiere' ), number_format_i18n( $count ) );
    if ( $product->get_rating_count() ) {
        $summary = sprintf( '%s sur 5 · %s', wc_format_localized_decimal( $product->get_average_rating() ), $summary );
    }
    return 'Avis clients<span class="product-reviews__summary">' . esc_html( $summary ) . '</span>';
}
add_filter( 'woocommerce_reviews_title', 'buissiere_product_reviews_title', 10, 3 );

function buissiere_product_review_form( $args ) {
    $args['title_reply'] = 'Votre avis compte';
    $args['title_reply_before'] = '<h2 id="reply-title" class="comment-reply-title product-reviews__form-title">';
    $args['title_reply_after'] = '</h2>';
    $args['label_submit'] = 'Publier mon avis';
    $args['class_submit'] = 'submit button button--filled product-reviews__submit';
    return $args;
}
add_filter( 'woocommerce_product_review_comment_form_args', 'buissiere_product_review_form' );
