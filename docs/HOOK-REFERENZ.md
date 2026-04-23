# PS Kleinanzeigen - Hook-Referenz

Diese Referenz ist als API-Übersicht gedacht.

## 1. Eigene Extension-Hooks (vom Plugin emittiert)

| Hook | Typ | Signatur | Rueckgabe | Emittiert in |
|---|---|---|---|---|
| classifieds_full_access | Filter | bool $result | bool | [core/core.php](../core/core.php) |
| cf_pagination | Filter | string $pagination_html | string | [core/core.php](../core/core.php) |
| classifieds_custom_templates_dir | Filter | string $template_dir | string | [core/class-template-content-service.php](../core/class-template-content-service.php) |
| classifieds_custom_fields | Filter | array $custom_fields | array | [core/class-cf-fields.php](../core/class-cf-fields.php) |
| author_classifieds_link | Filter | string $link, int $author_id, string $author_nicename | string | [core/functions.php](../core/functions.php) |
| cf_wp_kses_allowed_html | Filter | array $allowed_html | array | [core/functions.php](../core/functions.php) |
| cf_handle_credits_requests | Action | none | void | [core/admin.php](../core/admin.php) |
| classifieds_affiliate_settings | Action | array $affiliate_settings | void | [ui-admin/settings-affiliate.php](../ui-admin/settings-affiliate.php) |
| classifieds_affiliate_credit_purchase | Action | array $affiliate_settings, int $user_id, int $order_post_id, array $purchased_credit_packages | void | [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php) |
| classifieds_affiliate_one_time_purchase | Action | array $affiliate_settings, int $user_id, int $order_post_id, array $one_time_purchase | void | [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php) |

## 2. Registrierte WordPress-Hooks (Kernel/Core)

| WP Hook | Typ | Callback | Datei |
|---|---|---|---|
| init | Action | Classifieds_Core::init | [core/core.php](../core/core.php) |
| init | Action | Classifieds_Core::register_message_post_type | [core/core.php](../core/core.php) |
| wp_loaded | Action | Classifieds_Core::create_default_pages | [core/core.php](../core/core.php) |
| wp_loaded | Action | Classifieds_Core::schedule_expiration_check | [core/core.php](../core/core.php) |
| check_expiration_dates | Action | Classifieds_Core::check_expiration_dates_callback | [core/core.php](../core/core.php) |
| template_redirect | Action | Classifieds_Core::handle_login_requests | [core/core.php](../core/core.php) |
| template_redirect | Action | CF_Contact_Form_Service::handle_contact_form_requests | [core/core.php](../core/core.php) |
| wp_enqueue_scripts | Action | Classifieds_Core::on_enqueue_scripts | [core/core.php](../core/core.php) |
| wp_print_scripts | Action | Classifieds_Core::on_print_scripts | [core/core.php](../core/core.php) |
| pre_get_posts | Action | Classifieds_Core::on_pre_get_posts | [core/core.php](../core/core.php) |
| pre_get_posts | Filter | Classifieds_Core::pre_get_posts_for_classifieds | [core/core.php](../core/core.php) |
| parse_query | Filter | Classifieds_Core::on_parse_query | [core/core.php](../core/core.php) |
| parse_query | Filter | Classifieds_Core::show_only_c_user_classifieds | [core/core.php](../core/core.php) |
| taxonomy_template | Filter | Classifieds_Core::get_taxonomy_template | [core/core.php](../core/core.php) |
| map_meta_cap | Filter | Classifieds_Core::map_meta_cap | [core/core.php](../core/core.php) |
| user_contactmethods | Filter | Classifieds_Core::contact_fields | [core/core.php](../core/core.php) |
| wp_page_menu_args | Filter | Classifieds_Core::hide_menu_pages | [core/core.php](../core/core.php) |
| admin_post_thumbnail_html | Filter | Classifieds_Core::on_admin_post_thumbnail_html | [core/core.php](../core/core.php) |
| activated_plugin | Action | cf_flag_activation | [core/core.php](../core/core.php) |
| plugins_loaded | Action | cf_on_plugins_loaded | [core/core.php](../core/core.php) |

## 3. Registrierte WordPress-Hooks (Main Frontend-Routing)

| WP Hook | Typ | Callback | Datei |
|---|---|---|---|
| template_redirect | Action | Classifieds_Main::process_page_requests | [core/main.php](../core/main.php) |
| template_redirect | Action | Classifieds_Main::handle_page_requests | [core/main.php](../core/main.php) |
| wp_enqueue_scripts | Action | Classifieds_Main::enqueue_scripts | [core/main.php](../core/main.php) |
| author_link | Filter | Classifieds_Main::on_author_link | [core/main.php](../core/main.php) |
| the_title | Filter | Classifieds_Main::page_title_output | [core/main.php](../core/main.php) |
| the_content | Filter | Classifieds_Main::classifieds_content und weitere Content-Renderer | [core/main.php](../core/main.php) |
| template_include | Filter | Classifieds_Main::custom_classifieds_template | [core/main.php](../core/main.php) |

## 4. Registrierte WordPress-Hooks (Admin)

| WP Hook | Typ | Callback | Datei |
|---|---|---|---|
| admin_menu | Action | Classifieds_Core_Admin::admin_menu | [core/admin.php](../core/admin.php) |
| admin_init | Action | Classifieds_Core_Admin::admin_head | [core/admin.php](../core/admin.php) |
| admin_init | Action | Classifieds_Core_Admin::tutorial_script | [core/admin.php](../core/admin.php) |
| admin_enqueue_scripts | Action | Classifieds_Core_Admin::block_external_cdn_assets | [core/admin.php](../core/admin.php) |
| admin_print_footer_scripts | Action | Classifieds_Core_Admin::print_tutorial_script | [core/admin.php](../core/admin.php) |
| save_post | Action | Classifieds_Core_Admin::save_expiration_date | [core/admin.php](../core/admin.php) |
| restrict_manage_posts | Action | Classifieds_Core_Admin::on_restrict_manage_posts | [core/admin.php](../core/admin.php) |
| user_has_cap | Filter | Classifieds_Core_Admin::determine_backend_cap | [core/admin.php](../core/admin.php) |

## 5. AJAX-Endpunkte (Action-Names fuer admin-ajax.php)

| AJAX Action | Auth | Nonce | Callback | Datei |
|---|---|---|---|---|
| cf_get_caps | eingeloggte Admins | verify | Classifieds_Core_Admin::ajax_get_caps | [core/admin.php](../core/admin.php) |
| cf_save | eingeloggte Admins | admin-intern | Classifieds_Core_Admin::ajax_save | [core/admin.php](../core/admin.php) |
| cf-captcha | nopriv + priv | endpoint-spezifisch | Classifieds_Core::on_captcha | [core/core.php](../core/core.php) |
| cf_toggle_favorite | nopriv + priv | cf_frontend_actions | CF_Favorites_Manager::ajax_toggle_favorite | [core/class-favorites-manager.php](../core/class-favorites-manager.php) |
| cf_quick_view | nopriv + priv | cf_frontend_actions | CF_Quick_View_Service::ajax_quick_view | [core/class-quick-view-service.php](../core/class-quick-view-service.php) |
| cf_send_message | priv | cf_send_message | CF_My_Classifieds_Ajax::ajax_send_message | [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php) |
| cf_get_conversation | priv | cf_send_message | CF_My_Classifieds_Ajax::ajax_get_conversation | [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php) |
| cf_mark_messages_read | priv | cf_send_message | CF_My_Classifieds_Ajax::ajax_mark_messages_read | [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php) |
| cf_load_dashboard_tab | nopriv + priv | cf_frontend_actions | CF_My_Classifieds_Ajax::ajax_load_dashboard_tab | [core/class-my-classifieds-ajax.php](../core/class-my-classifieds-ajax.php) |

## 6. Integrations-Hooks zu Fremdplugins

| Fremd-Hook | Typ | Zweck | Callback | Datei |
|---|---|---|---|---|
| mp_order_order_paid | Action | MarketPress-Order in Credits/One-Time synchronisieren | CF_MarketPress_Bridge::handle_order_paid | [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php) |
| affiliate_marketpress_should_process_order | Filter | generische Affiliate-Abrechnung fuer reine Kleinanzeigen-Orders ggf. unterbinden | CF_MarketPress_Bridge::maybe_skip_generic_marketpress_affiliate | [core/class-cf-marketpress-bridge.php](../core/class-cf-marketpress-bridge.php) |
| cpc_members_directory_user_details_hook | Action | Anzeigen-Summary im Community-Directory | PS_Native_Community::render_member_classifieds_summary | [core/class-native-community.php](../core/class-native-community.php) |
| cpc_members_profile_main_hook | Action | Anzeigen-Sektion im Community-Profil | PS_Native_Community::render_member_classifieds_section | [core/class-native-community.php](../core/class-native-community.php) |

## 7. Snippets fuer schnelle Erweiterung

### 7.1 Pagination-HTML anpassen

```php
add_filter('cf_pagination', function ($html) {
    return '<div class="my-pagination-wrapper">' . $html . '</div>';
});
```

### 7.2 Autoren-Link auf eigene Profilroute mappen

```php
add_filter('author_classifieds_link', function ($link, $author_id, $nicename) {
    return home_url('/profil/' . $author_id . '/anzeigen/');
}, 10, 3);
```

### 7.3 Affiliate-Settings-UI um eigenes Feld erweitern

```php
add_action('classifieds_affiliate_settings', function ($settings) {
    echo '<p>Eigene Affiliate-Erweiterung geladen.</p>';
}, 10, 1);
```

## 8. Hinweise

- Diese Referenz beschreibt den aktuellen Stand der Codebasis.
- Bei Hook-Erweiterungen immer Signatur + Datei aktualisieren.
- Fuer tiefere Erlaeuterungen siehe [DEV-HANDBUCH](./DEV-HANDBUCH.md) und [SCHNITTSTELLEN](./SCHNITTSTELLEN.md).

## 9. Callback-Parameter-Matrix (praktisch fuer Implementierer)

Diese Tabelle beschreibt die erwarteten Argumente fuer die wichtigsten Erweiterungs-Callbacks.

| Hook | Eigener Callback (Beispiel) | Parameter 1 | Parameter 2 | Parameter 3 | Parameter 4 |
|---|---|---|---|---|---|
| classifieds_full_access | my_full_access_filter | bool $result | - | - | - |
| cf_pagination | my_pagination_filter | string $pagination_html | - | - | - |
| classifieds_custom_templates_dir | my_templates_dir_filter | string $template_dir | - | - | - |
| classifieds_custom_fields | my_custom_fields_filter | array $custom_fields | - | - | - |
| author_classifieds_link | my_author_link_filter | string $link | int $author_id | string $author_nicename | - |
| cf_wp_kses_allowed_html | my_allowed_html_filter | array $allowed_html | - | - | - |
| classifieds_affiliate_settings | my_affiliate_settings_action | array $affiliate_settings | - | - | - |
| classifieds_affiliate_credit_purchase | my_affiliate_credit_action | array $affiliate_settings | int $user_id | int $order_post_id | array $purchased_credit_packages |
| classifieds_affiliate_one_time_purchase | my_affiliate_one_time_action | array $affiliate_settings | int $user_id | int $order_post_id | array $one_time_purchase |

### 9.1 Datenstruktur-Hinweise fuer Integrationen

`$affiliate_settings` (aus `classifieds_affiliate_settings`) enthaelt typischerweise:
- `credit_packages` (array von Paketen mit `product_id`, `label`, `credits`, `price`)
- `one_time` (array mit `enabled`, `price`, `label`)
- `cost` (affiliate-kostenbezogene Struktur)

`$purchased_credit_packages` (aus `classifieds_affiliate_credit_purchase`) ist ein Array von Eintraegen mit typischer Form:
- `product_id` (int)
- `label` (string)
- `credits` (int)
- `price` (string)
- `quantity` (int)

`$one_time_purchase` (aus `classifieds_affiliate_one_time_purchase`) ist typischerweise:
- `product_id` (int)
- `label` (string)
- `price` (string)
- `quantity` (int)

### 9.2 Implementierungsbeispiel mit Typchecks

```php
add_action('classifieds_affiliate_credit_purchase', function ($settings, $user_id, $order_id, $packages) {
    $user_id = (int) $user_id;
    $order_id = (int) $order_id;

    if ($user_id <= 0 || $order_id <= 0 || !is_array($packages)) {
        return;
    }

    foreach ($packages as $pkg) {
        $product_id = isset($pkg['product_id']) ? (int) $pkg['product_id'] : 0;
        $credits = isset($pkg['credits']) ? (int) $pkg['credits'] : 0;
        $qty = isset($pkg['quantity']) ? (int) $pkg['quantity'] : 0;

        if ($product_id <= 0 || $credits <= 0 || $qty <= 0) {
            continue;
        }

        // Eigene Logik hier
    }
}, 10, 4);
```
