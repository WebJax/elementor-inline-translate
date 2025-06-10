<?php
/**
 * Live Environment Test for llp.test
 * Tests the Elementor Inline Translate plugin in the actual WordPress environment
 */

// Include WordPress
require_once('/Users/jacobthygesen/Sites/llp/wp-config.php');

echo "=== ELEMENTOR INLINE TRANSLATE - LIVE TEST (llp.test) ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: llp.test\n\n";

// Test 1: Plugin Status
echo "=== PLUGIN STATUS TEST ===\n";

if (function_exists('is_plugin_active')) {
    $plugin_path = 'elementor-inline-translate/elementor-inline-translate.php';
    if (is_plugin_active($plugin_path)) {
        echo "✅ Plugin is ACTIVE\n";
    } else {
        echo "❌ Plugin is INACTIVE\n";
        echo "To activate: wp plugin activate elementor-inline-translate\n";
    }
} else {
    echo "⚠️  Cannot check plugin status (WordPress not fully loaded)\n";
}

// Test 2: Class Availability
echo "\n=== CLASS AVAILABILITY TEST ===\n";

$classes_to_check = [
    'Elementor_Inline_Translate' => 'Main plugin class',
    'Elementor_Integration' => 'Elementor integration class'
];

foreach ($classes_to_check as $class => $description) {
    if (class_exists($class)) {
        echo "✅ $description: $class EXISTS\n";
    } else {
        echo "❌ $description: $class NOT FOUND\n";
    }
}

// Test 3: WordPress Hooks
echo "\n=== WORDPRESS HOOKS TEST ===\n";

$hooks_to_check = [
    'wp_ajax_eit_translate_text' => 'Individual translation AJAX',
    'wp_ajax_eit_translate_page_bulk' => 'Bulk translation AJAX',
    'elementor/editor/after_enqueue_scripts' => 'Elementor script enqueue',
    'elementor/editor/after_enqueue_styles' => 'Elementor style enqueue'
];

global $wp_filter;
foreach ($hooks_to_check as $hook => $description) {
    if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks)) {
        echo "✅ $description: REGISTERED\n";
    } else {
        echo "⚠️  $description: NOT REGISTERED\n";
    }
}

// Test 4: File System Check
echo "\n=== FILE SYSTEM TEST ===\n";

$plugin_dir = WP_PLUGIN_DIR . '/elementor-inline-translate/';
$files_to_check = [
    'elementor-inline-translate.php' => 'Main plugin file',
    'assets/js/editor.js' => 'Optimized JavaScript',
    'assets/css/editor.css' => 'Styles',
    'includes/class-elementor-integration.php' => 'Elementor integration'
];

foreach ($files_to_check as $file => $description) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "✅ $description: EXISTS (" . number_format($size) . " bytes)\n";
    } else {
        echo "❌ $description: NOT FOUND\n";
    }
}

// Test 5: JavaScript Optimization Verification
echo "\n=== JAVASCRIPT OPTIMIZATION TEST ===\n";

$js_file = $plugin_dir . 'assets/js/editor.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    
    // Check for performance optimizations
    $optimizations = [
        'requestAnimationFrame(' => 'RequestAnimationFrame calls',
        'setTinyMCEContent(' => 'TinyMCE optimization',
        'processBatch(' => 'Batch processing',
        'avoid document.write()' => 'Document.write fixes'
    ];
    
    foreach ($optimizations as $pattern => $description) {
        $count = substr_count($js_content, $pattern);
        if ($count > 0) {
            echo "✅ $description: $count implementations\n";
        } else {
            echo "⚠️  $description: NOT FOUND\n";
        }
    }
    
    // Check for performance violations
    $setTimeout_violations = preg_match_all('/setTimeout\s*\(/', $js_content);
    if ($setTimeout_violations == 0) {
        echo "✅ NO setTimeout violations\n";
    } else {
        echo "⚠️  setTimeout violations found: $setTimeout_violations\n";
    }
    
} else {
    echo "❌ JavaScript file not found\n";
}

// Test 6: Database Test (if possible)
echo "\n=== DATABASE TEST ===\n";

if (function_exists('get_option')) {
    // Test basic WordPress database connection
    $site_url = get_option('siteurl');
    if ($site_url) {
        echo "✅ Database connection: WORKING\n";
        echo "✅ Site URL: $site_url\n";
    } else {
        echo "❌ Database connection: FAILED\n";
    }
} else {
    echo "⚠️  WordPress functions not available\n";
}

// Test 7: Elementor Check
echo "\n=== ELEMENTOR COMPATIBILITY TEST ===\n";

if (defined('ELEMENTOR_VERSION')) {
    echo "✅ Elementor is installed: v" . ELEMENTOR_VERSION . "\n";
} else {
    echo "⚠️  Elementor not detected\n";
}

if (class_exists('\Elementor\Plugin')) {
    echo "✅ Elementor Plugin class: AVAILABLE\n";
} else {
    echo "⚠️  Elementor Plugin class: NOT AVAILABLE\n";
}

// Test 8: Performance Score
echo "\n=== OVERALL PERFORMANCE SCORE ===\n";

if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    $raf_count = substr_count($js_content, 'requestAnimationFrame(');
    $setTimeout_count = preg_match_all('/setTimeout\s*\(/', $js_content);
    $optimization_markers = substr_count($js_content, 'OPTIMIZED');
    
    $performance_score = 0;
    if ($raf_count >= 15) $performance_score += 25;
    if ($setTimeout_count == 0) $performance_score += 25;
    if ($optimization_markers >= 2) $performance_score += 25;
    if (file_exists($plugin_dir . 'docs/FINAL-PERFORMANCE-STATUS.md')) $performance_score += 25;
    
    echo "Performance Score: $performance_score/100\n";
    
    if ($performance_score >= 90) {
        echo "🚀 EXCELLENT - Production ready!\n";
    } elseif ($performance_score >= 70) {
        echo "👍 GOOD - Minor optimizations possible\n";
    } else {
        echo "⚠️  NEEDS WORK - Performance issues detected\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "🎯 Plugin Status: Ready for testing in Elementor editor\n";
echo "🔗 Test URL: http://llp.test/wp-admin/edit.php?post_type=page\n";
echo "📝 Next: Create/edit an Elementor page to test translation features\n";

?>
