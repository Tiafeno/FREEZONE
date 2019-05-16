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
?>

<?php wc_print_notices(); ?>

<?php do_action( 'woocommerce_before_customer_login_form' ); ?>

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
					<input type="text" class="input-text form-control" name="username" id="reg_username" value="<?php if ( ! empty( $_POST['username'] ) ) echo esc_attr( $_POST['username'] ); ?>" />
				</p>

			<?php endif; ?>
			<p class="form-group form-row form-row-wide">
				<label for="reg_lastname"><?php esc_html_e( 'Nom', 'yozi' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text form-control" placeholder="Votre nom" name="lastname" id="reg_lastname" required
                       value="<?php if ( ! empty( $_POST['lastname'] ) ) echo esc_attr( $_POST['lastname'] ); ?>" />
			</p>

            <p class="form-group form-row form-row-wide">
                <label for="reg_firstname"><?php esc_html_e( 'Prénom', 'yozi' ); ?> </label>
                <input type="text" class="input-text form-control" placeholder="Votre prénom" name="firstname" id="reg_firstname"
                       value="<?php if ( ! empty( $_POST['firstname'] ) ) echo esc_attr( $_POST['firstname'] ); ?>" />
            </p>

            <p class="form-group form-row form-row-wide">
                <label for="reg_phone"><?php esc_html_e( 'Numéro de téléphone', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="text" placeholder="Votre numéro de téléphone" class="input-text form-control" name="phone" id="reg_phone" required
                       value="<?php if ( ! empty( $_POST['phone'] ) ) echo esc_attr( $_POST['phone'] ); ?>" />
            </p>

            <p class="form-group form-row form-row-wide">
                <label for="reg_address"><?php esc_html_e( 'Adresse', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="text" placeholder="Votre adresse" class="input-text form-control" name="address" id="reg_address" required
                       value="<?php if ( ! empty( $_POST['address'] ) ) echo esc_attr( $_POST['address'] ); ?>" />
            </p>

			<!--<p class="form-group form-row form-row-wide">
				<label for="reg_email">Type de compte <span class="required">*</span></label>
                <select class="form-control" name="role" id="reg_role" value="<?php /*if ( ! empty( $_POST['role'] ) ) echo esc_attr( $_POST['role'] ); */?>" required>
                    <option value="">Selectionner un type</option>
                    <option value="particular">Particulier</option>
                    <option value="supplier">Société ou Fournisseur</option>
                </select>
			</p>

            <p class="form-group form-row form-row-wide" id="form-company-name" style="display: none;">
                <label for="reg_phone">Nom de l'entreprise <span class="required">*</span></label>
                <input type="text" placeholder="Le nom de votre entreprise" class="input-text form-control" name="company_name" id="reg_company_name"
                       value="<?php /*if ( ! empty( $_POST['company_name'] ) ) echo esc_attr( $_POST['company_name'] ); */?>" />
            </p>-->

            <p class="form-group form-row form-row-wide">
                <label for="reg_email"><?php esc_html_e( 'Email address', 'yozi' ); ?> <span class="required">*</span></label>
                <input type="email" autocomplete="off" class="input-text form-control" name="email" id="reg_email" value="<?php if ( ! empty( $_POST['email'] ) ) echo esc_attr( $_POST['email'] ); ?>" />
            </p>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="form-group form-row form-row-wide">
					<label for="reg_password"><?php esc_html_e( 'Password', 'yozi' ); ?> <span class="required">*</span></label>
					<input type="password" autocomplete="off" class="input-text form-control" name="password" id="reg_password" />
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