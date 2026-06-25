<?php
/**
 * Import wizard — 3 steps: requirements → progress → success.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$templateon_required_plugins = [
    [ 'name' => 'Elementor',          'slug' => 'elementor' ],
    [ 'name' => 'Easy Elements',      'slug' => 'easy-elements' ],
    [ 'name' => 'BoldForm',           'slug' => 'boldform-lite' ],
    [ 'name' => 'WordPress Importer', 'slug' => 'wordpress-importer' ],
];

/**
 * Filter the list of required plugins shown in the import wizard.
 *
 * Add-ons (e.g. TemplateOn Pro) can append their own plugins here.
 * Each item must be an array with 'name' and 'slug' keys, where 'slug'
 * matches the plugin's wordpress.org slug / folder name.
 *
 * @param array $templateon_required_plugins List of [ 'name' => ..., 'slug' => ... ] items.
 */
$templateon_required_plugins = (array) apply_filters( 'templateon_required_plugins', $templateon_required_plugins );

$templateon_recommended_theme = [
    'name' => 'Hello Elementor',
    'slug' => 'hello-elementor',
];

/**
 * Filter the recommended theme shown in the import wizard.
 *
 * @param array $templateon_recommended_theme [ 'name' => ..., 'slug' => ... ].
 */
$templateon_recommended_theme = (array) apply_filters( 'templateon_recommended_theme', $templateon_recommended_theme );

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$templateon_all_plugins = get_plugins();

$templateon_current_theme      = wp_get_theme();
$templateon_is_theme_active    = $templateon_current_theme->get_stylesheet() === $templateon_recommended_theme['slug'];
$templateon_theme_obj          = wp_get_theme( $templateon_recommended_theme['slug'] );
$templateon_is_theme_installed = $templateon_theme_obj->exists();
?>
<div class="templa-import-modal" id="templa-import-modal" hidden role="dialog" aria-labelledby="templa-import-title">
    <div class="templa-import-overlay" data-close-import></div>

    <div class="templa-import-dialog">
        <button type="button" class="templa-import-close" data-close-import title="<?php esc_attr_e( 'Close', 'templateon' ); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>

        <div class="templa-import-step is-active" data-step="requirements">
            <h2 id="templa-import-title"><?php esc_html_e( 'Required Features', 'templateon' ); ?></h2>
            <p class="templa-import-sub"><?php esc_html_e( 'We will install & activate everything listed below before importing the template.', 'templateon' ); ?></p>

            <div class="templa-requirement-box">
                <div class="templa-requirement-head">
                    <h3><?php esc_html_e( 'Required Plugins', 'templateon' ); ?></h3>
                    <span><?php echo count( $templateon_required_plugins ); ?> <?php esc_html_e( 'plugins', 'templateon' ); ?></span>
                </div>
                <ul class="templa-requirement-list" id="templa-required-plugins">
                    <?php foreach ( $templateon_required_plugins as $templateon_plugin ) :
                        $templateon_plugin_file = '';
                        $templateon_is_active   = false;
                        foreach ( array_keys( $templateon_all_plugins ) as $templateon_plugin_basename ) {
                            if ( dirname( $templateon_plugin_basename ) === $templateon_plugin['slug'] ) { $templateon_plugin_file = $templateon_plugin_basename; break; }
                        }
                        if ( ! empty( $templateon_plugin_file ) ) {
                            $templateon_is_active = is_plugin_active( $templateon_plugin_file );
                        }
                        $templateon_status = $templateon_is_active ? 'active' : ( ! empty( $templateon_plugin_file ) ? 'inactive' : 'missing' );
                    ?>
                        <li
                            class="templa-plugin-row"
                            data-slug="<?php echo esc_attr( $templateon_plugin['slug'] ); ?>"
                            data-init="<?php echo esc_attr( $templateon_plugin_file ); ?>"
                            data-installed="<?php echo ! empty( $templateon_plugin_file ) ? 'true' : 'false'; ?>"
                            data-active="<?php echo $templateon_is_active ? 'true' : 'false'; ?>"
                        >
                            <span class="templa-dot"></span>
                            <span class="templa-plugin-name"><?php echo esc_html( $templateon_plugin['name'] ); ?></span>
                            <span class="templa-status templa-status--<?php echo esc_attr( $templateon_status ); ?>">
                                <?php echo esc_html( ucfirst( $templateon_status ) ); ?>
                            </span>
                            <?php if ( $templateon_is_active ) : ?>
                                <?php /* already shown in status pill — no duplicate button */ ?>
                            <?php elseif ( ! empty( $templateon_plugin_file ) ) : ?>
                                <button type="button" class="templa-btn-mini templa-plugin-action" data-do="activate">
                                    <span class="dashicons dashicons-controls-play"></span>
                                    <span class="templa-btn-mini-label"><?php esc_html_e( 'Activate', 'templateon' ); ?></span>
                                </button>
                            <?php else : ?>
                                <button type="button" class="templa-btn-mini templa-plugin-action" data-do="install">
                                    <span class="dashicons dashicons-download"></span>
                                    <span class="templa-btn-mini-label"><?php esc_html_e( 'Install', 'templateon' ); ?></span>
                                </button>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="templa-requirement-box">
                <div class="templa-requirement-head">
                    <h3><?php esc_html_e( 'Recommended Theme', 'templateon' ); ?></h3>
                    <span>1 <?php esc_html_e( 'theme', 'templateon' ); ?></span>
                </div>
                <ul class="templa-requirement-list">
                    <?php
                        $templateon_theme_status = $templateon_is_theme_active ? 'active' : ( $templateon_is_theme_installed ? 'inactive' : 'missing' );
                    ?>
                    <li
                        class="templa-theme-row"
                        data-slug="<?php echo esc_attr( $templateon_recommended_theme['slug'] ); ?>"
                        data-installed="<?php echo $templateon_is_theme_installed ? 'true' : 'false'; ?>"
                        data-active="<?php echo $templateon_is_theme_active ? 'true' : 'false'; ?>"
                    >
                        <label>
                            <input
                                type="checkbox"
                                id="templa-theme-check"
                                value="<?php echo esc_attr( $templateon_recommended_theme['slug'] ); ?>"
                                data-installed="<?php echo $templateon_is_theme_installed ? 'true' : 'false'; ?>"
                                <?php checked( $templateon_is_theme_active ); ?>
                            />
                            <?php echo esc_html( $templateon_recommended_theme['name'] ); ?>
                        </label>
                        <span class="templa-status templa-status--<?php echo esc_attr( $templateon_theme_status ); ?>">
                            <?php echo esc_html( ucfirst( $templateon_theme_status ) ); ?>
                        </span>
                        <?php if ( $templateon_is_theme_active ) : ?>
                            <?php /* already shown in status pill — no duplicate button */ ?>
                        <?php elseif ( $templateon_is_theme_installed ) : ?>
                            <button type="button" class="templa-btn-mini templa-theme-action" data-do="activate">
                                <span class="dashicons dashicons-controls-play"></span>
                                <span class="templa-btn-mini-label"><?php esc_html_e( 'Activate', 'templateon' ); ?></span>
                            </button>
                        <?php else : ?>
                            <button type="button" class="templa-btn-mini templa-theme-action" data-do="install">
                                <span class="dashicons dashicons-download"></span>
                                <span class="templa-btn-mini-label"><?php esc_html_e( 'Install', 'templateon' ); ?></span>
                            </button>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>

            <div class="templa-import-actions">
                <button type="button" class="templa-btn templa-btn-ghost" data-close-import>
                    <?php esc_html_e( 'Cancel', 'templateon' ); ?>
                </button>
                <button type="button" class="templa-btn templa-btn-primary" id="templa-start-import">
                    <?php esc_html_e( 'Start Import', 'templateon' ); ?>
                </button>
            </div>
        </div>

        <div class="templa-import-step" data-step="progress">
            <h2><?php esc_html_e( 'Creating your website…', 'templateon' ); ?></h2>
            <p class="templa-import-sub"><?php esc_html_e( 'Please wait, this may take a few minutes. Do not close this window.', 'templateon' ); ?></p>

            <div class="templa-skeleton">
                <div class="templa-skeleton-head">
                    <div class="templa-skeleton-bar templa-skeleton-logo"></div>
                    <div class="templa-skeleton-bar templa-skeleton-avatar"></div>
                </div>
                <div class="templa-skeleton-body">
                    <div class="templa-skeleton-bar templa-skeleton-hero"></div>
                    <div class="templa-skeleton-grid">
                        <div class="templa-skeleton-bar"></div>
                        <div class="templa-skeleton-bar"></div>
                        <div class="templa-skeleton-bar"></div>
                    </div>
                </div>
            </div>

            <div class="templa-progress">
                <div class="templa-progress-fill" id="templa-progress-fill"></div>
            </div>
            <p class="templa-progress-text" id="templa-progress-text">0% — <?php esc_html_e( 'Preparing…', 'templateon' ); ?></p>
        </div>

        <div class="templa-import-step" data-step="done">
            <div class="templa-success-icon">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <h2><?php esc_html_e( 'All done! 🎉', 'templateon' ); ?></h2>
            <p class="templa-import-sub"><?php esc_html_e( 'Your website has been imported and is ready to explore.', 'templateon' ); ?></p>
            <div class="templa-import-actions templa-import-actions--center">
                <a href="<?php echo esc_url( home_url() ); ?>" class="templa-btn templa-btn-primary templa-btn-big" target="_blank" rel="noopener">
                    <?php esc_html_e( 'Visit Website', 'templateon' ); ?>
                </a>
                <button type="button" class="templa-btn templa-btn-ghost" data-close-import>
                    <?php esc_html_e( 'Close', 'templateon' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
