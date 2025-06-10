<?php
/**
 * Quick Performance Check for Elementor Inline Translate Plugin
 */

echo "=== QUICK PERFORMANCE CHECK ===\n";

// Check if WordPress is available
if (!function_exists('get_option')) {
    echo "❌ WordPress not loaded\n";
    exit(1);
}

// Check plugin status
$plugin_file = '/Users/jacobthygesen/Sites/llp/wp-content/plugins/elementor-inline-translate/elementor-inline-translate.php';
if (file_exists($plugin_file)) {
    echo "✅ Plugin file exists\n";
} else {
    echo "❌ Plugin file not found\n";
    exit(1);
}

// Check JavaScript file
$js_file = '/Users/jacobthygesen/Sites/llp/wp-content/plugins/elementor-inline-translate/assets/js/editor.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    $js_size = strlen($js_content);
    echo "✅ JavaScript file exists ($js_size bytes)\n";
    
    // Check for key optimizations
    $optimizations = [
        'requestAnimationFrame(' => 'RequestAnimationFrame usage',
        'processBatch(' => 'Batch processing',
        'setTinyMCEContent(' => 'TinyMCE optimization',
        'avoid document.write()' => 'Document.write fixes',
        'full content length' => 'Text truncation fixes'
    ];
    
    $found_optimizations = 0;
    foreach ($optimizations as $pattern => $desc) {
        if (strpos($js_content, $pattern) !== false) {
            echo "✅ $desc: FOUND\n";
            $found_optimizations++;
        } else {
            echo "⚠️  $desc: NOT FOUND\n";
        }
    }
    
    // Check for performance violations
    $setTimeout_actual = preg_match_all('/setTimeout\s*\(/', $js_content);
    $setInterval_actual = preg_match_all('/setInterval\s*\(/', $js_content);
    
    echo "\n=== PERFORMANCE VIOLATIONS CHECK ===\n";
    echo "setTimeout actual calls: $setTimeout_actual\n";
    echo "setInterval actual calls: $setInterval_actual\n";
    
    if ($setTimeout_actual == 0 && $setInterval_actual == 0) {
        echo "✅ NO PERFORMANCE VIOLATIONS FOUND\n";
    } else {
        echo "⚠️  Performance violations detected\n";
    }
    
    // Count requestAnimationFrame usage
    $raf_count = substr_count($js_content, 'requestAnimationFrame(');
    echo "requestAnimationFrame usage: $raf_count calls\n";
    
    echo "\n=== OPTIMIZATION SCORE ===\n";
    $score = ($found_optimizations / count($optimizations)) * 100;
    echo "Optimization score: " . round($score, 1) . "%\n";
    
    if ($score >= 80) {
        echo "🚀 EXCELLENT - Plugin is well optimized\n";
    } elseif ($score >= 60) {
        echo "👍 GOOD - Plugin has most optimizations\n";
    } else {
        echo "⚠️  NEEDS WORK - Missing critical optimizations\n";
    }
    
} else {
    echo "❌ JavaScript file not found\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
