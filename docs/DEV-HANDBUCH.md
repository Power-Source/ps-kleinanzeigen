# PS Kleinanzeigen - Dev-Handbuch

Dieses Handbuch ist fuer Entwickler gedacht, die das Plugin erweitern, integrieren oder debuggen.

## 1. Technischer Ueberblick

- Plugin-Typ: klassisches WordPress-Plugin (kein Composer-Autoload im Runtime-Pfad noetig)
- Hauptlogik: [core/core.php](../core/core.php)
- Frontend-Routing/Content: [core/main.php](../core/main.php), [core/class-template-content-service.php](../core/class-template-content-service.php)
- Admin: [core/admin.php](../core/admin.php)
- Frontend-AJAX: [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php), [core/class-favorites-manager.php](../core/class-favorites-manager.php), [core/class-quick-view-service.php](../core/class-quick-view-service.php)

## 2. Datenmodell (wichtigste Entitaeten)

- Post Type `classifieds`: eigentliche Anzeigen
- Post Type `cf_message`: interne Nachrichten/Threads zwischen Nutzern
- User-Meta `_cf_favorites`: Favoriten-Liste (eingeloggt)
- Cookie `cf_favorites`: Favoriten-Liste (Gast)
- User-Meta-Container ueber `CF_Transactions`: Credits, Order-Infos, Billing-Status

Typische Meta-Keys bei Anzeigen:
- `_cf_cost`
- `_cf_duration`
- `_expiration_date`
- `_cf_gallery_ids`

Typische Meta-Keys bei Nachrichten:
- `_cf_msg_recipient`
- `_cf_msg_post_id`
- `_cf_msg_thread_id`
- `_cf_msg_read_<user_id>`

## 3. Plugin-Lebenszyklus

Wichtige Registrierungen passieren in [core/core.php](../core/core.php):
- `init`
- `wp_loaded`
- `template_redirect`
- `wp_enqueue_scripts`
- `pre_get_posts`
- diverse AJAX-Actions

Frontend-Seiten (virtuelle/verwaltete Pages) werden beim Laden erstellt/verwaltet, u.a.:
- `classifieds`
- `my-classifieds`
- `checkout`
- `add-classified`
- `edit-classified`
- `my-credits`
- `signin`

## 4. Security- und Coding-Konventionen

Empfohlenes Muster in diesem Plugin:
- AJAX immer mit `check_ajax_referer(...)`
- Zusaetzlich Capability-/Login-Check (z.B. `is_user_logged_in()`, `current_user_can()`)
- Eingaben mit `sanitize_*` und `absint()`
- Ausgaben mit `esc_html()`, `esc_attr()`, `esc_url()`

Aktive Nonces:
- `cf_frontend_actions` (Frontend-Favoriten/QuickView/Dashboard)
- `cf_send_message` (Messaging-Endpunkte)
- `verify` (Admin-Rollen/Caps AJAX)

## 5. Hook-Sammlung (Actions/Filter)

## 5.1 Eigene Extension-Hooks (vom Plugin bereitgestellt)

### Actions

1. `cf_handle_credits_requests`
- Ort: [core/admin.php](../core/admin.php)
- Wann: nach Verarbeitung/Rendering des Credits-Admin-Tabs
- Zweck: eigene Zusatzaktionen im Credits-Backend

2. `classifieds_affiliate_settings`
- Ort: [ui-admin/settings-affiliate.php](../ui-admin/settings-affiliate.php)
- Parameter: `$affiliate_settings`
- Zweck: Affiliate-UI erweitern

3. `classifieds_affiliate_credit_purchase`
- Ort: [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php)
- Parameter: `$affiliate_settings, $user_id, $order_post_id, $purchased_credit_packages`
- Zweck: Provision/Tracking fuer gekaufte Credit-Pakete

4. `classifieds_affiliate_one_time_purchase`
- Ort: [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php)
- Parameter: `$affiliate_settings, $user_id, $order_post_id, $one_time_purchase`
- Zweck: Provision/Tracking fuer Einmalzahlung

### Filter

1. `classifieds_full_access`
- Ort: [core/core.php](../core/core.php)
- Input/Output: `bool $result`
- Zweck: entscheidet, ob ein User ohne Credits voll freigeschaltet ist

2. `cf_pagination`
- Ort: [core/core.php](../core/core.php)
- Input/Output: `string $pagination_html`
- Zweck: Paginations-HTML anpassen

3. `classifieds_custom_templates_dir`
- Ort: [core/class-template-content-service.php](../core/class-template-content-service.php)
- Input/Output: `string $dir`
- Zweck: alternatives Template-Verzeichnis setzen

4. `classifieds_custom_fields`
- Ort: [core/class-cf-fields.php](../core/class-cf-fields.php)
- Input/Output: `array $custom_fields`
- Zweck: Custom-Field-Definitionen erweitern/anpassen

5. `author_classifieds_link`
- Ort: [core/functions.php](../core/functions.php)
- Input/Output: `string $link, int $author_id, string $author_nicename`
- Zweck: Autoren-Link fuer Anzeigen anpassen

6. `cf_wp_kses_allowed_html`
- Ort: [core/functions.php](../core/functions.php)
- Input/Output: `array $allowed_html`
- Zweck: erlaubte HTML-Tags fuer Sanitizing steuern

## 5.2 Hook-Beispiele

### Full-Access fuer spezielle Rolle

```php
add_filter('classifieds_full_access', function ($has_access) {
    if (current_user_can('manage_options')) {
        return true;
    }
    return $has_access;
});
```

### Eigenes Template-Verzeichnis

```php
add_filter('classifieds_custom_templates_dir', function ($dir) {
    return WP_CONTENT_DIR . '/my-classifieds-templates';
});
```

### Erlaubte HTML-Tags erweitern

```php
add_filter('cf_wp_kses_allowed_html', function ($allowed) {
    $allowed['iframe'] = array(
        'src' => true,
        'width' => true,
        'height' => true,
        'allow' => true,
        'allowfullscreen' => true,
    );
    return $allowed;
});
```

### Affiliate-Credit-Purchase auswerten

```php
add_action('classifieds_affiliate_credit_purchase', function ($settings, $user_id, $order_id, $packages) {
    // Beispiel: Logging in eigene Tabelle / externes Tracking
}, 10, 4);
```

## 6. AJAX-Endpunkte

1. `cf_get_caps`
- Auth: nur eingeloggte Admins
- Nonce: `verify`
- Handler: [core/admin.php](../core/admin.php)

2. `cf_save`
- Auth: Admin
- Handler: [core/admin.php](../core/admin.php)

3. `cf_toggle_favorite` / `nopriv`
- Nonce: `cf_frontend_actions`
- Handler: [core/class-favorites-manager.php](../core/class-favorites-manager.php)

4. `cf_quick_view` / `nopriv`
- Nonce: `cf_frontend_actions`
- Handler: [core/class-quick-view-service.php](../core/class-quick-view-service.php)

5. `cf_send_message`
- Nonce: `cf_send_message`
- Handler: [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php)

6. `cf_get_conversation`
- Nonce: `cf_send_message`
- Handler: [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php)

7. `cf_mark_messages_read`
- Nonce: `cf_send_message`
- Handler: [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php)

8. `cf_load_dashboard_tab` / `nopriv`
- Nonce: `cf_frontend_actions`
- Handler: [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php)

## 7. Shortcodes

Definiert in [core/core.php](../core/core.php), implementiert in [core/class-shortcode-service.php](../core/class-shortcode-service.php):

- `[cf_list_categories]`
- `[cf_classifieds_btn]`
- `[cf_add_classified_btn]`
- `[cf_edit_classified_btn]`
- `[cf_checkout_btn]`
- `[cf_my_credits_btn]`
- `[cf_my_classifieds_btn]`
- `[cf_profile_btn]`
- `[cf_logout_btn]`
- `[cf_signin_btn]`
- `[cf_custom_fields]`
- `[cf_user_classifieds]` (Native-Community-Integration)

Beispiel:

```text
[cf_checkout_btn text="Jetzt zahlen" view="both"]
```

## 8. Performance-Hinweise

Aktuell bereits optimiert:
- Messaging-Reads in Batches statt unbounded Query
- Unread-Count ueber leichte Query statt volles Laden
- Expiration-Cron in IDs/Batches
- Member-Classifieds-Count via `count_user_posts()`

Bei neuen Features bitte beibehalten:
- keine `posts_per_page = -1` bei potenziell grossen Datenmengen
- `no_found_rows => true` wenn keine Pagination-Counts benoetigt werden

## 9. Release-Checkliste fuer Entwickler

1. PHP Lint auf geaenderte Dateien
2. Nonce + Capability in neuen AJAX-Endpunkten
3. Strings mit Textdomain
4. Keine unbounded Queries
5. Smoke-Tests:
- Anzeigen erstellen/bearbeiten
- Favoriten + QuickView
- Messaging (senden, lesen, markieren)
- Checkout/Order-Pfad
