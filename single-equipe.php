<?php
/* Itération 11. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="site-main team-page">
    <?php $team_url = get_post_type_archive_link( 'equipe' ); ?>
    <?php if ( $team_url ) : ?>
        <p class="team-page__back"><a class="back-link" href="<?php echo esc_url( $team_url ); ?>"><span aria-hidden="true">←</span> Retour à l’équipe</a></p>
    <?php endif; ?>
    <?php while ( have_posts() ) : the_post(); ?>
        <?php $function = get_post_meta( get_the_ID(), 'fonction', true ); ?>
        <article class="team-profile" aria-labelledby="team-profile-title">
            <div class="team-profile__layout">
                <?php if ( ! post_password_required() && has_post_thumbnail() ) : ?>
                    <div class="team-profile__visual">
                        <?php the_post_thumbnail( 'large', array( 'class' => 'team-profile__image' ) ); ?>
                    </div>
                <?php endif; ?>
                <div class="team-profile__content">
                    <header class="team-profile__header">
                        <p class="team-profile__eyebrow">Notre équipe</p>
                        <h1 class="team-profile__name" id="team-profile-title"><?php echo esc_html( get_the_title() ); ?></h1>
                        <?php if ( ! post_password_required() && is_string( $function ) && '' !== trim( $function ) ) : ?>
                            <p class="team-profile__function"><?php echo esc_html( $function ); ?></p>
                        <?php endif; ?>
                    </header>
                    <?php
                    the_content();
                    wp_link_pages( array( 'before' => '<nav class="team-profile__pages" aria-label="Pages de la présentation">', 'after' => '</nav>' ) );
                    ?>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
