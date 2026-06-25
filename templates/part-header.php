<?php
/**
 * Header: logo left, search + favourites + sync + filter dropdown right.
 *
 * @var int $total_count
 * @var int $template_count
 * @var int $free_count
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<header class="templa-header">
    <div class="templa-header-left">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . TEMPLA_SLUG ) ); ?>" class="templa-logo" aria-label="<?php esc_attr_e( 'Templateon', 'templateon' ); ?>">
            <span class="templa-logo-mark">
                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                    <rect x="2" y="2" width="12" height="12" rx="3" fill="#7455ff"/>
                    <rect x="18" y="2" width="12" height="12" rx="3" fill="#9d84ff"/>
                    <rect x="2" y="18" width="12" height="12" rx="3" fill="#9d84ff"/>
                    <rect x="18" y="18" width="12" height="12" rx="3" fill="#7455ff"/>
                </svg>
            </span>
            <span class="templa-logo-text">
                <strong>Templateon</strong>
                <span><?php esc_html_e( 'Templates', 'templateon' ); ?></span>
            </span>
        </a>
    </div>

    <div class="templa-header-right">
        <a href="<?php echo esc_url( admin_url() ); ?>" class="templa-exit-btn" title="<?php esc_attr_e( 'Exit to Dashboard', 'templateon' ); ?>">
            <span class="dashicons dashicons-exit" aria-hidden="true"></span>
            <?php esc_html_e( 'Dashboard', 'templateon' ); ?>
        </a>

        <button type="button" class="templa-icon-btn" id="templa-favorites-toggle" title="<?php esc_attr_e( 'Show favourites', 'templateon' ); ?>" aria-pressed="false">
            <span class="dashicons dashicons-heart" aria-hidden="true"></span>
            <span class="templa-sr"><?php esc_html_e( 'Favourites', 'templateon' ); ?></span>
        </button>

        <button type="button" class="templa-icon-btn" id="templa-sync-btn" title="<?php esc_attr_e( 'Sync library', 'templateon' ); ?>">
            <span class="dashicons dashicons-update" aria-hidden="true"></span>
            <span class="templa-sr"><?php esc_html_e( 'Sync library', 'templateon' ); ?></span>
        </button>

        <div class="templa-select">
            <select id="templa-type-filter" aria-label="<?php esc_attr_e( 'Filter by type', 'templateon' ); ?>">
                <option value="all"><?php
                    /* translators: %d: total number of templates. */
                    printf( esc_html__( 'All (%d)', 'templateon' ), (int) $total_count );
                ?></option>
                <option value="free"><?php
                    /* translators: %d: number of free templates. */
                    printf( esc_html__( 'Free (%d)', 'templateon' ), (int) $free_count );
                ?></option>
                <?php
                do_action( 'templa_type_filter_options', $template_count );
                ?>
            </select>
            <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
        </div>
    </div>
</header>

<div class="templa-toast" id="templa-toast" hidden></div>
