<?php
/** US-12.3 : accès aux archives natives des collections de produits. */
defined( 'ABSPATH' ) || exit;
if ( ! taxonomy_exists( 'product_collection' ) ) {
    return;
}
$collections = get_terms( array( 'taxonomy' => 'product_collection', 'hide_empty' => true ) );
if ( is_wp_error( $collections ) || ! $collections ) {
    return;
}
$links = array();
foreach ( $collections as $collection ) {
    $url = get_term_link( $collection );
    if ( ! is_wp_error( $url ) ) {
        $links[] = array( 'term' => $collection, 'url' => $url );
    }
}
if ( ! $links ) {
    return;
}
?>
<nav class="collection-navigation" id="collections" aria-labelledby="collections-title">
    <h2 class="collection-navigation__title" id="collections-title">Nos collections</h2>
    <ul class="collection-navigation__list">
        <?php foreach ( $links as $link ) : ?>
            <?php $current = is_tax( 'product_collection', $link['term']->term_id ); ?>
            <li class="collection-navigation__item">
                <a class="button collection-navigation__link<?php echo $current ? ' button--filled' : ''; ?>" href="<?php echo esc_url( $link['url'] ); ?>"<?php if ( $current ) : ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $link['term']->name ); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ( is_tax( 'product_collection' ) ) : ?>
        <a class="back-link" href="<?php echo esc_url( home_url( '/#nos-produits' ) ); ?>"><span aria-hidden="true">←</span> Tous les produits</a>
    <?php endif; ?>
</nav>
