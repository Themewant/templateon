<?php
/**
 * Templateon — admin page wrapper.
 *
 * @var array $templates
 * @var array $categories
 * @var array $favorites
 * @var int   $total_count
 * @var int   $template_count
 * @var int   $free_count
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="templa-app" id="templa-app">

    <?php include TEMPLA_TEMPLATES . 'part-header.php'; ?>

    <div class="templa-body">
        <div class="templa-intro">
            <span class="templa-intro-eyebrow"><?php esc_html_e( 'Template Library', 'templateon' ); ?></span>
            <h1 class="templa-intro-title"><?php esc_html_e( 'Templateon', 'templateon' ); ?></h1>
            <p class="templa-intro-desc"><?php esc_html_e( 'Discover beautiful, ready-to-use templates for your next project — import in one click and start customising in seconds.', 'templateon' ); ?></p>
        </div>

        <div class="templa-search-bar">
            <div class="templa-search">
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                <input
                    type="search"
                    id="templa-search-input"
                    placeholder="<?php esc_attr_e( 'What type of website are you building?', 'templateon' ); ?>"
                    autocomplete="off"
                />
            </div>
        </div>

        <?php include TEMPLA_TEMPLATES . 'part-tabs.php'; ?>

        <div class="templa-grid-wrap">
            <div class="templa-grid" id="templa-grid">
                <?php foreach ( $templates as $templateon_template ) : ?>
                    <?php include TEMPLA_TEMPLATES . 'part-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <div class="templa-infinite-sentinel" id="templa-infinite-sentinel" aria-hidden="true"></div>
            <div class="templa-infinite-loader" id="templa-infinite-loader" hidden>
                <span class="templa-spinner" aria-hidden="true"></span>
                <span><?php esc_html_e( 'Loading more…', 'templateon' ); ?></span>
            </div>

            <div class="templa-empty" id="templa-empty" hidden>
                <span class="dashicons dashicons-search"></span>
                <h3><?php esc_html_e( 'No templates found', 'templateon' ); ?></h3>
                <p><?php esc_html_e( 'Try a different search term, category or filter.', 'templateon' ); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
// Footer lives OUTSIDE .templa-app so the sticky CTA bar is not clipped by the
// card's `overflow: clip` (which would break `position: sticky`).
include TEMPLA_TEMPLATES . 'part-footer.php';
?>

<?php include TEMPLA_TEMPLATES . 'part-import-modal.php'; ?>
