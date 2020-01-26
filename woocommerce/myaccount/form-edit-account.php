<?php
/**
 * Edit account form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php wc_print_notices(); ?>

<form action="" method="post">
	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>
    <div class="row">
        <div class="col-md-6">
            <p class="form-group form-row form-row-first">
                <label for="account_first_name"><?php esc_html_e( 'First name', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="text" class="input-text form-control" name="account_first_name" id="account_first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
            </p>
            <p class="form-group form-row form-row-last">
                <label for="account_last_name"><?php esc_html_e( 'Last name', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="text" class="input-text form-control" name="account_last_name" id="account_last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
            </p>
            <p class="form-group form-row form-row-wide">
                <label for="account_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                <input type="text" class="input-text form-control" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" /> <span><em><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ); ?></em></span>
            </p>

            <p class="form-group form-row form-row-wide">
                <label for="account_email"><?php esc_html_e( 'Email address', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="email" class="input-text form-control" name="account_email" id="account_email" value="<?php echo esc_attr( $user->user_email ); ?>" />
            </p>
        </div>
        <div class="col-md-6">
            <fieldset>
                <legend><?php esc_html_e( 'Password Change', 'yozi' ); ?></legend>
                <p class="form-group form-row form-row-thirds">
                    <label for="password_current"><?php esc_html_e( 'Current Password (leave blank to leave unchanged)', 'yozi' ); ?></label>
                    <input type="password" class="input-text form-control" name="password_current" id="password_current" />
                </p>
                <p class="form-group form-row form-row-thirds">
                    <label for="password_1"><?php esc_html_e( 'New Password (leave blank to leave unchanged)', 'yozi' ); ?></label>
                    <input type="password" class="input-text form-control" name="password_1" id="password_1" />
                </p>
                <p class="form-group form-row form-row-thirds">
                    <label for="password_2"><?php esc_html_e( 'Confirm New Password', 'yozi' ); ?></label>
                    <input type="password" class="input-text form-control" name="password_2" id="password_2" />
                </p>
            </fieldset>
        </div>
    </div>
	<!-- <div class="clear"></div> -->
	<?php do_action( 'woocommerce_edit_account_form' ); ?>
	<p class="form-group">
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<input type="submit" class="button" name="save_account_details" value="<?php esc_html_e( 'Save changes', 'yozi' ); ?>" />
		<input type="hidden" name="action" value="save_account_details" />
	</p>
	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>