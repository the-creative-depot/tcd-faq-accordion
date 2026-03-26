<?php
/**
 * Plugin Name: TCD FAQ Accordion
 * Description: Elementor accordion widget for the FAQ custom post type. Full styling control, FAQPage schema output, zero dependencies. Built by The Creative Depot.
 * Version: 1.2.3
 * Author: The Creative Depot
 * Author URI: https://thecreativedepot.com
 * License: GPL v2 or later
 * Text Domain: tcd-faq-accordion
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TCD_FAQW_VERSION', '1.2.3' );
define( 'TCD_FAQW_PATH', plugin_dir_path( __FILE__ ) );


/**
 * Register Elementor widget
 */
function tcd_faqw_register_widget( $widgets_manager ) {
    require_once TCD_FAQW_PATH . 'class-tcd-faq-accordion.php';
    $widgets_manager->register( new \TCD_FAQ_Accordion_Widget() );
}
add_action( 'elementor/widgets/register', 'tcd_faqw_register_widget' );


/**
 * Register widget category
 */
function tcd_faqw_elementor_category( $elements_manager ) {
    $elements_manager->add_category( 'tcd', array(
        'title' => 'TCD',
        'icon'  => 'eicon-folder',
    ) );
}
add_action( 'elementor/elements/categories_registered', 'tcd_faqw_elementor_category' );


/**
 * Frontend CSS
 */
function tcd_faqw_enqueue_styles() {
    wp_register_style( 'tcd-faq-accordion', false, array(), TCD_FAQW_VERSION );
    wp_add_inline_style( 'tcd-faq-accordion', '
.tcd-faq-accordion { max-width: 100%; box-sizing: border-box; overflow: hidden; }
.tcd-faq-accordion *, .tcd-faq-accordion *::before, .tcd-faq-accordion *::after { box-sizing: border-box; }
.tcd-faq-item { border-bottom: 1px solid #e0e0e0; transition: background-color 0.2s ease; }
.tcd-faq-item:last-child { border-bottom: none; }
.tcd-faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: 100%;
    padding: 20px 0;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    font-size: inherit;
    font-family: inherit;
    color: inherit;
    gap: 16px;
    line-height: 1.4;
    min-width: 0;
    overflow: hidden;
    white-space: normal;
}
.tcd-faq-question-text { flex: 1 1 0%; min-width: 0; overflow-wrap: break-word; word-break: break-word; }
.tcd-faq-question:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}
.tcd-faq-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    transition: transform 0.3s ease;
}
.tcd-faq-icon svg { width: 100%; height: 100%; display: block; }
.tcd-faq-item.is-open .tcd-faq-icon--rotate { transform: rotate(45deg); }
.tcd-faq-item.is-open .tcd-faq-icon--flip { transform: rotate(180deg); }
.tcd-faq-count { margin: 0 0 12px 0; padding: 0; font-size: 14px; color: #888; }
.tcd-faq-answer {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.35s ease, padding 0.35s ease;
    padding: 0;
}
.tcd-faq-item.is-open .tcd-faq-answer { padding: 0 0 20px 0; }
.tcd-faq-answer-inner { overflow-wrap: break-word; word-break: break-word; min-width: 0; }
.tcd-faq-answer-inner p:first-child { margin-top: 0; }
.tcd-faq-answer-inner p:last-child { margin-bottom: 0; }
.tcd-faq-answer-inner img, .tcd-faq-answer-inner video, .tcd-faq-answer-inner iframe, .tcd-faq-answer-inner table { max-width: 100%; height: auto; }
.tcd-faq-category-title { margin: 0 0 8px 0; padding: 0; }
.tcd-faq-category-group + .tcd-faq-category-group { margin-top: 32px; }
' );
    wp_enqueue_style( 'tcd-faq-accordion' );
}
add_action( 'wp_enqueue_scripts', 'tcd_faqw_enqueue_styles' );


/**
 * Frontend JS - vanilla, no dependencies
 */
function tcd_faqw_enqueue_scripts() {
    wp_register_script( 'tcd-faq-accordion', false, array(), TCD_FAQW_VERSION, true );
    wp_add_inline_script( 'tcd-faq-accordion', '
document.addEventListener("DOMContentLoaded", function() {

    function toggleItem(accordion, item, btn, forceOpen) {
        var answer = item.querySelector(".tcd-faq-answer");
        var inner = item.querySelector(".tcd-faq-answer-inner");
        var isOpen = item.classList.contains("is-open");
        var collapseOthers = accordion && accordion.dataset.collapse === "yes";
        var smoothScroll = accordion && accordion.dataset.smoothScroll === "yes";

        if (typeof forceOpen === "undefined") forceOpen = !isOpen;
        if (forceOpen === isOpen) return;

        if (forceOpen && collapseOthers) {
            var openItems = accordion.querySelectorAll(".tcd-faq-item.is-open");
            for (var i = 0; i < openItems.length; i++) {
                if (openItems[i] !== item) {
                    openItems[i].classList.remove("is-open");
                    openItems[i].querySelector(".tcd-faq-question").setAttribute("aria-expanded", "false");
                    openItems[i].querySelector(".tcd-faq-answer").style.maxHeight = "0";
                }
            }
        }

        if (forceOpen) {
            item.classList.add("is-open");
            btn.setAttribute("aria-expanded", "true");
            answer.style.maxHeight = inner.scrollHeight + "px";
            if (smoothScroll) {
                setTimeout(function() {
                    item.scrollIntoView({ behavior: "smooth", block: "nearest" });
                }, 100);
            }
        } else {
            item.classList.remove("is-open");
            btn.setAttribute("aria-expanded", "false");
            answer.style.maxHeight = "0";
        }
    }

    /* Click handler */
    document.addEventListener("click", function(e) {
        var btn = e.target.closest(".tcd-faq-question");
        if (!btn) return;
        var item = btn.closest(".tcd-faq-item");
        var accordion = btn.closest(".tcd-faq-accordion");
        toggleItem(accordion, item, btn);
    });

    /* Keyboard navigation (WAI-ARIA accordion pattern) */
    document.addEventListener("keydown", function(e) {
        var btn = e.target.closest(".tcd-faq-question");
        if (!btn) return;
        var accordion = btn.closest(".tcd-faq-accordion");
        if (!accordion) return;
        var buttons = Array.prototype.slice.call(accordion.querySelectorAll(".tcd-faq-question"));
        var index = buttons.indexOf(btn);
        var next = -1;

        switch (e.key) {
            case "ArrowDown": next = (index + 1) % buttons.length; break;
            case "ArrowUp":   next = (index - 1 + buttons.length) % buttons.length; break;
            case "Home":      next = 0; break;
            case "End":       next = buttons.length - 1; break;
            default: return;
        }

        e.preventDefault();
        buttons[next].focus();
    });

    /* Set proper max-height for initially open items */
    var openItems = document.querySelectorAll(".tcd-faq-item.is-open");
    for (var i = 0; i < openItems.length; i++) {
        var answer = openItems[i].querySelector(".tcd-faq-answer");
        var inner = openItems[i].querySelector(".tcd-faq-answer-inner");
        if (answer && inner) {
            answer.style.maxHeight = inner.scrollHeight + "px";
        }
    }
});
' );
    wp_enqueue_script( 'tcd-faq-accordion' );
}
add_action( 'wp_enqueue_scripts', 'tcd_faqw_enqueue_scripts' );


/**
 * Get the FAQ post type slug (filterable)
 */
function tcd_faqw_cpt_slug() {
    return apply_filters( 'tcd_faqw_cpt_slug', 'faq' );
}

/**
 * Get the FAQ taxonomy slug (filterable)
 */
function tcd_faqw_tax_slug() {
    return apply_filters( 'tcd_faqw_tax_slug', 'faq-category' );
}

/**
 * Helper: query FAQs
 */
function tcd_faqw_get_faqs( $category = '', $limit = -1 ) {
    $args = array(
        'post_type'      => tcd_faqw_cpt_slug(),
        'posts_per_page' => intval( $limit ),
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    );

    if ( ! empty( $category ) ) {
        $args['tax_query'] = array( array(
            'taxonomy' => tcd_faqw_tax_slug(),
            'field'    => 'slug',
            'terms'    => array_map( 'sanitize_text_field', explode( ',', $category ) ),
        ) );
    }

    return get_posts( $args );
}

/**
 * Get FAQ categories with transient caching
 */
function tcd_faqw_get_categories() {
    $cache_key  = 'tcd_faqw_categories';
    $categories = get_transient( $cache_key );

    if ( false === $categories ) {
        $categories = get_terms( array(
            'taxonomy'   => tcd_faqw_tax_slug(),
            'hide_empty' => false,
        ) );
        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }
        set_transient( $cache_key, $categories, 5 * MINUTE_IN_SECONDS );
    }

    return $categories;
}

/**
 * Invalidate category cache when terms change
 */
function tcd_faqw_flush_category_cache( $term_id, $tt_id, $taxonomy ) {
    if ( $taxonomy === tcd_faqw_tax_slug() ) {
        delete_transient( 'tcd_faqw_categories' );
    }
}
add_action( 'created_term', 'tcd_faqw_flush_category_cache', 10, 3 );
add_action( 'edited_term', 'tcd_faqw_flush_category_cache', 10, 3 );
add_action( 'delete_term', 'tcd_faqw_flush_category_cache', 10, 3 );


/**
 * GitHub Updater
 *
 * To enable auto-updates from your private GitHub repo:
 * 1. Create a GitHub Personal Access Token (classic) with 'repo' scope
 * 2. Add this line to wp-config.php (above "That's all, stop editing!"):
 *    define( 'TCD_GITHUB_TOKEN', 'ghp_your_token_here' );
 */
function tcd_faqw_init_updater() {
    if ( ! is_admin() ) {
        return;
    }

    require_once TCD_FAQW_PATH . 'class-tcd-github-updater.php';

    $token = defined( 'TCD_GITHUB_TOKEN' ) ? TCD_GITHUB_TOKEN : '';

    new TCD_GitHub_Updater(
        __FILE__,
        'the-creative-depot/tcd-faq-accordion',  // Change to your GitHub owner/repo
        $token
    );
}
add_action( 'admin_init', 'tcd_faqw_init_updater' );

