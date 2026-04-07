# PS Kleinanzeigen - Schnittstellen zu anderen Plugins

Dieses Dokument beschreibt die aktuellen Integrationspunkte des Plugins mit anderen Plugins/Systemen.

## 1. MarketPress

Technische Anbindung:
- Bridge-Klasse: [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php)
- Registrierung:
- `add_action('mp_order_order_paid', ...)`
- `add_filter('affiliate_marketpress_should_process_order', ...)`

### 1.1 Verarbeitungslogik bei bezahlter Order

Beim Hook `mp_order_order_paid` wird:
1. Order validiert und gegen Doppelverarbeitung geschuetzt
2. User zur Order aufgeloest
3. Credit-Pakete/One-Time-Artikel aus den Plugin-Settings gemappt
4. Credits auf `CF_Transactions` gebucht
5. optional One-Time-Access gesetzt
6. Ergebnis in Order-Meta gespeichert

Verwendete Meta-Keys auf der Order:
- `_cf_mp_bridge_processed`
- `_cf_mp_bridge_result`

Relevante Payment-Settings (Option `payments`):
- `enable_marketpress_bridge`
- `mp_credits_product_id`
- `mp_one_time_product_id`
- `mp_credit_meta_key`
- `mp_credit_packages`

### 1.2 Integrations-Hooks fuer Drittlogik

- `classifieds_affiliate_credit_purchase`
- `classifieds_affiliate_one_time_purchase`

Beispiel:

```php
add_action('classifieds_affiliate_one_time_purchase', function ($settings, $user_id, $order_id, $purchase) {
    // Eigene Provisionslogik oder Event-Weitergabe
}, 10, 4);
```

### 1.3 Zusammenspiel mit Affiliate-Plugin

Filter `affiliate_marketpress_should_process_order` wird genutzt, um generische Affiliate-Verarbeitung fuer rein-Kleinanzeigen-Orders zu unterbinden (double payout vermeiden).

## 2. Affiliate-Plugin / Kleinanzeigen-Add-on

Admin-UI-Extension:
- Datei: [ui-admin/settings-affiliate.php](../ui-admin/settings-affiliate.php)
- Hook: `do_action('classifieds_affiliate_settings', $affiliate_settings)`

Erwarteter Use-Case:
- Das externe Affiliate-Plugin rendert eigene Felder in der Kleinanzeigen-Affiliate-Seite.

Payload `$affiliate_settings` enthaelt:
- `credit_packages` (Produkt-/Credit-/Preis-Daten)
- `one_time` (`enabled`, `price`, `label`)
- `cost` (Affiliate-Kostenstruktur)

Minimalbeispiel fuer ein Add-on:

```php
add_action('classifieds_affiliate_settings', function ($settings) {
    echo '<pre>' . esc_html(print_r($settings, true)) . '</pre>';
});
```

## 3. PS-Mitgliedschaften (Membership)

Im Admin-Flow werden Membership-Klassen geprueft:
- `MS_Model_Membership`
- `MS_Model_Member`

Bedeutung:
- Wenn vorhanden, kann `enable_recurring` aktiv bleiben.
- Ohne diese Klassen wird recurring sicher deaktiviert.

Hinweis:
- Die eigentliche Abrechnung fuer wiederkehrende Zahlungen wird ausserhalb von Kleinanzeigen abgewickelt (laut Affiliate-Settings-UI-Hinweis).

## 4. Native Community / PS Community

Integration in Community-Profil:
- Datei: [core/class-native-community.php](../core/class-native-community.php)
- Eingehakte Hooks:
- `cpc_members_directory_user_details_hook`
- `cpc_members_profile_main_hook`

Aktivierung nur wenn Community-Core verfuegbar ist:
- `function_exists('cpc_members_is_core_enabled') && cpc_members_is_core_enabled()`

Zusaetzliche Schnittstelle:
- Shortcode `[cf_user_classifieds user_id="123" limit="10"]`

Nutzung im externen Community-Template:

```php
echo do_shortcode('[cf_user_classifieds user_id="' . get_current_user_id() . '" limit="6"]');
```

## 5. BuddyPress-Kompatibilitaet

Die Codebasis enthaelt weiterhin BuddyPress-kompatible Templatepfade (historische Kompatibilitaet), u.a. spezielle Template-Aufloesung im Core.

Praktischer Hinweis:
- Bei Custom-Themes sollte das konkrete Template-Override gegen die aktuelle Routing-Logik in [core/main.php](../core/main.php) und [core/class-template-content-service.php](../core/class-template-content-service.php) getestet werden.

## 6. Legacy-Payment (PayPal/Authorize.Net)

Legacy-Pfade sind im Code historisch vorhanden (z.B. `CF_Transactions`, `AuthnetXML.class.php`).

Aktueller Stand der Migration:
- In den Payment-Optionen werden `use_paypal` und `use_authorizenet` im Migrationspfad deaktiviert.
- Primarer Checkout-/Order-Flow laeuft ueber MarketPress-Bridge.

## 7. Integrations-Checkliste

Wenn ein anderes Plugin angebunden wird:
1. Trigger/Hooks dokumentieren (Name, Parameter, Timing)
2. Nonce- und Capability-Konzept pruefen
3. idempotente Verarbeitung sicherstellen (insb. bei Payment-Events)
4. Datenvertrag (Payload-Felder) versionieren
5. Logging nur ohne sensible Daten

## 8. Troubleshooting

### Order wurde nicht uebernommen

Pruefen:
1. Ist `enable_marketpress_bridge` aktiv?
2. Gibt es `_cf_mp_bridge_processed` auf der Order?
3. Sind Produkt-IDs in `mp_credit_packages`/One-Time korrekt gemappt?

### Affiliate-Provision fehlt

Pruefen:
1. Haken externes Affiliate-Plugin + Add-on aktiv?
2. Wird `classifieds_affiliate_credit_purchase`/`classifieds_affiliate_one_time_purchase` empfangen?
3. Fuer reine Kleinanzeigen-Orders kann `affiliate_marketpress_should_process_order` bewusst `false` liefern.
