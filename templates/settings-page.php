<?php
/**
 * Template for admin settings page
 *
 * @since 1.1.1
 */


namespace Woosa\Bol;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( PREFIX . '_sections_' . $current_tab ) || has_action( PREFIX . '_settings_' . $current_tab ) || has_action( PREFIX . '_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'edit.php?post_type=bol_invoice&page=bol-settings' ) );
	exit;
}
?>
<div class="wrap woocommerce">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php

			foreach ( $tabs as $slug => $label ) {
				echo '<a href="' . esc_html( admin_url( 'edit.php?post_type=bol_invoice&page=bol-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
			}

			do_action( PREFIX . '_settings_tabs' );

			?>
		</nav>
		<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
		<?php
			do_action( PREFIX . '_sections_' . $current_tab );

			\WC_Admin_Settings::show_messages();

			do_action( PREFIX . '_settings_' . $current_tab );
		?>
		<p class="submit">
			<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
				<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
			<?php endif; ?>
			<?php wp_nonce_field( 'woocommerce-settings' ); ?>
		</p>
	</form>
</div>
