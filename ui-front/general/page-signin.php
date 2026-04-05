<?php

global $bp, $user_ID, $user_identity, $user_login, $user_email, $userdata, $blog_id;
get_currentuserinfo();

$register = (empty($_GET['register'])) ? '' : $_GET['register'];
$reset = (empty($_GET['reset'])) ? '' : $_GET['reset'];
$redirect = (empty($_GET['redirect_to'])) ? home_url() : $_GET['redirect_to'];

$options = $this->get_options('general');

$signin_url = ! empty($options['signin_url']) ? $options['signin_url'] : home_url();

$options = $this->get_options('payments');

if(is_multisite() ){
	$registration = get_site_option('registration');
	$can_register = ($registration == 'user' || $registration == 'all' );
	//define transient to identify the blog id we're in
	if($blog_id > 1) set_site_transient('register_blog_id_'.$_SERVER['REMOTE_ADDR'], $blog_id, 60 * 60 );
} else {
	$can_register = get_option('users_can_register');
}

?>

<div id="login-register-password">

	<?php if (! $user_ID): ?>

	<ul class="cf_tabs">
		<li class="cf_active"><a href="#tab1_login"><?php _e('Anmelden', $this->text_domain); ?></a></li>
		<?php if($can_register): ?>

		<?php if(isset($bp) ): ?>
		<li><a href="<?php echo esc_url( site_url('wp-login.php?action=register', 'login_post') ); ?>"><?php _e('Registrieren', $this->text_domain); ?></a></li>
		<?php else: ?>
		<li><a href="#tab2_login"><?php _e('Neuer Account', $this->text_domain); ?></a></li>
		<?php endif; ?>

		<?php endif; ?>
		<li><a href="#tab3_login"><?php _e('Vergessen?', $this->text_domain); ?></a></li>
	</ul>
	<div class="cf_tab_container">

		<div id="tab1_login" class="cf_tab_content">
			<?php if ($register == true): ?>

			<h3><?php _e('Erfolg!', $this->text_domain); ?></h3>
			<p><?php _e('Überprüfe Deine E-Mails auf das Passwort und kehre dann zurück, um Dich anzumelden.', $this->text_domain); ?></p>

			<?php elseif($reset == true): ?>

			<h3><?php _e('Erfolg!', $this->text_domain); ?></h3>
			<p><?php _e('Überprüfe Deine E-Mails, um Dein Passwort zurückzusetzen.', $this->text_domain); ?></p>

			<?php else: ?>

			<h3><?php _e('Ein Konto haben?', $this->text_domain); ?></h3>
			<p><?php _e('Du musst Dich anmelden, um den Inhalt dieser Seite anzuzeigen.', $this->text_domain); ?></p>
			<p><?php _e('Anmelden oder Registrieren! Es ist schnell und einfach.', $this->text_domain); ?></p>

			<?php endif; ?>

			<form method="post" action="<?php echo wp_login_url(); ?>" class="wp-user-form">
				<div class="username">
					<label for="user_login"><?php _e('Benutzername', $this->text_domain); ?>: </label>
					<input type="text" name="log" value="<?php echo esc_attr(stripslashes($user_login)); ?>" size="20" id="user_login" tabindex="11" />
				</div>
				<div class="password">
					<label for="user_pass"><?php _e('Passwort', $this->text_domain); ?>: </label>
					<input type="password" name="pwd" value="" size="20" id="user_pass" tabindex="12" />
				</div>
				<div class="login_fields">
					<div class="rememberme">
						<label for="rememberme">
							<input type="checkbox" name="rememberme" value="forever" checked="checked" id="rememberme" tabindex="13" /> <?php _e('Angemeldet bleiben', $this->text_domain); ?>
						</label>
					</div>
					<?php do_action('login_form'); ?>
					<input type="submit" name="user-submit" value="<?php _e('Anmelden', $this->text_domain); ?>" tabindex="14" class="user-submit" />
					<input type="hidden" name="redirect_to" value="<?php echo $redirect; ?>" />
					<input type="hidden" name="user-cookie" value="1" />
				</div>
			</form>
		</div>

		<div id="tab2_login" class="cf_tab_content" style="display:none;">
			<h3><?php _e('Registriere Dich für diese Seite!', $this->text_domain); ?></h3>
			<p><?php _e('Melde Dich jetzt für die guten Sachen an.', $this->text_domain); ?></p>

			<?php if(is_multisite()): ?>
			<form method="post" id="register_frm" action="<?php echo network_site_url('wp-signup.php', 'login_post') ?>" class="wp-user-form">
				<input type="hidden" name="stage" value="validate-user-signup" />
				<?php do_action( 'signup_hidden_fields' ); ?>
				<input type="hidden" name="signup_for" value="user" />
				<div class="username">
					<label for="user_name"><?php _e('Benutzername', $this->text_domain); ?>: </label>
					<input type="text" name="user_name" value="<?php echo esc_attr(stripslashes($user_login)); ?>" size="20" id="user_name" tabindex="101" />
				</div>

				<?php else:	?>

				<form method="post" id="register_frm" action="<?php echo esc_url( site_url('wp-login.php?action=register', 'login_post') ); ?>" class="wp-user-form">
					<div class="username">
						<label for="user_login"><?php _e('Benutzername', $this->text_domain); ?>: </label>
						<input  class="required" type="text" name="user_login" value="<?php echo esc_attr(stripslashes($user_login)); ?>" size="20" id="user_login" tabindex="101" />
					</div>

					<?php endif; ?>

					<div class="password">
						<label for="user_email"><?php _e('Deine Email', $this->text_domain); ?>: </label>
						<input type="text" name="user_email" value="<?php echo esc_attr(stripslashes($user_email)); ?>" size="25" id="user_email" tabindex="102" />
					</div>

					<?php if(! empty($options['tos_txt']) ): ?>
					<div>
						<br />
						<label><strong><?php _e('Nutzungsbedingungen', $this->text_domain)?></strong></label>
						<div class="terms"><?php echo wp_kses_post( wpautop( $options['tos_txt'] ) ); ?></div>
						<label><input type="checkbox" id="tos_agree" value="1" class="required"  tabindex="103" /> <?php _e('Ich stimme den Nutzungsbedingungen zu', $this->text_domain); ?></label>
					</div>
					<?php endif; ?>

					<div class="login_fields">
						<?php do_action('register_form'); ?>
						<input type="submit" name="user-submit" value="<?php _e('Sign up!', $this->text_domain); ?>" class="user-submit" tabindex="104" />
						<?php if($register == true): ?>
						<p><?php _e('Überprüfe Deine E-Mails auf das Passwort!', $this->text_domain); ?></p>
						````````````<?php endif; ?>
						<input type="hidden" name="redirect_to" value="<?php echo $redirect; ?>?register=true" />
						<input type="hidden" name="user-cookie" value="1" />
					</div>
				</form>
			</div>

			<div id="tab3_login" class="cf_tab_content" style="display:none;">
				<h3><?php _e('Passwort vergessen?', $this->text_domain); ?></h3>
				<p><?php _e('Gib Deinen Benutzernamen oder Deine E-Mail-Adresse ein, um Dein Passwort zurückzusetzen.', $this->text_domain); ?></p>
				<form method="post" action="<?php echo wp_lostpassword_url(); ?>" class="wp-user-form">
					<div class="username">
						<label for="user_login" class="hide"><?php _e('Benutzername oder E-Mail-Adresse', $this->text_domain); ?>: </label>
						<input type="text" name="user_login" value="" size="20" id="user_login" tabindex="1001" />
					</div>
					<div class="login_fields">
						<?php do_action('login_form', 'resetpass'); ?>
						<input type="submit" name="user-submit" value="<?php _e('Setze mein Passwort zurück', $this->text_domain); ?>" class="user-submit" tabindex="1002" />
						<?php if($reset == true): ?>
						<p><?php _e('Eine Nachricht wird an Deine E-Mail-Adresse gesendet.', $this->text_domain); ?></p>
						<?php endif; ?>
						<input type="hidden" name="redirect_to" value="<?php echo $redirect; ?>?reset=true" />
						<input type="hidden" name="user-cookie" value="1" />
					</div>
				</form>
			</div>
		</div>

		<?php else: // is logged in ?>

		<div class="sidebox">
			<h3><?php echo sprintf(__('Willkommen, %s', $this->text_domain), $user_identity); ?></h3>
			<div class="usericon">
				<?php echo get_avatar($userdata->ID, 60); ?>
			</div>
			<div class="userinfo">
				<p><?php echo sprintf(__('Du bist als <strong>%s</strong> angemeldet',$this->text_domain),$user_identity); ?></p>
				<p>
					<a href="<?php echo wp_logout_url('index.php'); ?>"><?php _e('Abmelden', $this->text_domain); ?></a> |
					<?php if (current_user_can('manage_options')) {
					echo '<a href="' . admin_url() . '">' . __('Admin', $this->text_domain) . '</a>'; } else {
					echo '<a href="' . admin_url() . 'profile.php">' . __('Profil', $this->text_domain) . '</a>'; } ?>
				</p>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<script type="text/javascript">
		jQuery(function($) {
			$(document).ready(function() {
				$(".cf_tab_content").hide();
				$("ul.cf_tabs li:first").addClass("cf_active").show();
				$(".cf_tab_content:first").show();
				$("ul.cf_tabs").on("click", "li", function() {
					$("ul.cf_tabs li").removeClass("cf_active");
					$(this).addClass("cf_active");
					$(".cf_tab_content").hide();
					var activeTab = $(this).find("a").attr("href");
					$(activeTab).show();
					return false;
				});
			});
			<?php if(! empty($options['tos_txt']) ): ?>
			$("#register_frm").on("submit", function() {
				if (!$("#tos_agree").prop("checked")) {
					alert("<?php echo __('Bitte akzeptiere die Geschäftsbedingungen', $this->text_domain); ?>");
					return false;
				}
			});
			<?php endif; ?>
		});
</script>
