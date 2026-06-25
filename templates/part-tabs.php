<?php
/**
 * Horizontal mega-menu category filter.
 *
 * Parents are always visible. Hovering a parent that has children reveals
 * a panel of clickable child items. Clicking a child toggles the filter.
 * Childless parents act as a direct toggleable chip in the bar.
 *
 * @var array $categories  Tree: [ [ slug, label, count, children: [...] ], ... ]
 * @var int   $total_count
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<nav class="templa-mega" aria-label="<?php esc_attr_e( 'Template categories', 'templateon' ); ?>">
    <ul class="templa-mega-list" id="templa-mega-list">
        <?php foreach ( $categories as $cat ) :
            $templateon_has_children   = ! empty( $cat['children'] );
            $templateon_children_count = $templateon_has_children ? count( $cat['children'] ) : 0;
            $templateon_children_class = 'templa-mega-children' . ( $templateon_children_count > 6 ? ' is-multi-column' : '' );
        ?>
            <?php if ( $templateon_has_children ) : ?>
                <li class="templa-mega-item has-children" data-parent="<?php echo esc_attr( $cat['slug'] ); ?>">
                    <button type="button" class="templa-mega-trigger" aria-haspopup="true" aria-expanded="false">
                        <span class="templa-mega-trigger-label"><?php echo esc_html( $cat['label'] ); ?></span>
                        <svg class="templa-mega-arrow" viewBox="0 0 11 6" fill="none" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.7812 1.22462L6.02812 5.78989C5.73645 6.07004 5.26355 6.07004 4.97188 5.78989L0.218757 1.22462C-0.0729189 0.944468 -0.0729189 0.490259 0.218757 0.210111C0.510432 -0.0700375 0.983331 -0.0700374 1.27501 0.210111L5.5 4.26813L9.72499 0.210111C10.0167 -0.0700371 10.4896 -0.070037 10.7812 0.210111C11.0729 0.490259 11.0729 0.944468 10.7812 1.22462Z" fill="currentColor"/>
                        </svg>
                        <span class="templa-mega-trigger-active" aria-hidden="true"></span>
                    </button>

                    <div class="templa-mega-panel" role="menu">
                        <div class="templa-mega-panel-head">
                            <span class="templa-mega-panel-title"><?php echo esc_html( $cat['label'] ); ?></span>
                        </div>
                        <ul class="<?php echo esc_attr( $templateon_children_class ); ?>">
                            <?php foreach ( $cat['children'] as $templateon_child ) : ?>
                                <li>
                                    <button
                                        type="button"
                                        class="templa-mega-child templa-cat-item"
                                        role="menuitemcheckbox"
                                        aria-checked="false"
                                        data-cat="<?php echo esc_attr( $templateon_child['slug'] ); ?>"
                                        data-parent="<?php echo esc_attr( $cat['slug'] ); ?>"
                                    >
                                        <span class="templa-cat-check" aria-hidden="true">
                                            <span class="dashicons dashicons-yes"></span>
                                        </span>
                                        <span class="templa-cat-name"><?php echo esc_html( $templateon_child['label'] ); ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <button
                            type="button"
                            class="templa-mega-deselect-all"
                            data-parent="<?php echo esc_attr( $cat['slug'] ); ?>"
                            hidden
                        >
                            <span><?php esc_html_e( 'Uncheck all', 'templateon' ); ?></span>
                        </button>
                    </div>
                </li>
            <?php else : ?>
                <li class="templa-mega-item is-leaf">
                    <button
                        type="button"
                        class="templa-mega-trigger templa-mega-leaf templa-cat-item"
                        data-cat="<?php echo esc_attr( $cat['slug'] ); ?>"
                    >
                        <span class="templa-mega-trigger-label"><?php echo esc_html( $cat['label'] ); ?></span>
                    </button>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <button type="button" class="templa-mega-reset" id="templa-cat-reset" hidden>
        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
        <span class="templa-mega-reset-label"><?php esc_html_e( 'Reset', 'templateon' ); ?></span>
        <span class="templa-mega-reset-count" id="templa-mega-reset-count">0</span>
    </button>
</nav>
