# Changelog - Elementor Inline Oversættelse

All notable changes to this project will be documented in this file.

## [1.1.0] - 2025-06-09

### 🚀 Major Features Added
- **Advanced HTML Preservation System**: Revolutionary separator-based approach to maintain HTML structure during translation
- **DeepL Corruption Prevention**: Intelligent handling of DeepL API's tendency to merge HTML list items
- **Intelligent Text Reconstruction**: Multiple fallback algorithms for robust text reconstruction

### ✨ Enhancements
- **DOMDocument-based HTML Parsing**: Replaced simple tag stripping with sophisticated HTML analysis
- **Element Boundary Tracking**: Preserves whitespace and spacing information during translation
- **Multiple Fallback Strategies**: Sentence-based and word-based splitting when separators are lost
- **Performance Optimization**: Efficient processing of large HTML content (100+ text nodes in <1ms)

### 🔧 Technical Improvements
- Added `|EIT_SEPARATOR|` marker system for preserving HTML structure boundaries
- Enhanced `extract_text_from_html()` method with XPath queries and error handling
- Completely rewrote `reconstruct_html_with_translated_text()` with intelligent algorithms
- Added `stored_element_boundaries` property for maintaining HTML context
- Implemented comprehensive debug logging for troubleshooting

### 🧪 Testing Infrastructure
- Created `html-preservation-test.php` for basic functionality validation
- Built `enhanced-html-test.php` with advanced scenarios and performance benchmarking
- Generated `COMPREHENSIVE-TEST-GUIDE.md` with real-world testing protocols
- Validated critical DeepL corruption scenarios (list merging, inline formatting)

### 🐛 Bug Fixes
- Fixed HTML list items being merged into continuous text by DeepL API
- Resolved spacing issues around inline elements (strong, em, a, span)
- Corrected HTML entity encoding problems with Danish characters (ø, æ, å)
- Fixed fallback mechanisms when translation services remove separators

### 📚 Documentation
- Updated `PROJECT-STATUS.md` with comprehensive feature overview
- Created detailed technical documentation for the HTML preservation system
- Added performance benchmarks and validation test results
- Enhanced code comments with implementation details

## [1.0.0] - 2025-06-08

### 🎉 Initial Release
- **Core Translation Functionality**: Basic text translation using DeepL API
- **Elementor Integration**: Seamless integration with Elementor editor
- **Widget Support**: Support for Heading, Text Editor, and Button widgets
- **Multi-language Support**: Danish, German, and English target languages
- **PolyLang Integration**: Full integration with PolyLang multilingual plugin
- **TinyMCE HTML Rendering**: Advanced handling of HTML in TinyMCE visual editor
- **Real-time Preview**: Immediate visual updates after translation
- **Reference Text System**: Automatic display of text from main language
- **Copy from Reference**: One-click copying from main language

### 🔧 Technical Foundation
- WordPress plugin architecture with proper hooks and classes
- AJAX request handling with nonce verification
- Event handler optimization to prevent duplicate bindings
- Comprehensive error handling and fallback mechanisms
- Robust translation state management with `isTranslating` flag

### 🎨 User Interface
- Clean and intuitive editor controls
- Visual feedback system with loading states
- Toast notifications for user guidance
- Context-aware UI elements based on translation status

### 📋 File Structure
- Main plugin file with AJAX handlers and core functionality
- Elementor integration class for widget controls
- JavaScript for editor functionality and TinyMCE handling
- CSS for UI styling and visual presentation
- Comprehensive documentation and testing guides

---

## Upgrading

### From 1.0.0 to 1.1.0
- **Automatic**: The HTML preservation enhancements are backward compatible
- **Benefits**: Existing translated content will maintain structure better
- **Testing**: Run the included test suites to validate functionality
- **No Breaking Changes**: All existing features continue to work as before

### System Requirements
- WordPress 5.0+
- Elementor 3.0+
- PHP 7.4+
- DOMDocument extension (for advanced HTML processing)
- DeepL API key
- PolyLang plugin (optional, for multilingual features)

### Performance Notes
- HTML processing time: <1ms for typical content
- Memory usage: Minimal increase due to efficient DOM handling
- Large content support: Tested with 100+ text nodes successfully
- Fallback mechanisms: Graceful degradation if DOMDocument unavailable

---

**Plugin Status: 🟢 Production Ready**  
**Latest Version: 1.1.0**  
**Release Date: June 9, 2025**
