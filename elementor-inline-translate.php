<?php
/**
 * Plugin Name: Elementor Inline Oversættelse
 * Description: Et simpelt plugin til at demonstrere inline oversættelse i Elementor editoren med bulk oversættelse.
 * Version: 1.2.0
 * Author: Jaxweb
 * Text Domain: elementor-inline-translate
 * Domain Path: /languages
 * Elementor tested up to: 3.29.0
 * Elementor Pro tested up to: 3.29.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EIT_PLUGIN_VERSION', '1.2.0' );
define( 'EIT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'EIT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Hovedklassen for Elementor Inline Oversættelse.
 */
final class Elementor_Inline_Translate {

    /**
     * Plugin version.
     *
     * @since 0.1.0
     * @var string
     */
    const VERSION = EIT_PLUGIN_VERSION;

    /**
     * Minimum Elementor version påkrævet.
     *
     * @since 0.1.0
     * @var string
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.5.0';

    /**
     * Minimum PHP version påkrævet.
     *
     * @since 0.1.0
     * @var string
     */
    const MINIMUM_PHP_VERSION = '7.4';

    /**
     * Instans af klassen.
     *
     * @since 0.1.0
     * @access private
     * @static
     * @var Elementor_Inline_Translate
     */
    private static $_instance = null;

    /**
     * Gemmer element boundaries for HTML rekonstruktion.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $stored_element_boundaries = array();

    /**
     * Sikrer kun én instans af klassen.
     *
     * @since 0.1.0
     * @access public
     * @static
     * @return Elementor_Inline_Translate Instans.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Konstruktør.
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Initialiserer pluginet.
     *
     * Tjekker om Elementor er installeret og aktiv.
     *
     * @since 0.1.0
     * @access public
     */
    public function init() {
        // Tjek om Elementor er aktiv
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
            return;
        }

        // Tjek for påkrævet Elementor version
        if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
            return;
        }

        // Tjek for påkrævet PHP version
        if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
            return;
        }

        // Inkluder filer
        $this->includes();

        // Tilføj actions
        add_action( 'elementor/init', [ $this, 'register_controls' ] );
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
        add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_editor_scripts_admin' ] );
        
        // Debug hooks
        add_action( 'elementor/editor/init', function() {
            error_log('EIT Debug: elementor/editor/init hook fired');
        });
        add_action( 'elementor/loaded', function() {
            error_log('EIT Debug: elementor/loaded hook fired');
        });

        // AJAX handlers
        add_action( 'wp_ajax_eit_translate_text', [ $this, 'handle_translate_text_ajax' ] );
        add_action( 'wp_ajax_eit_get_reference_text', [ $this, 'handle_get_reference_text_ajax' ] );
        add_action( 'wp_ajax_eit_translate_page_bulk', [ $this, 'handle_translate_page_bulk_ajax' ] );
    }

    /**
     * Inkluderer påkrævede filer.
     *
     * @since 0.1.0
     * @access private
     */
    private function includes() {
        require_once EIT_PLUGIN_PATH . 'includes/class-elementor-integration.php';
    }

    /**
     * Registrerer custom controls (hvis nødvendigt - i dette eksempel tilføjer vi til eksisterende widgets).
     *
     * @param \Elementor\Controls_Manager $controls_manager
     * @since 0.1.0
     * @access public
     */
    public function register_controls( $controls_manager ) {
        // Her kan du registrere custom controls, hvis du bygger helt nye controls.
        // For dette eksempel vil vi tilføje en knap til eksisterende widgets via Elementor_Integration klassen.
        error_log('EIT Debug: register_controls called');
        new Elementor_Inline_Translate_Integration();
    }

    /**
     * Enqueue scripts til Elementor editoren.
     *
     * @since 0.1.0
     * @access public
     */
    public function enqueue_editor_scripts() {
        error_log('EIT Debug: enqueue_editor_scripts called');
        
        // Enqueue CSS
        wp_enqueue_style(
            'eit-editor-style',
            EIT_PLUGIN_URL . 'assets/css/editor.css',
            [],
            self::VERSION
        );
        
        wp_enqueue_script(
            'eit-editor-script',
            EIT_PLUGIN_URL . 'assets/js/editor.js',
            [ 'elementor-editor', 'jquery' ], // Sørg for at elementor-editor er en afhængighed
            self::VERSION,
            true // Indlæs i footer
        );

        // Gør AJAX URL og nonce tilgængelig for JavaScript
        wp_localize_script(
            'eit-editor-script',
            'eit_vars', // Ændret til eit_vars for konsistens med JavaScript
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'eit_translate_nonce' ),
                'is_polylang_active' => $this->is_polylang_active(),
                'is_translation' => $this->is_current_page_translation(),
                'default_language' => $this->get_default_language(),
                'current_language' => $this->get_current_language(),
                'current_post_id' => get_the_ID(),
                // Du kan tilføje oversættelige strenge her, hvis nødvendigt
                'i18n'     => [
                    'translateButton' => __( 'Oversæt Tekst', 'elementor-inline-translate' ),
                    'selectLanguage'  => __( 'Vælg Sprog:', 'elementor-inline-translate' ),
                    'translating'     => __( 'Oversætter...', 'elementor-inline-translate' ),
                    'error'           => __( 'Fejl under oversættelse.', 'elementor-inline-translate' ),
                    'referenceText'   => __( 'Reference (hovedsprog):', 'elementor-inline-translate' ),
                    'copyFromReference' => __( 'Kopier fra hovedsprog', 'elementor-inline-translate' ),
                ]
            ]
        );
        
        // Tilføj meta tag for at undertrykke TinyMCE document.write advarsel
        add_action( 'admin_head', function() {
            echo '<meta http-equiv="Content-Security-Policy" content="\'unsafe-inline\'">';
        });
        
        error_log('EIT Debug: Script enqueued with URL: ' . EIT_PLUGIN_URL . 'assets/js/editor.js');
    }

    /**
     * Enqueue scripts til admin sider (backup method).
     *
     * @since 0.1.0
     * @access public
     */
    public function enqueue_editor_scripts_admin($hook) {
        error_log('EIT Debug: enqueue_editor_scripts_admin called on: ' . $hook . ' with GET: ' . print_r($_GET, true));
        
        // Tjek om vi er på en Elementor editor side
        if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'elementor' ) {
            return;
        }
        
        error_log('EIT Debug: Enqueuing script for Elementor editor');
        $this->enqueue_editor_scripts();
    }

    /**
     * Håndterer AJAX anmodning for oversættelse.
     *
     * @since 0.1.0
     * @access public
     */
    public function handle_translate_text_ajax() {
        error_log('EIT Debug: handle_translate_text_ajax called');
        error_log('EIT Debug: POST data: ' . print_r($_POST, true));
        
        // Sikkerhedstjek (nonce)
        check_ajax_referer( 'eit_translate_nonce', 'nonce' );

        // Hent data fra AJAX anmodning
        $text_to_translate = isset( $_POST['text'] ) ? wp_unslash( $_POST['text'] ) : '';
        $target_language   = isset( $_POST['target_lang'] ) ? sanitize_text_field( $_POST['target_lang'] ) : 'EN-US'; // Standard til engelsk
        $element_id        = isset( $_POST['element_id'] ) ? sanitize_text_field( $_POST['element_id'] ) : '';
        $control_name      = isset( $_POST['control_name'] ) ? sanitize_text_field( $_POST['control_name'] ) : '';

        if ( empty( $text_to_translate ) || empty( $target_language ) || empty($element_id) || empty($control_name) ) {
            wp_send_json_error( [ 'message' => __( 'Manglende data til oversættelse. text_to_translate: ' . $text_to_translate . ' target_language: ' . $target_language . ' element_id: ' . $element_id . ' control_name: ' . $control_name, 'elementor-inline-translate' ) ] );
            return;
        }

        // Definer din DeepL API nøgle her.
        // **VIGTIGT**: Udskift 'DIN_DEEPL_API_NØGLE' med din faktiske DeepL API nøgle.
        // For bedre sikkerhed i et rigtigt plugin, bør denne nøgle gemmes i WordPress' indstillinger
        // og ikke hardcodes direkte i koden.
        if ( ! defined( 'EIT_DEEPL_API_KEY' ) ) {
            define( 'EIT_DEEPL_API_KEY', '5b2070a2-59bc-4902-b009-c9ef94f845b5:fx' ); // ERSTAT MED DIN RIGTIGE NØGLE
        }

        $api_key = EIT_DEEPL_API_KEY;

        if ( empty( $api_key ) || $api_key === 'DIN_DEEPL_API_NØGLE' ) {
            wp_send_json_error( [ 'message' => __( 'DeepL API nøgle er ikke konfigureret korrekt.', 'elementor-inline-translate' ) ] );
            return;
        }

        // Intelligent håndtering af HTML indhold
        $original_html = $text_to_translate;
        $text_for_translation = $text_to_translate;
        $is_html_content = false;
        
        // Tjek om indholdet indeholder HTML tags (typisk for text-editor widgets)
        if ( $control_name === 'editor' && ( strpos( $text_to_translate, '<' ) !== false || strpos( $text_to_translate, '&' ) !== false ) ) {
            $is_html_content = true;
            error_log('EIT Debug: Detected HTML content, preserving structure');
            
            // Ekstrahér ren tekst fra HTML for oversættelse
            $text_for_translation = $this->extract_text_from_html( $text_to_translate );
            error_log('EIT Debug: Extracted text for translation: ' . $text_for_translation);
        }

        // DeepL API URL 
        // Hvis du bruger DeepL Pro, kan du ændre URL'en til 'https://api.deepl.com/v2/translate'
        // Hvis du bruger gratis versionen, skal du bruge 'https://api-free.deepl.com/v2/translate'
        // For dette eksempel bruger vi gratis versionen.
        $api_url = 'https://api-free.deepl.com/v2/translate';

        $response = wp_remote_post( $api_url, [
            'method'    => 'POST',
            'headers'   => [
            'Authorization' => 'DeepL-Auth-Key ' . $api_key,
            'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'      => [
            'text'        => $text_for_translation, // Brug den ekstraherede tekst
            'target_lang' => $target_language,
            // 'source_lang' => 'DA', // Valgfrit: Angiv kildesprog hvis nødvendigt
            ],
            'timeout'   => 30, // Sæt en passende timeout
        ]);

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => __( 'Fejl ved kommunikation med DeepL API: ', 'elementor-inline-translate' ) . $response->get_error_message() ] );
            return;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( wp_remote_retrieve_response_code( $response ) !== 200 || ! isset( $data['translations'][0]['text'] ) ) {
            $error_message = isset($data['message']) ? $data['message'] : __( 'Ukendt fejl fra DeepL API.', 'elementor-inline-translate' );
            if (wp_remote_retrieve_response_code( $response ) === 403) {
            $error_message = __( 'DeepL API-godkendelse mislykkedes. Tjek din API-nøgle.', 'elementor-inline-translate' );
            } elseif (wp_remote_retrieve_response_code( $response ) === 456) {
            $error_message = __( 'DeepL API kvote overskredet. Tjek din DeepL konto.', 'elementor-inline-translate' );
            }
            wp_send_json_error( [ 'message' => $error_message, 'deepl_response' => $data ] );
            return;
        }

        $translated_text = $data['translations'][0]['text'];

        // Hvis det var HTML indhold, rekonstruer HTML strukturen med oversat tekst
        if ( $is_html_content ) {
            $translated_text = $this->reconstruct_html_with_translated_text( $original_html, $text_for_translation, $translated_text );
            error_log('EIT Debug: Reconstructed HTML with translated text: ' . $translated_text);
        }

        if ( $translated_text ) {
            wp_send_json_success( [
                'translated_text' => $translated_text,
                'element_id'      => $element_id,
                'control_name'    => $control_name
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Kunne ikke oversætte teksten.', 'elementor-inline-translate' ) ] );
        }
    }

    /**
     * Tjekker om PolyLang er aktivt.
     *
     * @return bool
     */
    public function is_polylang_active() {
        return function_exists('pll_default_language') && function_exists('pll_get_post');
    }

    /**
     * Henter standardsproget fra PolyLang.
     *
     * @return string|false
     */
    public function get_default_language() {
        if ( ! $this->is_polylang_active() ) {
            return false;
        }
        return pll_default_language();
    }

    /**
     * Henter det aktuelle sprog.
     *
     * @return string|false
     */
    public function get_current_language() {
        if ( ! $this->is_polylang_active() ) {
            return false;
        }
        return pll_current_language();
    }

    /**
     * Tjekker om den aktuelle side er en oversættelse (ikke hovedsproget).
     *
     * @return bool
     */
    public function is_current_page_translation() {
        if ( ! $this->is_polylang_active() ) {
            return false;
        }
        
        $current_lang = $this->get_current_language();
        $default_lang = $this->get_default_language();
        
        return $current_lang && $default_lang && $current_lang !== $default_lang;
    }

    /**
     * Finder hovedsprogets version af den aktuelle side.
     *
     * @param int $post_id Nuværende post ID.
     * @return int|false Post ID for hovedsproget, eller false hvis ikke fundet.
     */
    public function get_default_language_post_id( $post_id ) {
        if ( ! $this->is_polylang_active() ) {
            return false;
        }
        
        $default_lang = $this->get_default_language();
        if ( ! $default_lang ) {
            return false;
        }
        
        return pll_get_post( $post_id, $default_lang );
    }

    /**
     * Henter reference tekst fra hovedsprogets version af elementet.
     *
     * @since 1.0.0
     * @access public
     */
    public function handle_get_reference_text_ajax() {
        error_log('EIT Debug: handle_get_reference_text_ajax called');
        
        // Sikkerhedstjek (nonce)
        check_ajax_referer( 'eit_translate_nonce', 'nonce' );

        // Hent data fra AJAX anmodning
        $element_id = isset( $_POST['element_id'] ) ? sanitize_text_field( $_POST['element_id'] ) : '';
        $control_name = isset( $_POST['control_name'] ) ? sanitize_text_field( $_POST['control_name'] ) : '';
        
        // Få det nuværende post ID fra den globale variabel eller URL parameter
        $current_post_id = 0;
        if ( isset( $_POST['post_id'] ) && intval( $_POST['post_id'] ) > 0 ) {
            $current_post_id = intval( $_POST['post_id'] );
        } elseif ( isset( $_GET['post'] ) && intval( $_GET['post'] ) > 0 ) {
            $current_post_id = intval( $_GET['post'] );
        } elseif ( defined( 'ELEMENTOR_EDITING_MODE' ) && isset( $GLOBALS['post'] ) ) {
            $current_post_id = $GLOBALS['post']->ID;
        }

        if ( empty( $element_id ) || empty( $control_name ) || empty( $current_post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Manglende data til reference forespørgsel. Element ID: ' . $element_id . ', Control: ' . $control_name . ', Post ID: ' . $current_post_id, 'elementor-inline-translate' ) ] );
            return;
        }

        // Tjek om PolyLang er aktivt
        if ( ! $this->is_polylang_active() ) {
            wp_send_json_error( [ 'message' => __( 'PolyLang er ikke aktivt.', 'elementor-inline-translate' ) ] );
            return;
        }

        // Find hovedsprogets version af siden
        $default_post_id = $this->get_default_language_post_id( $current_post_id );
        if ( ! $default_post_id ) {
            wp_send_json_error( [ 'message' => __( 'Kunne ikke finde hovedsprogets version af siden.', 'elementor-inline-translate' ) ] );
            return;
        }

        error_log('EIT Debug: Default language post ID: ' . $default_post_id);

        // Hent Elementor data fra hovedsprogets side
        $default_elementor_data = get_post_meta( $default_post_id, '_elementor_data', true );
        if ( empty( $default_elementor_data ) ) {
            wp_send_json_error( [ 'message' => __( 'Kunne ikke finde Elementor data for hovedsproget.', 'elementor-inline-translate' ) ] );
            return;
        }

        // Parse Elementor data
        if ( is_string( $default_elementor_data ) ) {
            $default_elementor_data = json_decode( $default_elementor_data, true );
        }

        if ( ! is_array( $default_elementor_data ) ) {
            wp_send_json_error( [ 'message' => __( 'Ugyldig Elementor data format.', 'elementor-inline-translate' ) ] );
            return;
        }

        // Find elementet med det matchende ID
        $reference_text = $this->find_element_text_by_id( $default_elementor_data, $element_id, $control_name );
        
        if ( $reference_text === false ) {
            wp_send_json_error( [ 'message' => __( 'Kunne ikke finde reference tekst for elementet.', 'elementor-inline-translate' ) ] );
            return;
        }

        wp_send_json_success( [
            'reference_text' => $reference_text,
            'element_id' => $element_id,
            'control_name' => $control_name,
            'default_language' => $this->get_default_language(),
            'default_post_id' => $default_post_id
        ] );
    }

    /**
     * Rekursivt søger gennem Elementor data for at finde et element med specifikt ID.
     *
     * @param array $elements Elementor data struktur.
     * @param string $target_id Det element ID vi leder efter.
     * @param string $control_name Det kontrolfelt navn vi vil hente tekst fra.
     * @return string|false Reference teksten eller false hvis ikke fundet.
     */
    private function find_element_text_by_id( $elements, $target_id, $control_name ) {
        if ( ! is_array( $elements ) ) {
            return false;
        }

        foreach ( $elements as $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }

            // Tjek om dette element har det rigtige ID
            if ( isset( $element['id'] ) && $element['id'] === $target_id ) {
                // Find teksten i elementets settings
                if ( isset( $element['settings'][ $control_name ] ) ) {
                    error_log('EIT Debug: Found reference text: ' . $element['settings'][ $control_name ]);
                    return $element['settings'][ $control_name ];
                }
            }

            // Søg rekursivt i børne-elementer
            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $result = $this->find_element_text_by_id( $element['elements'], $target_id, $control_name );
                if ( $result !== false ) {
                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * Ekstraherer tekst fra HTML til oversættelse mens strukturen bevares.
     * I stedet for at fjerne alle tags, parser vi HTML og oversætter element for element.
     *
     * @param string $html HTML indhold.
     * @return string Ren tekst til oversættelse.
     */
    private function extract_text_from_html( $html ) {
        // Hvis DOMDocument ikke er tilgængelig, fallback til simpel text extraction
        if ( ! class_exists( 'DOMDocument' ) ) {
            $text = wp_strip_all_tags( $html );
            $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            return trim( $text );
        }

        // Parse HTML og saml tekst fra individuelle elementer
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        
        // Undgå warnings for dårligt formateret HTML
        libxml_use_internal_errors( true );
        
        // Load HTML med UTF-8 encoding
        $success = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        
        if ( ! $success ) {
            // Fallback hvis HTML parsing fejler
            $text = wp_strip_all_tags( $html );
            $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            return trim( $text );
        }
        
        // Find alle text nodes og sammel dem med separatorer for at bevare struktur
        $xpath = new DOMXPath( $dom );
        $textNodes = $xpath->query( '//text()[normalize-space(.) != ""]' );
        
        $text_parts = array();
        $element_boundaries = array(); // For at tracke hvor hvert element starter/slutter
        
        foreach ( $textNodes as $index => $textNode ) {
            $content = trim( $textNode->textContent );
            if ( ! empty( $content ) ) {
                $parent_tag = $textNode->parentNode->nodeName;
                
                // Preserve whitespace information for reconstruction
                $prev_sibling = $textNode->previousSibling;
                $next_sibling = $textNode->nextSibling;
                $has_space_before = ( $prev_sibling && $prev_sibling->nodeType === XML_TEXT_NODE && preg_match('/\s$/', $prev_sibling->textContent ) );
                $has_space_after = ( $next_sibling && $next_sibling->nodeType === XML_TEXT_NODE && preg_match('/^\s/', $next_sibling->textContent ) );
                
                $text_parts[] = $content;
                $element_boundaries[] = array(
                    'text' => $content,
                    'parent' => $parent_tag,
                    'index' => $index,
                    'has_space_before' => $has_space_before,
                    'has_space_after' => $has_space_after,
                    'original_node' => $textNode
                );
            }
        }
        
        // Join med special separatorer så vi kan splitte dem igen efter oversættelse
        $combined_text = implode( ' |EIT_SEPARATOR| ', $text_parts );
        
        // Store element boundaries til rekonstruktion
        $this->stored_element_boundaries = $element_boundaries;
        
        error_log('EIT Debug: Extracted ' . count($text_parts) . ' text parts with separators');
        return $combined_text;
    }

    /**
     * Rekonstruerer HTML med oversat tekst.
     * Bruger element boundaries og separatorer til at bevare HTML struktur.
     *
     * @param string $original_html Original HTML struktur.
     * @param string $original_text Original ren tekst (med separatorer).
     * @param string $translated_text Oversat tekst.
     * @return string HTML med oversat indhold.
     */
    private function reconstruct_html_with_translated_text( $original_html, $original_text, $translated_text ) {
        error_log('EIT Debug: Reconstruct - Original text: ' . $original_text);
        error_log('EIT Debug: Reconstruct - Translated text: ' . $translated_text);
        
        // Tjek om vi har separatorer i den originale tekst
        if ( strpos( $original_text, '|EIT_SEPARATOR|' ) !== false && ! empty( $this->stored_element_boundaries ) ) {
            error_log('EIT Debug: Using separator-based reconstruction');
            
            // Split den oversatte tekst på de samme separatorer
            $translated_parts = array();
            $original_parts = explode( '|EIT_SEPARATOR|', $original_text );
            $expected_count = count( $original_parts );
            
            // Først prøv eksakt separator match
            if ( strpos( $translated_text, '|EIT_SEPARATOR|' ) !== false ) {
                $translated_parts = explode( '|EIT_SEPARATOR|', $translated_text );
                error_log('EIT Debug: Found exact separators in translation');
            } 
            // Hvis separatorerne er væk, prøv intelligent splitting
            else {
                error_log('EIT Debug: Separators missing, using intelligent splitting');
                
                // Method 1: Try splitting on sentence boundaries
                if ( $expected_count > 1 ) {
                    $translated_parts = preg_split('/(?<=[.!?])\s+/', $translated_text, $expected_count);
                    
                    // Method 2: If sentence splitting doesn't work, try word-based estimation
                    if ( count( $translated_parts ) !== $expected_count ) {
                        $words = explode( ' ', $translated_text );
                        $words_per_part = max( 1, floor( count( $words ) / $expected_count ) );
                        
                        $translated_parts = array();
                        for ( $i = 0; $i < $expected_count; $i++ ) {
                            $start = $i * $words_per_part;
                            if ( $i === $expected_count - 1 ) {
                                // Last part gets remaining words
                                $part_words = array_slice( $words, $start );
                            } else {
                                $part_words = array_slice( $words, $start, $words_per_part );
                            }
                            $translated_parts[] = implode( ' ', $part_words );
                        }
                        error_log('EIT Debug: Used word-based splitting into ' . count($translated_parts) . ' parts');
                    }
                } else {
                    $translated_parts = array( $translated_text );
                }
            }
            
            // Ensure we have the right number of parts
            if ( count( $translated_parts ) !== $expected_count ) {
                error_log('EIT Debug: Part count mismatch, using fallback approach');
                
                // If we have too few parts, pad with empty strings
                while ( count( $translated_parts ) < $expected_count ) {
                    $translated_parts[] = '';
                }
                
                // If we have too many parts, merge the last ones
                while ( count( $translated_parts ) > $expected_count ) {
                    $last = array_pop( $translated_parts );
                    $translated_parts[ count( $translated_parts ) - 1 ] .= ' ' . $last;
                }
            }
            
            error_log('EIT Debug: Split translation into ' . count($translated_parts) . ' parts (expected ' . $expected_count . ')');
            
            // Brug DOMDocument til at rekonstruere HTML med de oversatte dele
            if ( class_exists( 'DOMDocument' ) && ! empty( $translated_parts ) ) {
                $dom = new DOMDocument();
                $dom->encoding = 'UTF-8';
                
                // Undgå warnings for dårligt formateret HTML
                libxml_use_internal_errors( true );
                
                // Load HTML med UTF-8 encoding
                $success = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $original_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
                
                if ( $success ) {
                    // Find alle text nodes
                    $xpath = new DOMXPath( $dom );
                    $textNodes = $xpath->query( '//text()[normalize-space(.) != ""]' );
                    
                    // Erstat hver text node med den tilsvarende oversatte del
                    $part_index = 0;
                    foreach ( $textNodes as $textNode ) {
                        if ( $part_index < count( $translated_parts ) ) {
                            $translated_part = trim( $translated_parts[ $part_index ] );
                            if ( ! empty( $translated_part ) ) {
                                // Preserve spacing based on stored boundaries
                                if ( isset( $this->stored_element_boundaries[ $part_index ] ) ) {
                                    $boundary = $this->stored_element_boundaries[ $part_index ];
                                    
                                    // Add appropriate spacing
                                    $spacing_before = $boundary['has_space_before'] ? ' ' : '';
                                    $spacing_after = $boundary['has_space_after'] ? ' ' : '';
                                    
                                    // For inline elements, we typically need space around them
                                    if ( in_array( $boundary['parent'], array( 'strong', 'em', 'b', 'i', 'a', 'span' ) ) ) {
                                        $final_text = $spacing_before . $translated_part . $spacing_after;
                                    } else {
                                        $final_text = $translated_part;
                                    }
                                    
                                    $textNode->textContent = $final_text;
                                } else {
                                    $textNode->textContent = $translated_part;
                                }
                                error_log('EIT Debug: Replaced text node with: ' . $translated_part);
                            }
                            $part_index++;
                        }
                    }
                    
                    $result = $dom->saveHTML();
                    
                    // Ryd op XML-erklæringen
                    $result = preg_replace( '/^<\?xml[^>]*\?>/', '', $result );
                    
                    // Clean up extra whitespace that might have been introduced
                    $result = preg_replace( '/\s+/', ' ', $result );
                    $result = preg_replace( '/>\s+</', '><', $result );
                    
                    error_log('EIT Debug: Successfully reconstructed HTML: ' . $result);
                    return $result;
                }
            }
        }
        
        // Fallback til den gamle metode hvis separator-baseret rekonstruktion fejler
        error_log('EIT Debug: Using fallback reconstruction method');
        
        // Simpel direkte erstatning
        if ( strpos( $original_html, $original_text ) !== false ) {
            return str_replace( $original_text, $translated_text, $original_html );
        }
        
        // Avanceret DOM-baseret erstatning
        if ( class_exists( 'DOMDocument' ) ) {
            $dom = new DOMDocument();
            $dom->encoding = 'UTF-8';
            
            // Undgå warnings for dårligt formateret HTML
            libxml_use_internal_errors( true );
            
            // Load HTML med UTF-8 encoding
            $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $original_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
            
            // Find alle text nodes og erstat indholdet
            $xpath = new DOMXPath( $dom );
            $textNodes = $xpath->query( '//text()[normalize-space(.) != ""]' );
            
            // Sammensæt tekst for sammenligning
            $combined_original = '';
            foreach ( $textNodes as $textNode ) {
                $combined_original .= trim( $textNode->textContent ) . ' ';
            }
            $combined_original = trim( $combined_original );
            
            // Fjern separatorer fra original tekst for sammenligning
            $clean_original = str_replace( ' |EIT_SEPARATOR| ', ' ', $original_text );
            
            // Hvis teksterne matcher nogenlunde
            if ( $combined_original === $clean_original || similar_text( $combined_original, $clean_original ) > 0.7 ) {
                // Erstat den første text node med den oversatte tekst
                if ( $textNodes->length > 0 ) {
                    $firstTextNode = $textNodes->item( 0 );
                    $firstTextNode->textContent = $translated_text;
                    
                    // Fjern alle andre text nodes (undtagen den første)
                    for ( $i = 1; $i < $textNodes->length; $i++ ) {
                        $textNode = $textNodes->item( $i );
                        if ( $textNode->parentNode ) {
                            $textNode->parentNode->removeChild( $textNode );
                        }
                    }
                }
                
                $result = $dom->saveHTML();
                
                // Ryd op XML-erklæringen
                $result = preg_replace( '/^<\?xml[^>]*\?>/', '', $result );
                
                return $result;
            }
        }
        
        // Som sidste udvej, returner bare den oversatte tekst
        error_log('EIT Debug: All reconstruction methods failed, returning plain text');
        return $translated_text;
    }

    /**
     * Handle bulk translation AJAX request
     *
     * @since 1.2.0
     * @access public
     */
    public function handle_translate_page_bulk_ajax() {
        // Verify nonce for security
        if ( ! wp_verify_nonce( $_POST['nonce'], 'eit_translate_nonce' ) ) {
            wp_die( 'Nonce verification failed' );
        }

        // Check required parameters
        if ( ! isset( $_POST['page_id'] ) || ! isset( $_POST['target_language'] ) ) {
            wp_send_json_error( 'Missing required parameters' );
        }

        $page_id = intval( $_POST['page_id'] );
        $target_language = sanitize_text_field( $_POST['target_language'] );
        
        error_log('EIT Debug: Starting bulk translation for page ' . $page_id . ' to ' . $target_language);

        // Get page data from Elementor
        $elementor_data = get_post_meta( $page_id, '_elementor_data', true );
        if ( empty( $elementor_data ) ) {
            wp_send_json_error( 'No Elementor data found for this page' );
        }

        // Parse JSON data if it's a string
        if ( is_string( $elementor_data ) ) {
            $elementor_data = json_decode( $elementor_data, true );
        }

        if ( ! is_array( $elementor_data ) ) {
            wp_send_json_error( 'Invalid Elementor data format' );
        }

        // Find all translatable elements
        $translatable_elements = $this->find_translatable_elements( $elementor_data );
        error_log('EIT Debug: Found ' . count( $translatable_elements ) . ' translatable elements');

        $results = [];
        $success_count = 0;
        $error_count = 0;

        // Translate each element
        foreach ( $translatable_elements as $element ) {
            try {
                $translated_element = $this->translate_single_element( $element, $target_language );
                if ( $translated_element ) {
                    $results[] = [
                        'id' => $element['id'],
                        'type' => $element['widgetType'],
                        'original' => $element['original_text'],
                        'translated' => $translated_element['translated_text'],
                        'success' => true
                    ];
                    $success_count++;
                } else {
                    $results[] = [
                        'id' => $element['id'],
                        'type' => $element['widgetType'],
                        'original' => $element['original_text'],
                        'error' => 'Translation failed',
                        'success' => false
                    ];
                    $error_count++;
                }
            } catch ( Exception $e ) {
                error_log('EIT Debug: Translation error for element ' . $element['id'] . ': ' . $e->getMessage());
                $results[] = [
                    'id' => $element['id'],
                    'type' => $element['widgetType'],
                    'original' => $element['original_text'],
                    'error' => $e->getMessage(),
                    'success' => false
                ];
                $error_count++;
            }
        }

        // Return comprehensive results
        wp_send_json_success( [
            'total_elements' => count( $translatable_elements ),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results,
            'target_language' => $target_language
        ] );
    }

    /**
     * Recursively find all translatable elements in Elementor data
     *
     * @param array $elements The Elementor data structure
     * @return array Array of translatable elements
     * @since 1.2.0
     * @access private
     */
    private function find_translatable_elements( $elements ) {
        $translatable = [];

        foreach ( $elements as $element ) {
            // Check if this element is translatable
            if ( isset( $element['widgetType'] ) && in_array( $element['widgetType'], [ 'heading', 'text-editor', 'button' ] ) ) {
                $text_content = $this->extract_text_from_element( $element );
                if ( ! empty( $text_content ) ) {
                    $translatable[] = [
                        'id' => $element['id'],
                        'widgetType' => $element['widgetType'],
                        'original_text' => $text_content,
                        'element_data' => $element
                    ];
                }
            }

            // Recursively check child elements
            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $child_translatable = $this->find_translatable_elements( $element['elements'] );
                $translatable = array_merge( $translatable, $child_translatable );
            }
        }

        return $translatable;
    }

    /**
     * Extract text content from an element based on its widget type
     *
     * @param array $element The element data
     * @return string The extracted text content
     * @since 1.2.0
     * @access private
     */
    private function extract_text_from_element( $element ) {
        $widget_type = $element['widgetType'];
        $settings = isset( $element['settings'] ) ? $element['settings'] : [];

        switch ( $widget_type ) {
            case 'heading':
                return isset( $settings['title'] ) ? $settings['title'] : '';
            
            case 'text-editor':
                return isset( $settings['editor'] ) ? wp_strip_all_tags( $settings['editor'] ) : '';
            
            case 'button':
                return isset( $settings['text'] ) ? $settings['text'] : '';
            
            default:
                return '';
        }
    }

    /**
     * Translate a single element using the existing translation logic
     *
     * @param array $element The element to translate
     * @param string $target_language The target language code
     * @return array|false The translation result or false on failure
     * @since 1.2.0
     * @access private
     */
    private function translate_single_element( $element, $target_language ) {
        $original_text = $element['original_text'];
        
        if ( empty( $original_text ) ) {
            return false;
        }

        // Use the core translation logic
        $translated_text = $this->core_translate_text( $original_text, $target_language );
        
        if ( $translated_text && $translated_text !== $original_text ) {
            return [
                'id' => $element['id'],
                'widgetType' => $element['widgetType'],
                'original_text' => $original_text,
                'translated_text' => $translated_text
            ];
        }

        return false;
    }

    /**
     * Core translation method that handles the actual DeepL API call
     *
     * @param string $text_to_translate The text to translate
     * @param string $target_language The target language code
     * @return string|false The translated text or false on failure
     * @since 1.2.0
     * @access private
     */
    private function core_translate_text( $text_to_translate, $target_language ) {
        if ( empty( $text_to_translate ) || empty( $target_language ) ) {
            return false;
        }

        // Define DeepL API key
        if ( ! defined( 'EIT_DEEPL_API_KEY' ) ) {
            define( 'EIT_DEEPL_API_KEY', '5b2070a2-59bc-4902-b009-c9ef94f845b5:fx' );
        }

        $api_key = EIT_DEEPL_API_KEY;

        if ( empty( $api_key ) || $api_key === 'DIN_DEEPL_API_NØGLE' ) {
            error_log('EIT Debug: DeepL API key not configured properly');
            return false;
        }

        // Intelligent handling of HTML content
        $original_html = $text_to_translate;
        $text_for_translation = $text_to_translate;
        $is_html_content = false;
        
        // Check if content contains HTML tags
        if ( strpos( $text_to_translate, '<' ) !== false || strpos( $text_to_translate, '&' ) !== false ) {
            $is_html_content = true;
            error_log('EIT Debug: Detected HTML content, preserving structure');
            
            // Extract plain text from HTML for translation
            $text_for_translation = $this->extract_text_from_html( $text_to_translate );
            error_log('EIT Debug: Extracted text for translation: ' . $text_for_translation);
        }

        // DeepL API URL (using free version)
        $api_url = 'https://api-free.deepl.com/v2/translate';

        $response = wp_remote_post( $api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'      => [
                'text'        => $text_for_translation,
                'target_lang' => $target_language,
            ],
            'timeout'   => 30,
        ]);

        if ( is_wp_error( $response ) ) {
            error_log('EIT Debug: Error communicating with DeepL API: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( wp_remote_retrieve_response_code( $response ) !== 200 || ! isset( $data['translations'][0]['text'] ) ) {
            $response_code = wp_remote_retrieve_response_code( $response );
            error_log('EIT Debug: DeepL API error. Response code: ' . $response_code . ', Data: ' . print_r($data, true));
            return false;
        }

        $translated_text = $data['translations'][0]['text'];

        // If it was HTML content, reconstruct HTML structure with translated text
        if ( $is_html_content ) {
            $translated_text = $this->reconstruct_html_with_translated_text( $original_html, $text_for_translation, $translated_text );
            error_log('EIT Debug: Reconstructed HTML with translated text: ' . $translated_text);
        }

        return $translated_text;
    }


    /**
     * Admin notice for manglende Elementor plugin.
     *
     * @since 0.1.0
     * @access public
     */
    public function admin_notice_missing_main_plugin() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(
            esc_html__( '"%1$s" kræver "%2$s" for at være installeret og aktiveret.', 'elementor-inline-translate' ),
            '<strong>' . esc_html__( 'Elementor Inline Oversættelse', 'elementor-inline-translate' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'elementor-inline-translate' ) . '</strong>'
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    /**
     * Admin notice for minimum Elementor version.
     *
     * @since 0.1.0
     * @access public
     */
    public function admin_notice_minimum_elementor_version() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(
            esc_html__( '"%1$s" kræver "%2$s" version %3$s eller højere.', 'elementor-inline-translate' ),
            '<strong>' . esc_html__( 'Elementor Inline Oversættelse', 'elementor-inline-translate' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'elementor-inline-translate' ) . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    /**
     * Admin notice for minimum PHP version.
     *
     * @since 0.1.0
     * @access public
     */
    public function admin_notice_minimum_php_version() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(
            esc_html__( '"%1$s" kræver PHP version %2$s eller højere.', 'elementor-inline-translate' ),
            '<strong>' . esc_html__( 'Elementor Inline Oversættelse', 'elementor-inline-translate' ) . '</strong>',
            self::MINIMUM_PHP_VERSION
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }
}

// Initialiser pluginet
Elementor_Inline_Translate::instance();
