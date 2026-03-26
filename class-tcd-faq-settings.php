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
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

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
