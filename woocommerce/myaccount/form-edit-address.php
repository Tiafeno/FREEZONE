<?php
/**
 * Edit address form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? __( 'Billing address', 'woocommerce' ) : __( 'Shipping address', 'woocommerce' );
$customer_id = get_current_user_id();
$role = \classes\fzClient::initializeClient($customer_id)->get_role();
do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

<style type="text/css">
    .woocommerce-address-fields input, .woocommerce-address-fields select {
        border: 2px solid #374387 !important;
    }
</style>
	<form method="post">
		<h3><?php echo apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ); ?></h3><?php // @codingStandardsIgnoreLine ?>
		<div class="woocommerce-address-fields">
			<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="woocommerce-address-fields__field-wrapper">
                        <?php
                        foreach ( $address as $key => $field ) {
                            if (in_array($key, [ 'billing_address_1', 'billing_country'])) continue;
                            if ($key === "billing_address_2") $field['placeholder'] = "Votre adresse";
                            woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <?php if ($role === "fz-company"):
                            $company = \classes\fzClient::initializeClient($customer_id)->get_client();
                            $services = new \Services\fzServices();
                            $sector_activity = $services->get_sector_activity(); // Array of sector activity
                        ?>
                    <div id="section-company" >
                        <div class="form-group form-row form-row-wide">
                            <label for="reg_sector_activity">Secteur d'activité <span class="required">*</span></label>
                            <select class="form-control radius-0" name="sector_activity" style="height: 46px;"
                                    value="<?= $company->sector_activity ?>" required>
                                <option value="0">Aucun</option>
                                <?php foreach ($sector_activity as $activity): ?>
                                    <option value="<?= $activity['id'] ?>" <?= $activity['id'] == $company->sector_activity ? "selected" : '' ?>>
                                        <?= $activity['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="form-group form-row form-row-wide">
                                    <label for="reg_stat">STAT  <span class="required">*</span></label>
                                    <input type="text" placeholder="" required class="input-text form-control radius-0" name="stat"
                                           value="<?= $company->stat ?>" />
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <p class="form-group form-row form-row-wide">
                                    <label for="reg_nif">NIF  <span class="required">*</span></label>
                                    <input type="text" placeholder="" required class="input-text form-control radius-0" name="nif"
                                           value="<?= $company->nif ?>" />
                                </p>
                            </div>
                        </div>
                        <p class="form-group form-row form-row-wide">
                            <label for="reg_rc">RC  <span class="required">*</span></label>
                            <input type="text" placeholder="" required class="input-text form-control radius-0" name="rc"
                                   value="<?= $company->rc ?>" />
                        </p>
                        <p class="form-group form-row form-row-wide">
                            <label for="reg_cif">CIF  <span class="required">*</span></label>
                            <input type="text" placeholder="" required class="input-text form-control radius-0" name="cif"
                                   value="<?= $company->cif ?>" />
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === "fz-particular"):
                        $particular = \classes\fzClient::initializeClient($customer_id)->get_client();
                        ?>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="form-group form-row form-row-wide">
                                    <label for="reg_stat">CIN  <span class="required">*</span></label>
                                    <input type="number" placeholder="" min="12" required class="input-text form-control radius-0" name="stat"
                                           value="<?= $particular->cin ?>" />
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <p class="form-group form-row form-row-wide">
                                    <label for="reg_nif">Fait le  <span class="required">*</span></label>
                                    <input type="date" placeholder="" required class="input-text form-control radius-0" name="nif"
                                           value="<?= $particular->date_cin ?>" />
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


			<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

			<p>
				<button type="submit" class="button" name="save_address" value="<?php esc_attr_e( 'Save address', 'woocommerce' ); ?>"><?php esc_html_e( 'Save address', 'woocommerce' ); ?></button>
				<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
				<input type="hidden" name="action" value="edit_address" />
			</p>
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
