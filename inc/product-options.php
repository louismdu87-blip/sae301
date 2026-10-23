<?php
/* Itération 3. */
defined( 'ABSPATH' ) || exit;

function buissiere_variable_quantity_open() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    ?>
    <div class="product-options__quantity">
        <div class="product-options__stepper">
            <button class="product-options__quantity-button" type="button" data-quantity-step="-1" aria-label="Diminuer la quantité" disabled>−</button>
    <?php
}

function buissiere_variable_quantity_close() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    ?>
            <button class="product-options__quantity-button" type="button" data-quantity-step="1" aria-label="Augmenter la quantité">+</button>
        </div>
    </div>
    <?php
}

function buissiere_variable_product_formats() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    echo '<div class="product-options">';
    foreach ( $product->get_variation_attributes() as $attribute => $options ) {
        $key = sanitize_title( $attribute );
        $label_id = 'product-options-' . $product->get_id() . '-' . $key;
        $label = 'format' === $key ? 'Taille' : wc_attribute_label( $attribute, $product );
        ?>
        <div class="product-options__formats" data-attribute="<?php echo esc_attr( wc_variation_attribute_name( $attribute ) ); ?>" hidden>
            <p class="product-options__label" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $label ); ?></p>
            <ul class="product-options__choices" role="radiogroup" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
                <?php foreach ( $options as $option ) :
                    $option_label = $option;
                    if ( taxonomy_exists( $attribute ) ) {
                        $term = get_term_by( 'slug', $option, $attribute );
                        if ( $term ) { $option_label = $term->name; }
                    }
                    ?>
                    <li class="product-options__item">
                        <label class="product-options__option">
                            <input class="product-options__radio" type="radio" name="buissiere-<?php echo esc_attr( $product->get_id() . '-' . $key ); ?>" value="<?php echo esc_attr( $option ); ?>">
                            <span class="product-options__choice"><?php echo esc_html( $option_label ); ?></span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    echo '</div>';
}

add_filter( 'woocommerce_available_variation', function ( $data, $product, $variation ) {
    $format = $variation->get_attribute( 'format' );
    $data['price_html'] = $variation->get_price_html();
    if ( $format ) {
        $data['price_html'] .= '<span class="product-options__price-meta">TTC · ' . esc_html( $format ) . '</span>';
    }
    $quantity = $variation->get_stock_quantity();
    if ( ! $variation->is_in_stock() ) {
        $state = 'out';
        $message = 'Épuisé';
    } elseif ( $variation->managing_stock() && null !== $quantity && $quantity <= wc_get_low_stock_amount( $variation ) ) {
        $state = 'low';
        $message = sprintf( 'Stock faible · %s restant(s)', wc_format_stock_quantity_for_display( $quantity, $variation ) );
    } else {
        $state = 'available';
        $message = 'Disponible';
    }
    $data['availability_html'] = '<p class="product-options__availability product-options__availability--' . esc_attr( $state ) . '">' . esc_html( $message ) . '</p>';
    return $data;
}, 10, 3 );
