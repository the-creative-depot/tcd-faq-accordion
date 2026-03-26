# TCD FAQ Accordion - Settings Page Design

## Problem

The plugin hardcodes default CPT slug (`faq`) and taxonomy slug (`faq-category`). Sites using different slugs for their FAQ content have no way to configure the plugin without writing custom filter code. The plugin should provide a settings page and require explicit configuration.

## Design

### Settings Page

- Location: **Settings > TCD FAQ Accordion** (`add_options_page`)
- Two fields stored in `wp_options`:
  - `tcd_faqw_cpt_slug` (string) - the selected custom post type slug
  - `tcd_faqw_tax_slug` (string) - the selected taxonomy slug
- Uses WordPress Settings API (`register_setting`, `add_settings_section`, `add_settings_field`)

### Guided Dropdowns

The CPT dropdown lists all public custom post types, excluding WordPress built-ins (`post`, `page`, `attachment`, `revision`, `nav_menu_item`, `custom_css`, `customize_changeset`, `oembed_cache`, `user_request`, `wp_block`, `wp_template`, `wp_template_part`, `wp_global_styles`, `wp_navigation`, `wp_font_family`, `wp_font_face`). Use `get_post_types( array( '_builtin' => false, 'public' => true ), 'objects' )` to get only user-registered public CPTs.

When a CPT is selected, the taxonomy dropdown filters to show only taxonomies registered to that post type. This filtering uses inline JavaScript: on page load, a JS object maps each post type to its taxonomies (built server-side via `get_object_taxonomies`). When the CPT dropdown changes, JS rebuilds the taxonomy dropdown options. No AJAX needed.

Both dropdowns show the post type/taxonomy label with the slug in parentheses, e.g. "FAQs (faq)", "FAQ Category (faq-category)".

If no custom post types or taxonomies are registered on the site, the settings page displays a message explaining that the plugin requires a FAQ post type and taxonomy to be registered (by a plugin like SCF, ACF, or theme code).

### Admin Notice

On every admin page load, if either `tcd_faqw_cpt_slug` or `tcd_faqw_tax_slug` option is empty/missing:
- Show a non-dismissable `notice-warning` admin notice
- Text: "TCD FAQ Accordion: Please select your FAQ post type and taxonomy in Settings > TCD FAQ Accordion."
- The "Settings > TCD FAQ Accordion" text links to the settings page
- The notice does not appear on the settings page itself (no need to nag there)

### Widget Behavior When Unconfigured

When either slug option is empty, `tcd_faqw_get_faqs()` and `tcd_faqw_get_categories()` return empty arrays. The widget renders nothing. This prevents queries against nonexistent post types.

### Slug Resolution Order

The `tcd_faqw_cpt_slug()` and `tcd_faqw_tax_slug()` functions change to:

1. Return the settings page value if the option exists and is non-empty
2. Otherwise, apply the existing filter with an empty string default: `apply_filters( 'tcd_faqw_cpt_slug', '' )`
3. No hardcoded fallback - if both are empty, the plugin is considered unconfigured

### Cache Invalidation on Settings Save

When settings are saved, delete the `tcd_faqw_categories` transient so the widget immediately reflects the new taxonomy.

### Elementor Widget Category Control

The category dropdown in the Elementor widget (`register_controls`) currently hardcodes its options from `tcd_faqw_get_categories()`. This already uses `tcd_faqw_tax_slug()` internally, so no changes are needed to the widget class. It will automatically pull categories from whichever taxonomy is configured.

## New File

### `class-tcd-faq-settings.php`

A single class `TCD_FAQ_Settings` responsible for:

- `register()` - hooks into `admin_menu` and `admin_init`
- `add_settings_page()` - registers the options page under Settings
- `register_settings()` - registers both options with the Settings API, including sanitization callbacks
- `render_settings_page()` - outputs the form HTML with both dropdowns
- `render_cpt_field()` - renders the CPT select dropdown
- `render_tax_field()` - renders the taxonomy select dropdown
- `get_cpt_tax_map()` - returns a PHP array mapping each public CPT slug to its registered taxonomies (used for both the server-rendered HTML and the inline JS filter logic)
- `admin_notice()` - conditionally displays the configuration notice
- `on_settings_save()` - flushes the category transient on update via `update_option_{option}` hook

All output properly escaped per the plugin's coding standards. Class prefix follows convention: `TCD_FAQ_`.

## Changes to Existing Files

### `tcd-faq-accordion.php`

1. **Require and initialize** `class-tcd-faq-settings.php` on `plugins_loaded` (admin only)
2. **Update `tcd_faqw_cpt_slug()`** - check `get_option('tcd_faqw_cpt_slug')` first, fall back to filter with empty default
3. **Update `tcd_faqw_tax_slug()`** - same pattern
4. **Update `tcd_faqw_get_faqs()`** - return empty array if slug is empty
5. **Update `tcd_faqw_get_categories()`** - return empty array if slug is empty
6. **Add settings link** to the plugin's action links on the Plugins page (`plugin_action_links_{plugin}` filter)

### `class-tcd-faq-accordion.php`

No changes required. The widget already uses the helper functions which will now read from settings.

### `claude.md`

Update to document the new settings page, the settings class, and the updated slug resolution order.

## Inline JS for Taxonomy Filtering

On the settings page, a small inline script (following the plugin's zero-dependency convention):

```
- On page load, read the CPT-to-taxonomy map from a JS object (output via wp_add_inline_script or a script tag in the render method)
- On CPT dropdown change, rebuild taxonomy dropdown options
- If the currently saved taxonomy is in the new list, keep it selected
- If no taxonomies exist for the selected CPT, show a disabled option: "No taxonomies found for this post type"
```

## Security

- Both options sanitized via `sanitize_key` (slugs are lowercase alphanumeric with hyphens/underscores)
- Settings page requires `manage_options` capability
- All output escaped with `esc_html`, `esc_attr`, `esc_url`
- Nonce verification handled by WordPress Settings API

## Version

This is a minor feature addition: bump to **1.3.0**.
