<?php
/*
Plugin Name: Sustainability Impact Display
Description: Show sustainability stats (Water Saved, CO₂ Reduced) on WooCommerce product pages using AI detection from product descriptions.
Version: 1.2
Author: Eva Patel
*/

if (!defined('ABSPATH')) exit;

add_action('woocommerce_single_product_summary', 'show_sustainability_impact', 35);
add_action('woocommerce_product_options_general_product_data', 'add_sustainability_fields');
add_action('woocommerce_process_product_meta', 'save_sustainability_fields');

// Include logic files
require_once plugin_dir_path(__FILE__) . 'includes/display-impact.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-fields.php';
