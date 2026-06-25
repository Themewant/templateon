<?php
/**
 * Single template card.
 *
 * @var array $templateon_template
 * @var array $favorites
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$templa_slug            = $templateon_template['slug'];
$templateon_has_xml     = ! empty( $templateon_template['xml'] );
$templateon_badge       = isset( $templateon_template['badge'] ) ? trim( (string) $templateon_template['badge'] ) : '';
$templateon_is_favorite = in_array( $templa_slug, (array) $favorites, true );


$templateon_card_type = apply_filters( 'templa_card_type', 'free', $templateon_template );

$templateon_cat_slugs = [];
$templateon_cat_names = [];

foreach ( (array) $templateon_template['categories'] as $templateon_cat_entry ) {
    if ( is_array( $templateon_cat_entry ) ) {
        if ( ! empty( $templateon_cat_entry['slugs'] ) ) {
            foreach ( (array) $templateon_cat_entry['slugs'] as $templa_slug_alias ) {
                $templateon_cat_slugs[] = sanitize_title( $templa_slug_alias );
            }
        }
        if ( ! empty( $templateon_cat_entry['name'] ) ) {
            $templateon_cat_names[] = (string) $templateon_cat_entry['name'];
        }
    } else {
        $templateon_cat_slugs[] = sanitize_title( (string) $templateon_cat_entry );
        $templateon_cat_names[] = (string) $templateon_cat_entry;
    }
}

$templateon_cat_slugs   = array_values( array_unique( array_filter( $templateon_cat_slugs ) ) );
$templateon_cat_attr    = implode( ' ', $templateon_cat_slugs );
$templateon_cat_labels  = implode( ', ', array_filter( $templateon_cat_names ) );
?>
<article
    class="templa-card"
    data-slug="<?php echo esc_attr( $templa_slug ); ?>"
    data-name="<?php echo esc_attr( strtolower( $templateon_template['name'] ) ); ?>"
    data-type="<?php echo esc_attr( $templateon_card_type ); ?>"
    data-categories="<?php echo esc_attr( $templateon_cat_attr ); ?>"
    data-favorite="<?php echo $templateon_is_favorite ? '1' : '0'; ?>"
    data-preview="<?php echo esc_url( $templateon_template['preview'] ); ?>"
    data-thumb="<?php echo esc_url( $templateon_template['thumb'] ); ?>"
>
    <div class="templa-card-thumb">
        <img src="<?php echo esc_url( $templateon_template['thumb'] ); ?>" alt="<?php echo esc_attr( $templateon_template['name'] ); ?>" loading="lazy"/>

        <?php if ( '' !== $templateon_badge ) : ?>
            <span class="templa-badge templa-badge-<?php echo esc_attr( sanitize_html_class( strtolower( $templateon_badge ) ) ); ?>">
                <?php echo esc_html( strtoupper( $templateon_badge ) ); ?>
            </span>
        <?php endif; ?>

        <div class="templa-card-overlay">
            <?php if ( ! empty( $templateon_template['preview'] ) ) : ?>
                <a href="<?php echo esc_url( $templateon_template['preview'] ); ?>" class="templa-btn templa-btn-secondary templa-preview-btn" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e( 'Preview', 'templateon' ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $templateon_has_xml ) : ?>
                <button type="button" class="templa-btn templa-btn-primary templa-import-btn" data-slug="<?php echo esc_attr( $templa_slug ); ?>">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'Import', 'templateon' ); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="templa-card-meta">
        <div class="templa-card-title-wrap">
            <h3 class="templa-card-title"><?php echo esc_html( $templateon_template['name'] ); ?></h3>
            <span class="templa-card-cats"><?php echo esc_html( $templateon_cat_labels ); ?></span>
        </div>
        <button
            type="button"
            class="templa-fav-btn <?php echo $templateon_is_favorite ? 'is-active' : ''; ?>"
            data-slug="<?php echo esc_attr( $templa_slug ); ?>"
            title="<?php esc_attr_e( 'Toggle favourite', 'templateon' ); ?>"
            aria-pressed="<?php echo $templateon_is_favorite ? 'true' : 'false'; ?>">
            <span class="dashicons <?php echo $templateon_is_favorite ? 'dashicons-heart' : 'dashicons-heart'; ?>"></span>
        </button>
    </div>
</article>