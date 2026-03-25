<?php
/**
 * Plugin Name: TCD FAQ Accordion
 * Description: Elementor accordion widget for the FAQ custom post type. Full styling control, FAQPage schema output, zero dependencies. Built by The Creative Depot.
 * Version: 1.0.0
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

define( 'TCD_FAQW_VERSION', '1.0.0' );
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
.tcd-faq-accordion { max-width: 100%; }
.tcd-faq-item { border-bottom: 1px solid #e0e0e0; transition: background-color 0.2s ease; }
.tcd-faq-item:last-child { border-bottom: none; }
.tcd-faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
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
}
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
.tcd-faq-item.is-open .tcd-faq-icon { transform: rotate(45deg); }
.tcd-faq-answer {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.35s ease, padding 0.35s ease;
    padding: 0;
}
.tcd-faq-item.is-open .tcd-faq-answer { padding: 0 0 20px 0; }
.tcd-faq-answer-inner p:first-child { margin-top: 0; }
.tcd-faq-answer-inner p:last-child { margin-bottom: 0; }
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
    document.addEventListener("click", function(e) {
        var btn = e.target.closest(".tcd-faq-question");
        if (!btn) return;
        var item = btn.closest(".tcd-faq-item");
        var accordion = btn.closest(".tcd-faq-accordion");
        var answer = item.querySelector(".tcd-faq-answer");
        var inner = item.querySelector(".tcd-faq-answer-inner");
        var isOpen = item.classList.contains("is-open");
        var collapseOthers = accordion && accordion.dataset.collapse === "yes";
        if (collapseOthers) {
            var openItems = accordion.querySelectorAll(".tcd-faq-item.is-open");
            for (var i = 0; i < openItems.length; i++) {
                if (openItems[i] !== item) {
                    openItems[i].classList.remove("is-open");
                    openItems[i].querySelector(".tcd-faq-question").setAttribute("aria-expanded", "false");
                    openItems[i].querySelector(".tcd-faq-answer").style.maxHeight = "0";
                }
            }
        }
        if (isOpen) {
            item.classList.remove("is-open");
            btn.setAttribute("aria-expanded", "false");
            answer.style.maxHeight = "0";
        } else {
            item.classList.add("is-open");
            btn.setAttribute("aria-expanded", "true");
            answer.style.maxHeight = inner.scrollHeight + "px";
        }
    });
});
' );
    wp_enqueue_script( 'tcd-faq-accordion' );
}
add_action( 'wp_enqueue_scripts', 'tcd_faqw_enqueue_scripts' );


/**
 * Helper: query FAQs
 */
function tcd_faqw_get_faqs( $category = '', $limit = -1 ) {
    $args = array(
        'post_type'      => 'faq',
        'posts_per_page' => intval( $limit ),
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    );

    if ( ! empty( $category ) ) {
        $args['tax_query'] = array( array(
            'taxonomy' => 'faq-category',
            'field'    => 'slug',
            'terms'    => array_map( 'sanitize_text_field', explode( ',', $category ) ),
        ) );
    }

    return get_posts( $args );
}


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

