<?php if (!defined('ABSPATH')) die('No direct access allowed!'); ?>

<div class="wrap">

	<?php $this->render_admin( 'navigation', array( 'page' => 'classifieds_settings', 'tab' => 'shortcodes' ) ); ?>
	<?php $this->render_admin( 'message' ); ?>

	<h1><?php _e( 'Kleinanzeigen Shortcodes', $this->text_domain ); ?></h1>

	<div class="postbox">
		<h3 class='hndle'><span><?php _e( 'Kleinanzeigen Shortcodes', $this->text_domain ) ?></span></h3>
		<div class="inside">
			<p>
				<?php _e( 'Mit Shortcodes kannst Du dynamische Kleinanzeigen-Inhalte in Beiträge und Seiten Deiner Webseite einbinden. Gib sie einfach ein oder füge sie dort in Deinen Beitrag oder Seiteninhalt ein, wo sie angezeigt werden sollen. Optionale Attribute können in einem Format wie <em>[shortcode attr1="value" attr2="value"]</em> hinzugefügt werden.', $this->text_domain ) ?>
			</p>
			<p>
				<?php _e( 'Attribute: ("|" bedeutet, das eine ODER das andere zu verwenden. dh style="grid" oder style="list" nicht style="grid | list")', $this->text_domain); ?>
				<br /><?php _e( 'text = <em>Text, der auf einer Schaltfläche angezeigt werden soll</em>', $this->text_domain ) ?>
				<br /><?php _e( 'view = <em>Ob die Schaltfläche sichtbar ist, wenn man angemeldet, abgemeldet oder beides ist</em>', $this->text_domain ) ?>
				<br /><?php _e( 'redirect = <em>Auf der Abmelden-Schaltfläche wird angegeben, zu welcher Seite nach dem Abmelden gewechselt werden soll</em>', $this->text_domain ) ?>
				<br /><?php _e( 'ccats = <em>Eine durch Kommas getrennte Liste der anzuzeigenden classifieds_categories ids</em>', $this->text_domain ) ?>
			</p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e( 'Liste der Kategorien:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_list_categories style="grid | list" ccats="1,2,3" ]</strong></code>
						<br /><span class="description"><?php _e( 'Zeigt eine Liste der Kleinanzeigenkategorien an.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Kleinanzeigen-Button:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_classifieds_btn text="<?php _e('Kleinanzeigen', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_classifieds_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Kleinanzeigen', $this->text_domain);?>[/cf_classifieds_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Seite mit der Kleinanzeigenliste. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Meine Kleinanzeigen-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_my_classifieds_btn text="<?php _e('Meine Kleinanzeigen', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_my_classifieds_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Meine Kleinanzeigen', $this->text_domain);?>[/cf_my_classifieds_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Seite „Meine Kleinanzeigen“. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Meine Kleinanzeigen Credits-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_my_credits_btn text="<?php _e('Meine Kleinanzeigen Credits', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_my_credits_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Kleinanzeige bearbeiten', $this->text_domain);?>[/cf_my_credits_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Kleinanzeigen-Kreditverwaltungsseite. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Kleinanzeigen Checkout-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_checkout_btn text="<?php _e('Zur Kasse', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_checkout_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Anmelden', $this->text_domain);?>[/cf_checkout_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Kleinanzeigen-Checkout-Seite. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Kleinanzeige hinzufügen-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_add_classified_btn text="<?php _e('Kleinanzeige hinzufügen', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_add_classified_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Kleinanzeige hinzufügen', $this->text_domain);?>[/cf_add_classified_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Seite Kleinanzeigen hinzufügen. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Kleinanzeige bearbeiten-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_edit_classified_btn text="<?php _e('Kleinanzeige bearbeiten', $this->text_domain);?>" post="post_id" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_edit_classified_btn post="post_id" view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Kleinanzeige bearbeiten', $this->text_domain);?>[/cf_edit_classified_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Seite Kleinanzeigen bearbeiten. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Profil-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_profile_btn text="<?php _e('Profil', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_profile_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Profil', $this->text_domain);?>[/cf_profile_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Profilseite. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Anmelden-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_signin_btn text="<?php _e('Anmelden', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_signin_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Anmelden', $this->text_domain);?>[/cf_signin_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Anmeldeseite. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Checkout-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_checkout_btn text="<?php _e('Zur Kasse', $this->text_domain);?>" view="loggedin | loggedout | both"]</strong></code> oder
						<br /><code><strong>[cf_checkout_btn view="loggedin | loggedout | both"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Anmelden', $this->text_domain);?>[/cf_checkout_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Checkout-Seite. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Abmelden-Schaltfläche:', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_logout_btn text="<?php _e('Abmelden', $this->text_domain);?>"  view="loggedin | loggedout | both" redirect="http://someurl"]</strong></code> oder
						<br /><code><strong>[cf_logout_btn  view="loggedin | loggedout | always" redirect="http://someurl"]&lt;img src="<?php _e('someimage.jpg', $this->text_domain); ?>" /&gt;<?php _e('Abmelden', $this->text_domain);?>[/cf_logout_btn]</strong></code>
						<br /><span class="description"><?php _e( 'Link zur Abmeldeseite. Erzeugt einen &lt;button&gt; &lt;/button&gt; mit den von Dir definierten Inhalten. Das Attribut "redirect" ist die URL, zu der nach dem Abmelden weitergeleitet wird.', $this->text_domain ) ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Regionen-Karte (PS Maps):', $this->text_domain ) ?></th>
					<td>
						<code><strong>[cf_regions_map]</strong></code>
						<br /><code><strong>[cf_regions_map preview="5" zoom="7" regions="12,18"]</strong></code>
						<br /><span class="description"><?php _e( 'Zeigt eine Regionenkarte mit Marker-Popups (Anzahl + Anzeigenvorschau). Verfuegbar, wenn PS Maps aktiv ist und in den Karten-Einstellungen aktiviert wurde.', $this->text_domain ) ?></span>
					</td>
				</tr>
			</table>
		</div>
	</div>

</div>
