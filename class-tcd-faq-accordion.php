<?php
/**
 * TCD FAQ Accordion Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TCD_FAQ_Accordion_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'tcd_faq_accordion';
    }

    public function get_title() {
        return 'FAQ Accordion';
    }

    public function get_icon() {
        return 'eicon-accordion';
    }

    public function get_categories() {
        return array( 'tcd' );
    }

    public function get_keywords() {
        return array( 'faq', 'accordion', 'question', 'answer', 'toggle' );
    }


    protected function register_controls() {

        // ── CONTENT: Source ─────────────────────────────────

        $this->start_controls_section( 'section_source', array(
            'label' => 'Source',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ) );

        $categories  = tcd_faqw_get_categories();
        $cat_options = array( '' => 'All Categories' );
        if ( ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                $cat_options[ $cat->slug ] = $cat->name;
            }
        }

        $this->add_control( 'faq-category', array(
            'label'   => 'Category',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $cat_options,
            'default' => '',
        ) );

        $this->add_control( 'faq_limit', array(
            'label'       => 'Max FAQs',
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => -1,
            'min'         => -1,
            'max'         => 100,
            'description' => '-1 for all',
        ) );

        $this->add_control( 'group_by_category', array(
            'label'       => 'Group by Category',
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => 'Yes',
            'label_off'   => 'No',
            'description' => 'Show category headings above each group',
        ) );

        $this->end_controls_section();

        // ── CONTENT: Behavior ───────────────────────────────

        $this->start_controls_section( 'section_behavior', array(
            'label' => 'Behavior',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ) );

        $this->add_control( 'collapse_others', array(
            'label'     => 'Collapse Others on Open',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => 'Yes',
            'label_off' => 'No',
        ) );

        $this->add_control( 'first_open', array(
            'label'     => 'First Item Open by Default',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => '',
            'label_on'  => 'Yes',
            'label_off' => 'No',
        ) );

        $this->add_control( 'smooth_scroll', array(
            'label'       => 'Smooth Scroll to Active',
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => 'Yes',
            'label_off'   => 'No',
            'description' => 'Scroll opened item into view on mobile',
        ) );

        $this->add_control( 'show_count', array(
            'label'       => 'Show FAQ Count',
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => 'Yes',
            'label_off'   => 'No',
            'description' => 'Display item number (e.g. 1 of 10)',
        ) );

        $this->add_control( 'output_schema', array(
            'label'       => 'Output FAQPage Schema',
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'label_on'    => 'Yes',
            'label_off'   => 'No',
            'description' => 'JSON-LD FAQPage structured data',
        ) );

        $this->end_controls_section();

        // ── STYLE: Item ─────────────────────────────────────

        $this->start_controls_section( 'section_style_item', array(
            'label' => 'Item',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ) );

        $this->add_control( 'item_border_color', array(
            'label'     => 'Border Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-item' => 'border-bottom-color: {{VALUE}};' ),
        ) );

        $this->add_control( 'item_border_width', array(
            'label'      => 'Border Width',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 10 ) ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-item' => 'border-bottom-width: {{SIZE}}{{UNIT}};' ),
        ) );

        $this->add_responsive_control( 'item_padding', array(
            'label'      => 'Padding',
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em', '%' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );

        $this->add_control( 'item_background', array(
            'label'     => 'Background',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-item' => 'background-color: {{VALUE}};' ),
        ) );

        $this->add_control( 'item_background_active', array(
            'label'     => 'Active Background',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-item.is-open' => 'background-color: {{VALUE}};' ),
        ) );

        $this->add_control( 'item_background_hover', array(
            'label'     => 'Hover Background',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-item:hover' => 'background-color: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'item_spacing', array(
            'label'      => 'Spacing Between Items',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-item + .tcd-faq-item' => 'margin-top: {{SIZE}}{{UNIT}};' ),
        ) );

        $this->add_control( 'item_border_radius', array(
            'label'      => 'Border Radius',
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'item_shadow',
            'selector' => '{{WRAPPER}} .tcd-faq-item',
        ) );

        $this->end_controls_section();

        // ── STYLE: Question ─────────────────────────────────

        $this->start_controls_section( 'section_style_question', array(
            'label' => 'Question',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ) );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
            'name'     => 'question_typography',
            'selector' => '{{WRAPPER}} .tcd-faq-question',
        ) );

        $this->add_control( 'question_color', array(
            'label'     => 'Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-question' => 'color: {{VALUE}};' ),
        ) );

        $this->add_control( 'question_color_hover', array(
            'label'     => 'Hover Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-question:hover' => 'color: {{VALUE}};' ),
        ) );

        $this->add_control( 'question_color_active', array(
            'label'     => 'Active Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-item.is-open .tcd-faq-question' => 'color: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'question_padding', array(
            'label'      => 'Padding',
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-question' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );

        $this->end_controls_section();

        // ── STYLE: Answer ───────────────────────────────────

        $this->start_controls_section( 'section_style_answer', array(
            'label' => 'Answer',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ) );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
            'name'     => 'answer_typography',
            'selector' => '{{WRAPPER}} .tcd-faq-answer-inner',
        ) );

        $this->add_control( 'answer_color', array(
            'label'     => 'Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-answer-inner' => 'color: {{VALUE}};' ),
        ) );

        $this->add_control( 'answer_link_color', array(
            'label'     => 'Link Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-answer-inner a' => 'color: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'answer_padding', array(
            'label'      => 'Padding',
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-answer-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
        ) );

        $this->end_controls_section();

        // ── STYLE: Icon ─────────────────────────────────────

        $this->start_controls_section( 'section_style_icon', array(
            'label' => 'Icon',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ) );

        $this->add_control( 'icon_style', array(
            'label'   => 'Icon Style',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'plus',
            'options' => array(
                'plus'    => 'Plus / Minus',
                'chevron' => 'Chevron',
                'caret'   => 'Caret (Triangle)',
            ),
        ) );

        $this->add_control( 'icon_color', array(
            'label'     => 'Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-icon svg' => 'stroke: {{VALUE}}; fill: {{VALUE}};' ),
        ) );

        $this->add_control( 'icon_color_active', array(
            'label'     => 'Active Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-item.is-open .tcd-faq-icon svg' => 'stroke: {{VALUE}}; fill: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'icon_size', array(
            'label'      => 'Size',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 10, 'max' => 50 ) ),
            'default'    => array( 'size' => 20, 'unit' => 'px' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
        ) );

        $this->add_control( 'icon_stroke_width', array(
            'label'      => 'Stroke Width',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 1, 'max' => 5, 'step' => 0.5 ) ),
            'default'    => array( 'size' => 2, 'unit' => 'px' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-icon svg' => 'stroke-width: {{SIZE}};' ),
            'condition'  => array( 'icon_style!' => 'caret' ),
        ) );

        $this->end_controls_section();

        // ── STYLE: FAQ Count ────────────────────────────────

        $this->start_controls_section( 'section_style_count', array(
            'label'     => 'FAQ Count',
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => array( 'show_count' => 'yes' ),
        ) );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
            'name'     => 'count_typography',
            'selector' => '{{WRAPPER}} .tcd-faq-count',
        ) );

        $this->add_control( 'count_color', array(
            'label'     => 'Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-count' => 'color: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'count_spacing', array(
            'label'      => 'Bottom Spacing',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
            'default'    => array( 'size' => 12, 'unit' => 'px' ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-count' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
        ) );

        $this->end_controls_section();

        // ── STYLE: Category Heading ─────────────────────────

        $this->start_controls_section( 'section_style_category_heading', array(
            'label'     => 'Category Heading',
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => array( 'group_by_category' => 'yes' ),
        ) );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
            'name'     => 'category_heading_typography',
            'selector' => '{{WRAPPER}} .tcd-faq-category-title',
        ) );

        $this->add_control( 'category_heading_color', array(
            'label'     => 'Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .tcd-faq-category-title' => 'color: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'category_heading_spacing', array(
            'label'      => 'Bottom Spacing',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-category-title' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
        ) );

        $this->add_responsive_control( 'category_group_spacing', array(
            'label'      => 'Spacing Between Groups',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
            'selectors'  => array( '{{WRAPPER}} .tcd-faq-category-group + .tcd-faq-category-group' => 'margin-top: {{SIZE}}{{UNIT}};' ),
        ) );

        $this->add_control( 'category_heading_tag', array(
            'label'   => 'HTML Tag',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'h3',
            'options' => array(
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'div'  => 'div',
                'span' => 'span',
            ),
        ) );

        $this->end_controls_section();
    }


    /**
     * Get SVG icon markup by style
     */
    private function get_icon_svg( $style ) {
        $icons = array(
            'plus'    => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-linecap="round"/><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-linecap="round"/></svg>',
            'chevron' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="6 9 12 15 18 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'caret'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polygon points="6 9 18 9 12 16" fill="currentColor" stroke="none"/></svg>',
        );

        return apply_filters( 'tcd_faqw_icon_svg', isset( $icons[ $style ] ) ? $icons[ $style ] : $icons['plus'], $style );
    }

    /**
     * Get CSS class for icon rotation style
     */
    private function get_icon_rotation_class( $style ) {
        if ( 'plus' === $style ) {
            return 'tcd-faq-icon--rotate';
        }
        return 'tcd-faq-icon--flip';
    }


    /**
     * Render
     */
    protected function render() {

        $settings      = $this->get_settings_for_display();
        $category      = sanitize_text_field( $settings['faq-category'] );
        $limit         = intval( $settings['faq_limit'] );
        $collapse      = $settings['collapse_others'] === 'yes' ? 'yes' : 'no';
        $first_open    = $settings['first_open'] === 'yes';
        $smooth_scroll = $settings['smooth_scroll'] === 'yes' ? 'yes' : 'no';
        $show_count    = $settings['show_count'] === 'yes';
        $output_schema = $settings['output_schema'] === 'yes';
        $group_by_cat  = $settings['group_by_category'] === 'yes';
        $heading_tag   = isset( $settings['category_heading_tag'] ) ? sanitize_key( $settings['category_heading_tag'] ) : 'h3';
        $icon_style    = isset( $settings['icon_style'] ) ? sanitize_key( $settings['icon_style'] ) : 'plus';

        $allowed_tags = array( 'h2', 'h3', 'h4', 'h5', 'div', 'span' );
        if ( ! in_array( $heading_tag, $allowed_tags, true ) ) {
            $heading_tag = 'h3';
        }

        $faqs = tcd_faqw_get_faqs( $category, $limit );

        if ( empty( $faqs ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p style="padding:20px;background:#f5f5f5;text-align:center;color:#666;">No FAQs found. Check your category selection.</p>';
            }
            return;
        }

        $icon_svg       = $this->get_icon_svg( $icon_style );
        $icon_class     = $this->get_icon_rotation_class( $icon_style );
        $total_faqs     = count( $faqs );
        $schema_items   = array();

        do_action( 'tcd_faqw_before_accordion', $settings, $faqs );

        if ( $group_by_cat ) {
            $tax_slug    = tcd_faqw_tax_slug();
            $categorized = array();
            $no_cat      = array();

            foreach ( $faqs as $faq ) {
                $terms = get_the_terms( $faq->ID, $tax_slug );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $term = $terms[0];
                    if ( ! isset( $categorized[ $term->slug ] ) ) {
                        $categorized[ $term->slug ] = array(
                            'name' => $term->name,
                            'faqs' => array(),
                        );
                    }
                    $categorized[ $term->slug ]['faqs'][] = $faq;
                } else {
                    $no_cat[] = $faq;
                }
            }

            echo '<div class="tcd-faq-accordion" data-collapse="' . esc_attr( $collapse ) . '" data-smooth-scroll="' . esc_attr( $smooth_scroll ) . '">';

            if ( $show_count ) {
                echo '<p class="tcd-faq-count">' . esc_html( sprintf( '%d %s', $total_faqs, _n( 'question', 'questions', $total_faqs, 'tcd-faq-accordion' ) ) ) . '</p>';
            }

            $faq_index = 0;

            foreach ( $categorized as $cat_data ) {
                echo '<div class="tcd-faq-category-group">';
                echo '<' . esc_html( $heading_tag ) . ' class="tcd-faq-category-title">' . esc_html( $cat_data['name'] ) . '</' . esc_html( $heading_tag ) . '>';
                foreach ( $cat_data['faqs'] as $faq ) {
                    do_action( 'tcd_faqw_before_faq_item', $faq, $faq_index );
                    $this->render_faq_item( $faq, $icon_svg, $icon_class, $first_open && $faq_index === 0, $faq_index + 1, $total_faqs, $show_count );
                    do_action( 'tcd_faqw_after_faq_item', $faq, $faq_index );
                    $schema_items[] = $faq;
                    $faq_index++;
                }
                echo '</div>';
            }

            if ( ! empty( $no_cat ) ) {
                echo '<div class="tcd-faq-category-group">';
                foreach ( $no_cat as $faq ) {
                    do_action( 'tcd_faqw_before_faq_item', $faq, $faq_index );
                    $this->render_faq_item( $faq, $icon_svg, $icon_class, $first_open && $faq_index === 0, $faq_index + 1, $total_faqs, $show_count );
                    do_action( 'tcd_faqw_after_faq_item', $faq, $faq_index );
                    $schema_items[] = $faq;
                    $faq_index++;
                }
                echo '</div>';
            }

            echo '</div>';

        } else {
            echo '<div class="tcd-faq-accordion" data-collapse="' . esc_attr( $collapse ) . '" data-smooth-scroll="' . esc_attr( $smooth_scroll ) . '">';

            if ( $show_count ) {
                echo '<p class="tcd-faq-count">' . esc_html( sprintf( '%d %s', $total_faqs, _n( 'question', 'questions', $total_faqs, 'tcd-faq-accordion' ) ) ) . '</p>';
            }

            foreach ( $faqs as $index => $faq ) {
                do_action( 'tcd_faqw_before_faq_item', $faq, $index );
                $this->render_faq_item( $faq, $icon_svg, $icon_class, $first_open && $index === 0, $index + 1, $total_faqs, $show_count );
                do_action( 'tcd_faqw_after_faq_item', $faq, $index );
                $schema_items[] = $faq;
            }
            echo '</div>';
        }

        do_action( 'tcd_faqw_after_accordion', $settings, $faqs );

        if ( $output_schema && ! empty( $schema_items ) ) {
            $schema = array(
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array(),
            );
            foreach ( $schema_items as $faq ) {
                $schema['mainEntity'][] = array(
                    '@type'          => 'Question',
                    'name'           => wp_strip_all_tags( get_the_title( $faq ) ),
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => wp_strip_all_tags( apply_filters( 'the_content', $faq->post_content ) ),
                    ),
                );
            }
            echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
        }
    }


    private function render_faq_item( $faq, $icon_svg, $icon_class, $is_open = false, $current = 0, $total = 0, $show_count = false ) {
        $open_class  = $is_open ? ' is-open' : '';
        $aria        = $is_open ? 'true' : 'false';
        $answer_id   = 'tcd-faq-answer-' . intval( $faq->ID );
        $question_id = 'tcd-faq-question-' . intval( $faq->ID );
        $max_height  = $is_open ? 'max-height:9999px;' : 'max-height:0;';
        $answer_html = apply_filters( 'the_content', $faq->post_content );

        echo '<div class="tcd-faq-item' . esc_attr( $open_class ) . '">';
        echo '<button class="tcd-faq-question" id="' . esc_attr( $question_id ) . '" aria-expanded="' . esc_attr( $aria ) . '" aria-controls="' . esc_attr( $answer_id ) . '">';
        echo '<span class="tcd-faq-question-text">' . esc_html( get_the_title( $faq ) ) . '</span>';
        echo '<span class="tcd-faq-icon ' . esc_attr( $icon_class ) . '" aria-hidden="true">' . $icon_svg . '</span>';
        echo '</button>';
        echo '<div class="tcd-faq-answer" id="' . esc_attr( $answer_id ) . '" role="region" aria-labelledby="' . esc_attr( $question_id ) . '" style="' . esc_attr( $max_height ) . '">';
        echo '<div class="tcd-faq-answer-inner">' . wp_kses_post( $answer_html ) . '</div>';
        echo '</div>';
        echo '</div>';
    }
}
