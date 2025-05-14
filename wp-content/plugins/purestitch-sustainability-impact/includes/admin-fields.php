<?php

function add_sustainability_fields() {
    echo '<div class="options_group">';

    // Material Type
    woocommerce_wp_text_input([
        'id' => '_material_type',
        'label' => __('Material Type', 'woocommerce'),
        'description' => __('e.g., organic cotton, bamboo, etc.', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'text'
    ]);

    // Water Saved
    woocommerce_wp_text_input([
        'id' => '_water_saved',
        'label' => __('Water Saved (L)', 'woocommerce'),
        'description' => __('Liters of water saved', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => ['step' => 'any', 'min' => '0']
    ]);

    // CO2 Reduced
    woocommerce_wp_text_input([
        'id' => '_co2_saved',
        'label' => __('CO₂ Reduced (kg)', 'woocommerce'),
        'description' => __('Kg of CO₂ emissions reduced', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => ['step' => 'any', 'min' => '0']
    ]);

    echo '</div>';
}

function save_sustainability_fields($post_id) {
    if (isset($_POST['_material_type']))
        update_post_meta($post_id, '_material_type', sanitize_text_field($_POST['_material_type']));

    if (isset($_POST['_water_saved']))
        update_post_meta($post_id, '_water_saved', floatval($_POST['_water_saved']));

    if (isset($_POST['_co2_saved']))
        update_post_meta($post_id, '_co2_saved', floatval($_POST['_co2_saved']));
}
