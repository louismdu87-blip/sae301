<?php
/* Itération 11. */
defined( 'ABSPATH' ) || exit;
$function = get_post_meta( get_the_ID(), 'fonction', true );
$protected = post_password_required();
?>
<article class="team-card" aria-labelledby="team-member-<?php the_ID(); ?>">
    <?php if ( ! $protected && has_post_thumbnail() ) : ?>
        <div class="team-card__visual">
            <?php the_post_thumbnail( 'medium_large', array( 'class' => 'team-card__image' ) ); ?>
        </div>
    <?php endif; ?>
    <div class="team-card__body">
        <h2 class="team-card__name" id="team-member-<?php the_ID(); ?>">
            <a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
        </h2>
        <?php if ( ! $protected && is_string( $function ) && '' !== trim( $function ) ) : ?>
            <p class="team-card__function"><?php echo esc_html( $function ); ?></p>
        <?php endif; ?>
        <?php if ( ! $protected ) : ?>
            <div class="team-card__excerpt"><?php the_excerpt(); ?></div>
        <?php endif; ?>
        <a class="button team-card__link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( 'Découvrir le profil de ' . get_the_title() ); ?>">Découvrir le profil</a>
    </div>
</article>
