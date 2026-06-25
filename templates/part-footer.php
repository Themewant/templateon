<?php
/**
 * Admin page footer.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


$templateon_cta = apply_filters( 'templa_footer_cta', array(
    'text'         => sprintf(
        /* translators: %s: "Premium Starter Templates" highlighted phrase */
        __( 'Get unlimited access to all %s and more!', 'templateon' ),
        '<strong>' . esc_html__( 'Premium Starter Templates', 'templateon' ) . '</strong>'
    ),
    'button_label' => __( 'Get Premium Templates', 'templateon' ),
    'button_url'   => apply_filters( 'templa_premium_url', 'https://wpeasyelements.com/demotemplate/templateon/' ),
    'button_class' => 'templa-btn-primary',
    'new_tab'      => true,
) );

$templateon_has_cta = is_array( $templateon_cta )
    && ( ! empty( $templateon_cta['text'] ) || ! empty( $templateon_cta['button_label'] ) );
?>
<?php if ( $templateon_has_cta ) : ?>
    <div class="templa-footer-cta-bar">
        <div class="templa-footer-cta">
            <?php if ( ! empty( $templateon_cta['text'] ) ) : ?>
                <span class="templa-footer-cta-text">
                    <?php echo wp_kses( $templateon_cta['text'], templa_allowed_html() ); ?>
                </span>
            <?php endif; ?>

            <?php if ( ! empty( $templateon_cta['button_label'] ) && ! empty( $templateon_cta['button_url'] ) ) : ?>
                <a
                    class="templa-btn <?php echo esc_attr( ! empty( $templateon_cta['button_class'] ) ? $templateon_cta['button_class'] : 'templa-btn-primary' ); ?> templa-footer-cta-btn"
                    href="<?php echo esc_url( $templateon_cta['button_url'] ); ?>"
                    <?php echo empty( $templateon_cta['new_tab'] ) ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>
                >
                    <?php echo esc_html( $templateon_cta['button_label'] ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<footer class="templa-footer">
    <div class="templa-footer-legal">
        <?php
        printf(
            /* translators: %1$s: plugin version, %2$s: brand name */
            esc_html__( 'Templateon v%1$s — by %2$s', 'templateon' ),
            esc_html( TEMPLA_VERSION ),
            '<a href="https://themewant.com" target="_blank" rel="noopener">Themewant</a>'
        );
        ?>
    </div>
</footer>
