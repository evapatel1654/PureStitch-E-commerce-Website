<?php
// Add custom fields in product editor
add_action('woocommerce_product_options_general_product_data', 'add_impact_fields');
function add_impact_fields() {
    woocommerce_wp_text_input([
        'id' => '_material_type',
        'label' => 'Material Type (e.g. organic_cotton, bamboo, recycled_polyester)',
        'type' => 'text',
        'desc_tip' => true,
        'description' => 'Enter the material type used.'
    ]);
    woocommerce_wp_text_input([
        'id' => '_water_saved',
        'label' => 'Water Saved (Liters)',
        'type' => 'number',
        'desc_tip' => true,
        'description' => 'Enter amount of water saved by using sustainable materials.'
    ]);
    woocommerce_wp_text_input([
        'id' => '_co2_saved',
        'label' => 'CO2 Reduced (kg)',
        'type' => 'number',
        'desc_tip' => true,
        'description' => 'Enter amount of carbon emissions saved.'
    ]);
}

// Save custom fields
add_action('woocommerce_process_product_meta', 'save_impact_fields');
function save_impact_fields($post_id) {
    update_post_meta($post_id, '_material_type', sanitize_text_field($_POST['_material_type']));
    update_post_meta($post_id, '_water_saved', sanitize_text_field($_POST['_water_saved']));
    update_post_meta($post_id, '_co2_saved', sanitize_text_field($_POST['_co2_saved']));
}
?>
