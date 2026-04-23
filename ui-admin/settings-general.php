<?php if (!defined('ABSPATH')) die('No direct access allowed!');

global $wp_roles;
$options = $this->get_options( 'general' );
$default_email = __(
'Hi TO_NAME, you have received a message from

  Name: FROM_NAME
  Email: FROM_EMAIL
  Subject: FROM_SUBJECT
  Message:

  FROM_MESSAGE


  Classifieds link: POST_LINK
', $this->text_domain);

?>

<div class="wrap">

	<?php $this->render_admin( 'navigation', array( 'page' => 'classifieds_settings', 'tab' => 'general' ) ); ?>
	<?php $this->render_admin( 'message' ); ?>

	<h1><?php _e( 'Allgemeine Einstellungen', $this->text_domain ); ?></h1>

	<form action="#" method="post">
		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Mitglieder-Rolle', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th>
							<label for="roles"><?php _e( 'Mitglieder-Rolle zuweisen', $this->text_domain ) ?></label>
						</th>
						<td>
							<select id="member_role" name="member_role" style="width:200px;">
							<?php wp_dropdown_roles(@$options['member_role']); ?>
							</select>
							<br /><span class="description"><?php _e('Waehle die Rolle, die neue Kleinanzeigen-Mitglieder beim Registrieren bekommen.', $this->text_domain); ?></span>
							<br /><span class="description"><?php _e('Wenn mehrere Plugins eine Registrierung haben, nutze am besten dieselbe Rolle.', $this->text_domain); ?></span>
						</td>
					</tr>
					<tr>
						<th>
							<label>Mitglieder-Rollen verwalten</label>
						</th>
						<td>
							<label>Neue Rolle anlegen</label><br />
							<input type="text" id="new_role" name="new_role" size="30"/>
							<input type="submit" class="button" id="add_role" name="add_role" value="<?php _e( 'Rolle anlegen', $this->text_domain ); ?>" />
							<br /><span class="description"><?php _e('Lege eine neue Rolle an. Nur Buchstaben und Zahlen.', $this->text_domain); ?></span>
							<br /><span class="description"><?php _e('Danach musst du der Rolle noch die passenden Rechte geben.', $this->text_domain); ?></span>
							<br /><br />
							<label>Eigene Rollen</label><br />
							<select id="delete_role" name="delete_role"  style="width:200px;">
								<?php
								global $wp_roles;
								$system_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
								$role_names = $wp_roles->role_names;
								foreach ( $role_names as $role => $name ):
								if(! in_array($role, $system_roles) ): //Don't delete system roles.
								?>
								<option value="<?php echo $role; ?>"><?php echo $name; ?></option>
								<?php
								endif;
								endforeach;
								?>
							</select>
							<input type="button" class="button" onclick="jQuery(this).hide(); jQuery('#remove_role').show();" value="<?php _e( 'Rolle entfernen', $this->text_domain ); ?>" />
							<input type="submit" class="button-primary" id="remove_role" name="remove_role" value="<?php _e( 'Entfernen bestaetigen', $this->text_domain ); ?>" style="display: none;" />

						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Status-Optionen', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th>
							<label for="moderation"><?php _e('Verfuegbare Statuswerte', $this->text_domain ) ?></label>
						</th>
						<td>
							<label><input type="checkbox" name="moderation[publish]" value="1" <?php checked( ! empty($options['moderation']['publish']) ) ?> /> <?php _e('Veröffentlicht', $this->text_domain); ?></label>
							<br /><span class="description"><?php _e('Mitglieder duerfen ihre Anzeigen selbst veroeffentlichen.', $this->text_domain); ?></span>
							<br /><label><input type="checkbox" name="moderation[pending]" value="1" <?php checked( ! empty($options['moderation']['pending']) ) ?> /> <?php _e('Wartet auf Freigabe', $this->text_domain); ?></label>
							<br /><span class="description"><?php _e('Anzeige wartet auf Freigabe durch den Admin.', $this->text_domain ); ?></span>
							<br /><label><input type="checkbox" name="moderation[draft]" value="1" <?php checked( ! empty($options['moderation']['draft']) ) ?> /> <?php _e('Entwurf', $this->text_domain); ?></label>
							<br /><span class="description"><?php _e('Mitglieder duerfen Entwuerfe speichern.', $this->text_domain); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>


		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Formularfelder', $this->text_domain ); ?></span></h3>
			<div class="inside">

				<table class="form-table">
					<tr>
						<th><label for="field_image_req"><?php _e( 'Bildfeld:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="field_image_req" value="0" />
							<label>
							<input type="checkbox" id="field_image_req" name="field_image_req" value="1" <?php checked( isset( $options['field_image_req'] ) && 1 == $options['field_image_req'] ); ?> />
							<span class="description"><?php _e( 'nicht erforderlich', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="media_manager"><?php _e( 'Medien-Manager:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="media_manager" value="0" />
							<label>
							<input type="checkbox" id="media_manager" name="media_manager" value="1" <?php checked(isset( $options['media_manager'] ) && 1 == $options['media_manager'] ); ?> />
							<span class="description"><?php _e( 'Vollständigen Medien-Manager für Bild-Uploads aktivieren (derzeit nicht aktiv)', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="field_image_def"><?php _e( 'Standard-Bild (URL):', $this->text_domain ); ?></label></th>
						<td>
							<input type="text" id="field_image_def" name="field_image_def" size="70" value="<?php echo ( isset( $options['field_image_def'] ) && '' != $options['field_image_def'] ) ? $options['field_image_def'] : ''; ?>" />
							<br />
							<span class="description"><?php _e( 'Dieses Bild wird für alle Anzeigen ohne Bild angezeigt.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Anzeigeoptionen', $this->text_domain ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th>
							<label for="count_cat"><?php _e( 'Anzahl der Kategorien:', $this->text_domain ) ?></label>
						</th>
						<td>
							<input type="text" name="count_cat" id="count_cat" value="<?php echo (empty( $options['count_cat'] ) ) ? '10' : $options['count_cat']; ?>" size="2" />
							<span class="description"><?php _e( 'Anzahl der Kategorien, die in der Kategorieliste angezeigt werden.', $this->text_domain ) ?></span>
						</td>
					</tr>

					<tr>
						<th><label for="display_parent_count"><?php esc_html_e( 'Anzahl in Eltern-Kategorien anzeigen:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="display_parent_count" value="0" />
							<label>
							<input type="checkbox" id="display_parent_count" name="display_parent_count" value="1" <?php checked( isset( $options['display_parent_count'] ) && 1 == $options['display_parent_count'] ); ?> />
							<span class="description"><?php esc_html_e( 'Zeigt die Anzahl der Anzeigen bei den Eltern-Kategorien an.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>

					<tr>
						<th>
							<label for="count_sub_cat"><?php _e( 'Anzahl der Unterkategorien:', $this->text_domain ) ?></label>
						</th>
						<td>
							<input type="text" name="count_sub_cat" id="count_sub_cat" value="<?php echo ( empty( $options['count_sub_cat'] ) ) ? '5' : $options['count_sub_cat']; ?>" size="2" />
							<span class="description"><?php _e( 'Anzahl der Unterkategorien, die je Eltern-Kategorie in der Kategorieliste angezeigt werden.', $this->text_domain ) ?></span>
						</td>
					</tr>

					<tr>
						<th><label for="display_sub_count"><?php esc_html_e( 'Anzahl bei Unterkategorien anzeigen:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="display_sub_count" value="0" />
							<label>
								<input type="checkbox" id="display_sub_count" name="display_sub_count" value="1" <?php checked( !isset( $options['display_sub_count'] ) || 1 == $options['display_sub_count'] ); ?> />
								<span class="description"><?php esc_html_e( 'Zeigt die Anzahl der Anzeigen bei den Unterkategorien an.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>

					<tr>
						<th>
							<?php _e( 'Leere Unterkategorie:', $this->text_domain ) ?>
						</th>
						<td>
							<label>
							<input type="checkbox" name="hide_empty_sub_cat" id="hide_empty_sub_cat" value="1" <?php checked( empty( $options['hide_empty_sub_cat'] ) ? false : ! empty($options['hide_empty_sub_cat']) ); ?> />
							<span class="description"><?php _e( 'Leere Unterkategorien ausblenden', $this->text_domain ) ?></span>
							</label>
						</td>
					</tr>
					<?php
					/*
					<tr>
					<th>
					<?php _e( 'Display listing:', $this->text_domain ) ?>
					</th>
					<td>
					<input type="checkbox" name="display_listing" id="display_listing" value="1" <?php echo ( isset( $options['display_listing'] ) && '1' == $options['display_listing'] ) ? 'checked' : ''; ?> />
					<label for="display_listing"><?php _e( 'add Listings to align blocks according to height while  sub-categories are lacking', $this->text_domain ) ?></label>
					</td>
					</tr>
					*/
					?>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Seitennavigation', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th><label for="field_image_req"><?php _e( 'Position der Seitennavigation:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="pagination_top" value="0" />
							<label>
							<input type="checkbox" id="pagination_top" name="pagination_top" value="1" <?php echo ( isset( $options['pagination_top'] ) && 1 == $options['pagination_top'] ) ? 'checked' : ''; ?> />
							<span class="description"><?php _e( 'Oben auf der Seite anzeigen.', $this->text_domain ); ?></span>
							</label>
							<br />
							<input type="hidden" name="pagination_bottom" value="0" />
							<label>
							<input type="checkbox" id="pagination_bottom" name="pagination_bottom" value="1" <?php echo ( isset( $options['pagination_bottom'] ) && 1 == $options['pagination_bottom'] ) ? 'checked' : ''; ?> />
							<span class="description"><?php _e( 'Unten auf der Seite anzeigen.', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<!--
					<tr>
					<th><label for="ads_per_page"><?php _e( 'Ads per Page:', $this->text_domain ); ?></label></th>
					<td>
					<input type="text" id="ads_per_page" name="ads_per_page" size="4" value="<?php echo ( isset( $options['ads_per_page'] ) && '' != $options['ads_per_page'] ) ? $options['ads_per_page'] : '10'; ?>" />
					<br />
					<span class="description"><?php _e( 'Number of ads displayed on each page.', $this->text_domain ); ?></span>
					</td>
					</tr>
					-->
					<tr>
						<th><label for="pagination_range"><?php _e( 'Seitenlinks (Anzahl):', $this->text_domain ); ?></label></th>
						<td>
							<input type="text" id="pagination_range" name="pagination_range" size="4" value="<?php echo ( isset( $options['pagination_range'] ) && '' != $options['pagination_range'] ) ? $options['pagination_range'] : '4'; ?>" />
							<span class="description"><?php _e( 'Wie viele Seitenlinks gleichzeitig in der Navigation angezeigt werden.', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e( 'Benachrichtigungen', $this->text_domain ); ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th><label for="disable_contact_form"><?php _e( 'Kontaktformular deaktivieren:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="disable_contact_form" value="0" />
							<label>
							<input type="checkbox" id="disable_contact_form" name="disable_contact_form" value="1" <?php checked( isset( $options['disable_contact_form'] ) && 1 == $options['disable_contact_form'] ); ?> />
							<span class="description"><?php _e( 'Kontaktformular auf Anzeigen-Detailseiten ausblenden', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="cc_admin"><?php _e( 'Admin in Kopie:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="cc_admin" value="0" />
							<label>
							<input type="checkbox" id="cc_admin" name="cc_admin" value="1" <?php checked( isset( $options['cc_admin'] ) && 1 == $options['cc_admin'] ); ?> />
							<span class="description"><?php _e( 'Administrator erhält eine Kopie jeder Kontaktnachricht', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="cc_sender"><?php _e( 'Absender in Kopie:', $this->text_domain ); ?></label></th>
						<td>
							<input type="hidden" name="cc_sender" value="0" />
							<label>
							<input type="checkbox" id="cc_sender" name="cc_sender" value="1" <?php checked( isset( $options['cc_sender'] ) && 1 == $options['cc_sender'] ); ?> />
							<span class="description"><?php _e( 'Absender erhält eine Kopie seiner eigenen Nachricht', $this->text_domain ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="email_subject"><?php _e( 'E-Mail-Betreff:', $this->text_domain ); ?></label></th>
						<td>
							<input class="cf-full" type="text" id="email_subject" name="email_subject" value="<?php echo ( isset( $options['email_subject'] ) && '' != $options['email_subject'] ) ? $options['email_subject'] : 'SITE_NAME Contact Request: FROM_SUBJECT [ POST_TITLE ]'; ?>" />
							<br />
							<span class="description"><?php _e( 'Platzhalter: TO_NAME, FROM_NAME, FROM_EMAIL, FROM_SUBJECT, FROM_MESSAGE, POST_TITLE, POST_LINK, SITE_NAME', $this->text_domain ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="email_content"><?php _e( 'E-Mail-Inhalt:', $this->text_domain ); ?></label></th>
						<td>
							<textarea class="cf-full" id="email_content" name="email_content" rows="10" wrap="hard" ><?php
								echo esc_textarea( empty($options['email_content']) ? $default_email : $options['email_content'] );
							?></textarea>
							<br />
							<span class="description"><?php _e( 'Platzhalter: TO_NAME, FROM_NAME, FROM_EMAIL, FROM_SUBJECT, FROM_MESSAGE, POST_TITLE, POST_LINK, SITE_NAME', $this->text_domain ); ?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<p class="submit">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="hidden" name="key" value="general" />
			<input type="submit" class="button-primary" name="save" value="<?php _e( 'Aenderungen speichern', $this->text_domain ); ?>" />
		</p>
	</form>

</div>