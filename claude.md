TCD FAQ Accordion - Project Context
What This Is
An Elementor widget plugin that renders FAQ posts in an accessible accordion. Built by The Creative Depot for client sites running WordPress + Elementor. Currently deployed on Wellspring Health Center (wellspring-hc.com).
This plugin does NOT register any custom post types or taxonomies. Those are handled separately by Secure Custom Fields (SCF) on each site. This plugin is the display layer only.
File Structure
tcd-faq-accordion/
  tcd-faq-accordion.php          # Main plugin file: enqueues CSS/JS, helper functions, updater init
  class-tcd-faq-accordion.php    # Elementor widget: controls, render logic, schema output
  class-tcd-github-updater.php   # Auto-updater: checks private GitHub repo for new releases
  class-tcd-faq-settings.php      # Settings page: CPT/taxonomy slug configuration
  icon-256x256.png               # Plugin icon shown in WP Updates screen
  claude.md                      # This file (gitignored)
  .gitignore                     # Ignores claude.md
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
Architecture Decisions

Zero external dependencies. No npm, no Composer, no third-party libraries.
All CSS is inline via wp_add_inline_style. No external stylesheet files.
All JS is inline via wp_add_inline_script. No external script files. Vanilla JS only.
The widget renders in the "TCD" Elementor category.
FAQPage JSON-LD schema is output automatically by the widget (toggleable per instance).
Category list is cached with a 5-minute transient, auto-flushed on term create/edit/delete.
GitHub updater injects auth headers via http_request_args filter for private repo downloads.
Settings page uses the WordPress Settings API (register_setting, add_settings_section, add_settings_field). Taxonomy filtering on the settings page uses inline vanilla JS with a server-built CPT-to-taxonomy map.

Elementor Widget Features
Content Tab

Source: Category filter (dropdown), max FAQ limit, group by category toggle
Behavior: Collapse others, first item open, smooth scroll, show count, FAQPage schema toggle

Style Tab

Item: Border color/width, padding, background (default/active/hover), spacing, border radius, box shadow
Question: Typography, color (default/hover/active), padding
Answer: Typography, color, link color, padding
Icon: Style (plus/chevron/caret), color (default/active), size, stroke width, position (left/right)
Category Heading: Typography, color, bottom spacing, group spacing, HTML tag selector

Coding Standards

PHP 7.4 minimum. No PHP 8-only syntax.
All output escaped: esc_html, esc_attr, wp_kses_post.
Proper ARIA attributes on all interactive elements. Follows WAI-ARIA accordion pattern.
Keyboard navigation: Arrow keys, Home, End.
CSS class prefix: tcd-faq-
Function prefix: tcd_faqw_
No em dashes in comments or strings. Use "not" or commas instead.
Icon SVGs are filterable via tcd_faqw_icon_svg.
Action hooks: tcd_faqw_before_accordion, tcd_faqw_after_accordion, tcd_faqw_before_faq_item, tcd_faqw_after_faq_item

Version Management
Current version: check the Version: header in tcd-faq-accordion.php.
When making changes:

Update Version: in the plugin header AND the TCD_FAQW_VERSION constant (keep them in sync)
Commit with message format: v{X.Y.Z} - Description
Tag: git tag v{X.Y.Z}
Push: git push origin main --tags
Create a GitHub Release for the tag at https://github.com/the-creative-depot/tcd-faq-accordion/releases/new

WordPress picks up the new version automatically via the GitHub updater. Bump strategy: patch (1.0.X) for fixes, minor (1.X.0) for features, major (X.0.0) for breaking changes.
GitHub Updater Details

Repo: the-creative-depot/tcd-faq-accordion (private)
Auth: GitHub Personal Access Token stored in wp-config.php as TCD_GITHUB_TOKEN
The updater checks api.github.com/repos/.../releases/latest and compares the tag to the installed version
Auth headers are injected into download requests for api.github.com, github.com, and codeload.github.com URLs matching the repo
After install, the after_install method renames the extracted folder from GitHub's owner-repo-hash format to the proper plugin directory name

Things to Watch Out For

Never add publicly_queryable or has_archive to the FAQ post type. FAQ posts exist only to feed the widget.
The Elementor widget category dropdown control key is faq-category (with hyphen), matching the taxonomy slug. Do not change this without updating the render method's $settings['faq-category'] reference.
SiteGround's server-side object cache (Memcached) can aggressively cache taxonomy data. If terms seem missing after creation, the issue is likely caching, not code.
The after_install method in the updater handles GitHub's non-standard zip folder naming. Without it, updates would break the plugin directory structure.
When testing updates locally, delete the _site_transient_update_plugins row from the options table to force WordPress to recheck.
The plugin requires explicit configuration after install. If either tcd_faqw_cpt_slug or tcd_faqw_tax_slug option is empty, the widget renders nothing and an admin notice appears. This is intentional.
The settings page option names match the filter hook names (tcd_faqw_cpt_slug, tcd_faqw_tax_slug). The option takes priority over the filter.