<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.4.0
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theme = wp_get_theme('freezone');
wp_enqueue_script('register-fz', get_stylesheet_directory_uri() . '/assets/js/register.js', ['jquery', 'underscore'], $theme->get('Version'), true);
$args = array('#customer_login', '#customer_register');
$action = isset($_COOKIE['yozi_login_register']) && in_array($_COOKIE['yozi_login_register'], $args) ? $_COOKIE['yozi_login_register'] : '#customer_login';

$services = new fzServices();
$sector_activity = $services->get_sector_activity(); // Array of sector activity

?>

<?php wc_print_notices(); ?>

<?php do_action( 'woocommerce_before_customer_login_form' ); ?>
<style type="text/css">
    #customer_login input {
        border: 2px solid #374387;
    }
    #customer_register input, #customer_register select {
        border: 2px solid #374387;
    }
</style>
<div class="user">
	<div id="customer_login" class="register_login_wrapper <?php echo trim($action == '#customer_login' ? 'active' : ''); ?>">
		<h2 class="title"><?php esc_html_e( 'Login', 'yozi' ); ?></h2>
		<form method="post" class="login" role="form">

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="form-group form-row form-row-wide">
				<label for="username"><?php esc_html_e( 'Username or email address', 'yozi' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text form-control" name="username" id="username" value="<?php if ( ! empty( $_POST['username'] ) ) echo esc_attr( $_POST['username'] ); ?>" />
			</p>
			<p class="form-group form-row form-row-wide">
				<label for="password"><?php esc_html_e( 'Password', 'yozi' ); ?> <span class="required">*</span></label>
				<input class="input-text form-control" type="password" name="password" id="password" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<div class="form-group form-row">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<div class="form-group clearfix">
					<span for="rememberme" class="inline pull-left">
						<input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php esc_html_e( 'Remember me', 'yozi' ); ?>
					</span>
					<span class="lost_password pull-right">
						<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'yozi' ); ?></a>
					</span>
				</div>
				<input type="submit" class="btn btn-theme btn-block" name="login" value="<?php esc_html_e( 'sign in', 'yozi' ); ?>" />
			</div>

			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>

		<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>
			<div class="create text-center">
				<div class="line-border center">
					<span class="center-line">ou</span>
				</div>
				<a class="creat-account register-login-action" href="#customer_register"><?php echo esc_html__('CREATE AN ACCOUNT','yozi'); ?></a>
			</div>
		<?php endif; ?>

	</div>
<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>

	<div id="customer_register" class="content-register register_login_wrapper <?php echo trim($action == '#customer_register' ? 'active' : ''); ?>">
		<h2 class="title"><?php esc_html_e( 'Register', 'yozi' ); ?></h2>
		<form method="post" class="register widget" role="form">
			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
				<p class="form-group form-row form-row-wide">
					<label for="reg_username"><?php esc_html_e( 'Username', 'yozi' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text form-control" name="username" id="reg_username" 
                    value="<?php if ( ! empty( $_POST['username'] ) ) echo esc_attr( $_POST['username'] ); ?>" />
				</p>
			<?php endif; ?>
            <div class="row">
                <div class="col-md-6">
                    <p class="form-group form-row form-row-wide">
                        <label for="reg_lastname"><?php esc_html_e( 'Nom', 'yozi' ); ?> <span class="required">*</span></label>
                        <input type="text" class="input-text form-control radius-0" placeholder="" name="lastname" id="reg_lastname" required
                            value="<?php if ( ! empty( $_POST['lastname'] ) ) echo esc_attr( $_POST['lastname'] ); ?>" />
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="form-group form-row form-row-wide">
                        <label for="reg_firstname"><?php esc_html_e( 'Prénom', 'yozi' ); ?> </label>
                        <input type="text" class="input-text form-control radius-0" placeholder="" name="firstname" id="reg_firstname"
                            value="<?php if ( ! empty( $_POST['firstname'] ) ) echo esc_attr( $_POST['firstname'] ); ?>" />
                    </p>
                </div>
            </div>

            <div class="form-group form-row form-row-wide">
                <label for="reg_phone"><?php esc_html_e( 'Numéro de téléphone', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="text" placeholder="Exemple: 03x xx xxx xx" class="input-text form-control radius-0" name="phone" id="reg_phone" required
                       value="<?php if ( ! empty( $_POST['phone'] ) ) echo esc_attr( $_POST['phone'] ); ?>" />
                       <div style="font-size: 12px; color: #989498;">Veuillez ajouter un numero joigniable</div>
            </div>

            <p class="form-group form-row form-row-wide">
                <label for="reg_address"><?php esc_html_e( 'Adresse', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="text" placeholder="" class="input-text form-control radius-0" name="address" id="reg_address" required
                       value="<?php if ( ! empty( $_POST['address'] ) ) echo esc_attr( $_POST['address'] ); ?>" />
            </p>

            <div class="row">
                <div class="col-sm-6">
                    <p class="form-group form-row form-row-wide">
                        <label for="reg_city">Ville  <span class="required">*</span></label>
                        <input type="text" placeholder="" class="input-text form-control radius-0" name="city" id="reg_city"
                               value="<?php if ( ! empty( $_POST['city'] ) ) echo esc_attr( $_POST['city'] ); ?>" />
                    </p>
                </div>
                <div class="col-sm-6">
                    <p class="form-group form-row form-row-wide">
                        <label for="reg_postal_code">Code postal  <span class="required">*</span></label>
                        <input type="text" placeholder="" class="input-text form-control radius-0" name="postal_code" id="reg_postal_code"
                               value="<?php if ( ! empty( $_POST['postal_code'] ) ) echo esc_attr( $_POST['postal_code'] ); ?>" />
                    </p>
                </div>
            </div>

			<p class="form-group form-row form-row-wide">
				<label for="reg_role">Choisissez votre statut <span class="required">*</span></label>
                <select class="form-control" name="role" id="reg_role" style="height: 46px;"
                        value="<?php if ( ! empty( $_POST['role'] ) ) echo esc_attr( $_POST['role'] ); ?>" required>
                    <option value="">Selectionner un statut</option>
                    <option value="particular">Particulier</option>
                    <option value="company">Société ou Entreprise</option>
                </select>
			</p>
            
            <!-- Pour les utilisateurs de type société ou entreprise --->
            <div id="section-company" style="display: none">
                <div class="form-group form-row form-row-wide">
                    <label for="reg_company">Nom de l'entreprise / Société  <span class="required">*</span></label>
                    <input type="text" placeholder="" class="input-text form-control radius-0" name="company_name" id="reg_company" 
                           value="<?php if ( ! empty( $_POST['company_name'] ) ) echo esc_attr( $_POST['company_name'] ); ?>" />
                </div>

                <div class="form-group form-row form-row-wide">
                    <label for="reg_sector_activity">Secteur d'activité <span class="required">*</span></label>
                    <select class="form-control radius-0" name="sector_activity" id="reg_sector_activity" style="height: 46px;"
                            value="<?php if ( ! empty( $_POST['sector_activity'] ) ) echo esc_attr( $_POST['sector_activity'] ); ?>" required>
                        <option value="">Aucun</option>
                        <?php foreach ($sector_activity as $activity): ?>
                            <option value="<?= $activity['id'] ?>"><?= $activity['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <p class="form-group form-row form-row-wide">
                            <label for="reg_stat">STAT  <span class="required">*</span></label>
                            <input type="text" placeholder="" class="input-text form-control radius-0" name="stat" id="reg_stat" 
                                   value="<?php if ( ! empty( $_POST['stat'] ) ) echo esc_attr( $_POST['stat'] ); ?>" />
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p class="form-group form-row form-row-wide">
                            <label for="reg_nif">NIF  <span class="required">*</span></label>
                            <input type="text" placeholder="" class="input-text form-control radius-0" name="nif" id="reg_nif" 
                                   value="<?php if ( ! empty( $_POST['nif'] ) ) echo esc_attr( $_POST['nif'] ); ?>" />
                        </p>
                    </div>
                </div>
                
                <p class="form-group form-row form-row-wide">
                    <label for="reg_rc">RC  <span class="required">*</span></label>
                    <input type="text" placeholder="" class="input-text form-control radius-0" name="rc" id="reg_rc" 
                           value="<?php if ( ! empty( $_POST['rc'] ) ) echo esc_attr( $_POST['rc'] ); ?>" />
                </p>
                <p class="form-group form-row form-row-wide">
                    <label for="reg_cif">CIF  <span class="required">*</span></label>
                    <input type="text" placeholder="" class="input-text form-control radius-0" name="cif" id="reg_cif"
                           value="<?php if ( ! empty( $_POST['cif'] ) ) echo esc_attr( $_POST['cif'] ); ?>" />
                </p>
            </div>
            <!-- Fin pour les champs société ou entreprise -->

            <!-- Pour les utilisateurs de type particulier --->
            <div id="section-particular" style="display: none">
                <div class="row">
                    <div class="col-sm-6">
                        <p class="form-group form-row form-row-wide">
                            <label for="reg_cin">CIN  <span class="required">*</span></label>
                            <input type="number" placeholder="" class="input-text form-control radius-0" name="cin" id="reg_cin" minlength="12" maxlength="12"
                                value="<?php if ( ! empty( $_POST['cin'] ) ) echo esc_attr( $_POST['cin'] ); ?>" />
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p class="form-group form-row form-row-wide">
                            <label for="reg_date_cin">Date de délivrance  <span class="required">*</span></label>
                            <input type="date" placeholder="jj/mm/aaaa" class="input-text form-control radius-0" name="date_cin" id="reg_date_cin"
                                   value="<?php if ( ! empty( $_POST['date_cin'] ) ) echo esc_attr( $_POST['date_cin'] ); ?>" />
                        </p>
                    </div>
                </div>
            </div>
            <!-- Fin pour les champs particuliers -->

            <p class="form-group form-row form-row-wide">
                <label for="reg_email"><?php esc_html_e( 'Email address', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="email" autocomplete="off" class="input-text form-control radius-0" name="email" required="true"
                id="reg_email" value="<?php if ( ! empty( $_POST['email'] ) ) echo esc_attr( $_POST['email'] ); ?>" />
            </p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="form-group form-row form-row-wide">
					<label for="reg_password"><?php esc_html_e( 'Password', 'yozi' ); ?> <span class="required">*</span></label>
					<input type="password" autocomplete="off" class="input-text form-control radius-0" name="password" id="reg_password" required="true" />
				</p>

			<?php endif; ?>


			<?php do_action( 'woocommerce_register_form' ); ?>

			<p class="form-group form-row">
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<input type="submit" class="btn btn-primary btn-block" name="register" value="<?php esc_html_e( 'Register', 'yozi' ); ?>" />
			</p>

			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>

		<div class="create text-center">
			<div class="line-border center">
				<span class="center-line">ou</span>
			</div>
			<a class="login-account register-login-action" href="#customer_login"><?php echo esc_html__('SIGN IN','yozi'); ?></a>
		</div>

	</div>

<?php endif; ?>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>