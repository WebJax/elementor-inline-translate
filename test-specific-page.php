<?php
/**
 * Test bulk translation with specific page that has Elementor content
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please log in as an administrator.');
}

echo "<h1>Specific Page Bulk Translation Test</h1>\n";

// Use page 401 which we know has Elementor content
$page_id = 401;
echo "<p>Testing page ID: $page_id</p>\n";

// Get the plugin instance
$plugin = Elementor_Inline_Translate::instance();

// Simulate the AJAX request
$_POST['nonce'] = wp_create_nonce('eit_translate_nonce');
$_POST['page_id'] = $page_id;
$_POST['target_language'] = 'EN-GB';

echo "<h3>Testing bulk translation detection...</h3>\n";

// Get page data from Elementor
$elementor_data = get_post_meta($page_id, '_elementor_data', true);
if (empty($elementor_data)) {
    echo "<p style='color: red;'>❌ No Elementor data found for page $page_id</p>\n";
    exit;
}

// Parse JSON data if it's a string
if (is_string($elementor_data)) {
    $elementor_data = json_decode($elementor_data, true);
}

if (!is_array($elementor_data)) {
    echo "<p style='color: red;'>❌ Invalid Elementor data format</p>\n";
    exit;
}

echo "<p style='color: blue;'>✅ Found Elementor data with " . count($elementor_data) . " top-level elements</p>\n";

// Show structure of first few elements
echo "<h3>Sample Element Structure:</h3>\n";
for ($i = 0; $i < min(3, count($elementor_data)); $i++) {
    echo "<pre style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "Element $i:\n";
    echo "elType: " . (isset($elementor_data[$i]['elType']) ? $elementor_data[$i]['elType'] : 'not set') . "\n";
    echo "widgetType: " . (isset($elementor_data[$i]['widgetType']) ? $elementor_data[$i]['widgetType'] : 'not set') . "\n";
    echo "id: " . (isset($elementor_data[$i]['id']) ? $elementor_data[$i]['id'] : 'not set') . "\n";
    if (isset($elementor_data[$i]['elements']) && is_array($elementor_data[$i]['elements'])) {
        echo "child elements: " . count($elementor_data[$i]['elements']) . "\n";
    }
    echo "</pre>\n";
}

// Use reflection to access the private method for testing
$reflection = new ReflectionClass($plugin);
$find_method = $reflection->getMethod('find_translatable_elements');
$find_method->setAccessible(true);

// Find translatable elements
$translatable_elements = $find_method->invoke($plugin, $elementor_data);

echo "<p style='color: green;'>✅ Found " . count($translatable_elements) . " translatable elements</p>\n";

if (count($translatable_elements) > 0) {
    echo "<h3>Translatable Elements Found:</h3>\n";
    foreach ($translatable_elements as $index => $element) {
        echo "<div style='background: #f9f9f9; margin: 10px 0; padding: 10px; border-radius: 5px;'>\n";
        echo "<strong>Element " . ($index + 1) . ":</strong><br>\n";
        echo "ID: " . $element['id'] . "<br>\n";
        echo "Type: " . $element['widgetType'] . "<br>\n";
        echo "Text: " . htmlspecialchars(substr($element['original_text'], 0, 100)) . "...<br>\n";
        echo "</div>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No translatable elements found</p>\n";
}

echo "<h3>Check debug.log for detailed information</h3>\n";
echo "<p><a href='javascript:history.back()'>← Back</a></p>\n";
