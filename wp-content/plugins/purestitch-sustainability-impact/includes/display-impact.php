<?php

function show_sustainability_impact() {
    global $product;

    if (!$product instanceof WC_Product) return;

    $product_id = $product->get_id();

    // Get saved values
    $material = get_post_meta($product_id, '_material_type', true);
    $water_saved = get_post_meta($product_id, '_water_saved', true);
    $co2_saved = get_post_meta($product_id, '_co2_saved', true);

    // AI fallback using description
    if (empty($water_saved) || empty($co2_saved)) {
        $description = strtolower($product->get_description());
        $ai_estimates = detect_material_from_description($description);

        if (empty($water_saved)) $water_saved = $ai_estimates['water'];
        if (empty($co2_saved)) $co2_saved = $ai_estimates['co2'];
    }

    if (!$water_saved && !$co2_saved) {
        echo '<div class="sustainability-impact" style="margin-top:15px;padding:15px;border:2px dashed #ccc;border-radius:10px;background:#fafafa;">';
        echo '<p><em>No sustainability data available for this material.</em></p>';
        echo '</div>';
        return;
    }
    

    echo '<div class="sustainability-impact" style="margin-top:15px;padding:15px;border:2px dashed #4CAF50;border-radius:10px;background:#f6fff4;">';
    echo '<h4 style="color:#2e7d32;margin-bottom:10px;">🌿 Sustainability Impact</h4>';

    if ($water_saved) echo '<p>💧 <strong>' . esc_html($water_saved) . 'L</strong> water saved by choosing this sustainable product.</p>';
    if ($co2_saved) echo '<p>🌍 <strong>' . esc_html($co2_saved) . 'kg</strong> CO₂ emissions reduced.</p>';

    echo '</div>';
}

// AI-Lite Material Detection Function
function detect_material_from_description($description) {
    $materials = [
        'organic cotton' => ['water' => 2700, 'co2' => 1.5],
        'bamboo' => ['water' => 3000, 'co2' => 2.5],
        'recycled polyester' => ['water' => 2000, 'co2' => 2.1],
        'hemp' => ['water' => 3300, 'co2' => 2.8]
    ];

    foreach ($materials as $keyword => $impact) {
        if (strpos($description, $keyword) !== false) {
            return $impact;
        }
    }

    return ['water' => null, 'co2' => null];
}
