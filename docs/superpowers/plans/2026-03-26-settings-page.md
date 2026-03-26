# Settings Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Settings > TCD FAQ Accordion page with guided CPT/taxonomy dropdowns so the plugin works with any FAQ post type slug.

**Architecture:** New `TCD_FAQ_Settings` class handles the admin settings page, admin notice, and cache flush. The existing slug helper functions are updated to read from `wp_options` first with filter fallback. No new dependencies.

**Tech Stack:** PHP 7.4+, WordPress Settings API, vanilla JS for taxonomy filtering

---

### Task 1: Create the Settings Class

**Files:**
- Create: `class-tcd-faq-settings.php`

- [ ] **Step 1: Create `class-tcd-faq-settings.php` with the full settings class**

```php
<?php
/**
 * TCD FAQ Accordion Settings Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TCD_FAQ_Settings {

    /**
     * Hook into WordPress admin
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'admin_notice' ) );
        add_action( 'update_option_tcd_faqw_cpt_slug', array( $this, 'on_settings_save' ) );
        add_action( 'update_option_tcd_faqw_tax_slug', array( $this, 'on_settings_save' ) );
    }

    /**
     * Register the options page under Settings
     */
    public function add_settings_page() {
        add_options_page(
            'TCD FAQ Accordion',
            'TCD FAQ Accordion',
            'manage_options',
            'tcd-faq-accordion',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings with the Settings API
     */
    public function register_settings() {
        register_setting( 'tcd_faqw_settings', 'tcd_faqw_cpt_slug', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default'           => '',
        ) );

        register_setting( 'tcd_faqw_settings', 'tcd_faqw_tax_slug', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default'           => '',
        ) );

        add_settings_section(
            'tcd_faqw_main',
            'Post Type Configuration',
            array( $this, 'render_section_description' ),
            'tcd-faq-accordion'
        );

        add_settings_field(
            'tcd_faqw_cpt_slug',
            'FAQ Post Type',
            array( $this, 'render_cpt_field' ),
            'tcd-faq-accordion',
            'tcd_faqw_main'
        );

        add_settings_field(
            'tcd_faqw_tax_slug',
            'FAQ Taxonomy',
            array( $this, 'render_tax_field' ),
            'tcd-faq-accordion',
            'tcd_faqw_main'
        );
    }

    /**
     * Section description
     */
    public function render_section_description() {
        echo '<p>Select the custom post type and taxonomy used for your FAQs. The taxonomy dropdown filters based on the selected post type.</p>';
    }

    /**
     * Build a map of public CPT slugs to their registered taxonomies
     */
    public function get_cpt_tax_map() {
        $post_types = get_post_types( array( '_builtin' => false, 'public' => true ), 'objects' );
        $map        = array();

        foreach ( $post_types as $pt ) {
            $taxonomies = get_object_taxonomies( $pt->name, 'objects' );
            $tax_list   = array();
            foreach ( $taxonomies as $tax ) {
                if ( $tax->public ) {
                    $tax_list[ $tax->name ] = $tax->labels->name . ' (' . $tax->name . ')';
                }
            }
            $map[ $pt->name ] = array(
                'label'      => $pt->labels->name . ' (' . $pt->name . ')',
                'taxonomies' => $tax_list,
            );
        }

        return $map;
    }

    /**
     * Render the CPT select dropdown
     */
    public function render_cpt_field() {
        $current = get_option( 'tcd_faqw_cpt_slug', '' );
        $map     = $this->get_cpt_tax_map();

        if ( empty( $map ) ) {
            echo '<p><em>No custom post types found. Register a FAQ post type using a plugin like SCF, ACF, or theme code.</em></p>';
            return;
        }

        echo '<select name="tcd_faqw_cpt_slug" id="tcd_faqw_cpt_slug">';
        echo '<option value="">-- Select Post Type --</option>';
        foreach ( $map as $slug => $data ) {
            echo '<option value="' . esc_attr( $slug ) . '"' . selected( $current, $slug, false ) . '>' . esc_html( $data['label'] ) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Render the taxonomy select dropdown
     */
    public function render_tax_field() {
        $current_tax = get_option( 'tcd_faqw_tax_slug', '' );
        $current_cpt = get_option( 'tcd_faqw_cpt_slug', '' );
        $map         = $this->get_cpt_tax_map();

        echo '<select name="tcd_faqw_tax_slug" id="tcd_faqw_tax_slug">';
        echo '<option value="">-- Select Taxonomy --</option>';

        if ( ! empty( $current_cpt ) && isset( $map[ $current_cpt ] ) ) {
            foreach ( $map[ $current_cpt ]['taxonomies'] as $slug => $label ) {
                echo '<option value="' . esc_attr( $slug ) . '"' . selected( $current_tax, $slug, false ) . '>' . esc_html( $label ) . '</option>';
            }
        }

        echo '</select>';

        $this->render_taxonomy_filter_script( $map, $current_tax );
    }

    /**
     * Output inline JS that filters the taxonomy dropdown when the CPT changes
     */
    private function render_taxonomy_filter_script( $map, $saved_tax ) {
        $js_map = array();
        foreach ( $map as $cpt_slug => $data ) {
            $js_map[ $cpt_slug ] = $data['taxonomies'];
        }

        ?>
        <script>
        (function() {
            var cptTaxMap = <?php echo wp_json_encode( $js_map ); ?>;
            var savedTax = <?php echo wp_json_encode( $saved_tax ); ?>;
            var cptSelect = document.getElementById("tcd_faqw_cpt_slug");
            var taxSelect = document.getElementById("tcd_faqw_tax_slug");

            if (!cptSelect || !taxSelect) return;

            cptSelect.addEventListener("change", function() {
                var taxonomies = cptTaxMap[this.value] || {};
                var slugs = Object.keys(taxonomies);

                while (taxSelect.firstChild) {
                    taxSelect.removeChild(taxSelect.firstChild);
                }

                if (slugs.length === 0) {
                    var opt = document.createElement("option");
                    opt.value = "";
                    opt.textContent = "No taxonomies found for this post type";
                    opt.disabled = true;
                    opt.selected = true;
                    taxSelect.appendChild(opt);
                    return;
                }

                var placeholder = document.createElement("option");
                placeholder.value = "";
                placeholder.textContent = "-- Select Taxonomy --";
                taxSelect.appendChild(placeholder);

                for (var i = 0; i < slugs.length; i++) {
                    var opt = document.createElement("option");
                    opt.value = slugs[i];
                    opt.textContent = taxonomies[slugs[i]];
                    if (slugs[i] === savedTax) {
                        opt.selected = true;
                    }
                    taxSelect.appendChild(opt);
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>TCD FAQ Accordion</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'tcd_faqw_settings' );
                do_settings_sections( 'tcd-faq-accordion' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Show admin notice when plugin is not configured
     */
    public function admin_notice() {
        $screen = get_current_screen();
        if ( $screen && $screen->id === 'settings_page_tcd-faq-accordion' ) {
            return;
        }

        $cpt = get_option( 'tcd_faqw_cpt_slug', '' );
        $tax = get_option( 'tcd_faqw_tax_slug', '' );

        if ( ! empty( $cpt ) && ! empty( $tax ) ) {
            return;
        }

        $url = admin_url( 'options-general.php?page=tcd-faq-accordion' );
        echo '<div class="notice notice-warning">';
        echo '<p><strong>TCD FAQ Accordion:</strong> Please select your FAQ post type and taxonomy in <a href="' . esc_url( $url ) . '">Settings &gt; TCD FAQ Accordion</a>.</p>';
        echo '</div>';
    }

    /**
     * Flush category transient when settings are saved
     */
    public function on_settings_save() {
        delete_transient( 'tcd_faqw_categories' );
    }
}
```

- [ ] **Step 2: Verify the file was created correctly**

Open `class-tcd-faq-settings.php` and confirm it contains the full class with all methods: `register`, `add_settings_page`, `register_settings`, `render_section_description`, `get_cpt_tax_map`, `render_cpt_field`, `render_tax_field`, `render_taxonomy_filter_script`, `render_settings_page`, `admin_notice`, `on_settings_save`.

- [ ] **Step 3: Commit**

```bash
git add class-tcd-faq-settings.php
git commit -m "v1.3.0 - Add settings class for CPT/taxonomy configuration"
```

---

### Task 2: Update Main Plugin File - Settings Bootstrap and Slug Functions

**Files:**
- Modify: `tcd-faq-accordion.php:1-19` (version bump + settings require)
- Modify: `tcd-faq-accordion.php:200-256` (slug functions + query guards)

- [ ] **Step 1: Bump version to 1.3.0**

In `tcd-faq-accordion.php`, change:

```
 * Version: 1.2.3
```
to:
```
 * Version: 1.3.0
```

And change:
```php
define( 'TCD_FAQW_VERSION', '1.2.3' );
```
to:
```php
define( 'TCD_FAQW_VERSION', '1.3.0' );
```

- [ ] **Step 2: Add settings class bootstrap**

After the `TCD_FAQW_PATH` constant definition (line 19), add:

```php


/**
 * Initialize settings page (admin only)
 */
function tcd_faqw_init_settings() {
    if ( ! is_admin() ) {
        return;
    }
    require_once TCD_FAQW_PATH . 'class-tcd-faq-settings.php';
    $settings = new TCD_FAQ_Settings();
    $settings->register();
}
add_action( 'plugins_loaded', 'tcd_faqw_init_settings' );
```

- [ ] **Step 3: Update `tcd_faqw_cpt_slug()` function**

Replace the existing function (lines 203-205):

```php
function tcd_faqw_cpt_slug() {
    return apply_filters( 'tcd_faqw_cpt_slug', 'faq' );
}
```

with:

```php
function tcd_faqw_cpt_slug() {
    $slug = get_option( 'tcd_faqw_cpt_slug', '' );
    if ( ! empty( $slug ) ) {
        return $slug;
    }
    return apply_filters( 'tcd_faqw_cpt_slug', '' );
}
```

- [ ] **Step 4: Update `tcd_faqw_tax_slug()` function**

Replace the existing function (lines 210-212):

```php
function tcd_faqw_tax_slug() {
    return apply_filters( 'tcd_faqw_tax_slug', 'faq-category' );
}
```

with:

```php
function tcd_faqw_tax_slug() {
    $slug = get_option( 'tcd_faqw_tax_slug', '' );
    if ( ! empty( $slug ) ) {
        return $slug;
    }
    return apply_filters( 'tcd_faqw_tax_slug', '' );
}
```

- [ ] **Step 5: Add early return guard to `tcd_faqw_get_faqs()`**

At the top of the `tcd_faqw_get_faqs()` function body, before the `$args` array (line 218), add:

```php
    if ( empty( tcd_faqw_cpt_slug() ) ) {
        return array();
    }
```

- [ ] **Step 6: Add early return guard to `tcd_faqw_get_categories()`**

At the top of the `tcd_faqw_get_categories()` function body, before the `$cache_key` line (line 241), add:

```php
    if ( empty( tcd_faqw_tax_slug() ) ) {
        return array();
    }
```

- [ ] **Step 7: Commit**

```bash
git add tcd-faq-accordion.php
git commit -m "v1.3.0 - Wire up settings, update slug resolution with empty defaults"
```

---

### Task 3: Add Plugin Action Links (Settings Link on Plugins Page)

**Files:**
- Modify: `tcd-faq-accordion.php` (add after settings bootstrap)

- [ ] **Step 1: Add the settings link filter**

After the `tcd_faqw_init_settings` function and its `add_action` call, add:

```php


/**
 * Add Settings link to plugin action links
 */
function tcd_faqw_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=tcd-faq-accordion' ) ) . '">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tcd_faqw_plugin_action_links' );
```

- [ ] **Step 2: Verify the link placement**

Read `tcd-faq-accordion.php` and confirm the filter is added after the settings bootstrap and before the Elementor widget registration function.

- [ ] **Step 3: Commit**

```bash
git add tcd-faq-accordion.php
git commit -m "v1.3.0 - Add Settings link to plugin action links on Plugins page"
```

---

### Task 4: Update claude.md

**Files:**
- Modify: `claude.md`

- [ ] **Step 1: Update the file structure section**

In `claude.md`, add the new file to the file structure listing. After the `class-tcd-github-updater.php` line, add:

```
  class-tcd-faq-settings.php      # Settings page: CPT/taxonomy slug configuration
```

- [ ] **Step 2: Replace the CPT and Taxonomy Slugs section**

Replace the existing "CPT and Taxonomy Slugs" section with:

```
CPT and Taxonomy Slugs

Configured via Settings > TCD FAQ Accordion. Stored in wp_options:
- tcd_faqw_cpt_slug - the FAQ post type slug
- tcd_faqw_tax_slug - the FAQ taxonomy slug

Slug resolution order:
1. Settings page value (get_option) if non-empty
2. Filter fallback: apply_filters( 'tcd_faqw_cpt_slug', '' ) / apply_filters( 'tcd_faqw_tax_slug', '' )
3. No hardcoded default. If both are empty, the plugin shows an admin notice and the widget renders nothing.

The settings page shows guided dropdowns: selecting a CPT filters the taxonomy dropdown to only show taxonomies registered to that post type. Inline JS handles the filtering (no AJAX).

When settings are saved, the tcd_faqw_categories transient is flushed automatically.

Always use the helper functions tcd_faqw_cpt_slug() and tcd_faqw_tax_slug() rather than hardcoding slugs or reading options directly.
```

- [ ] **Step 3: Add settings class to Architecture Decisions**

After the line about GitHub updater auth headers, add:

```
Settings page uses the WordPress Settings API (register_setting, add_settings_section, add_settings_field). Taxonomy filtering on the settings page uses inline vanilla JS with a server-built CPT-to-taxonomy map.
```

- [ ] **Step 4: Add to Things to Watch Out For**

Add at the end of the "Things to Watch Out For" section:

```
The plugin requires explicit configuration after install. If either tcd_faqw_cpt_slug or tcd_faqw_tax_slug option is empty, the widget renders nothing and an admin notice appears. This is intentional.
The settings page option names match the filter hook names (tcd_faqw_cpt_slug, tcd_faqw_tax_slug). The option takes priority over the filter.
```

- [ ] **Step 5: Commit**

```bash
git add claude.md
git commit -m "v1.3.0 - Update claude.md with settings page documentation"
```

---

### Task 5: Manual Verification Checklist

This task has no code changes. It is a verification checklist to run on a WordPress test site.

- [ ] **Step 1: Activate plugin on a test site with a registered FAQ CPT**

Confirm:
- Admin notice appears on the dashboard: "TCD FAQ Accordion: Please select your FAQ post type and taxonomy in Settings > TCD FAQ Accordion."
- The notice links to the correct settings page
- The notice does NOT appear on the settings page itself

- [ ] **Step 2: Verify the settings page**

Navigate to Settings > TCD FAQ Accordion. Confirm:
- The CPT dropdown lists only custom post types (no `post`, `page`, etc.)
- Each option shows "Label (slug)" format
- Selecting a CPT updates the taxonomy dropdown to show only its taxonomies
- Selecting a CPT with no taxonomies shows "No taxonomies found for this post type"
- The placeholder option "-- Select Post Type --" / "-- Select Taxonomy --" appears

- [ ] **Step 3: Save settings and verify widget**

Select the FAQ CPT and taxonomy, save. Confirm:
- Admin notice disappears after saving valid values
- The Elementor widget shows FAQs from the configured post type
- The category dropdown in the widget editor shows terms from the configured taxonomy

- [ ] **Step 4: Verify plugin action links**

Go to Plugins page. Confirm:
- "Settings" link appears next to Deactivate for TCD FAQ Accordion
- Clicking it goes to the settings page

- [ ] **Step 5: Verify filter fallback**

Remove both options from the database (or set them to empty). Add a filter in the theme:
```php
add_filter( 'tcd_faqw_cpt_slug', function() { return 'faq'; } );
add_filter( 'tcd_faqw_tax_slug', function() { return 'faq-category'; } );
```
Confirm the widget uses the filter values. Then set options via settings page and confirm they override the filter.
