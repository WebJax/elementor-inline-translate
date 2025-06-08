<?php
/**
 * Plugin Name: Elementor Inline Oversættelse
 * Description: Et simpelt plugin til at demonstrere inline oversættelse i Elementor editoren.
 * Version: 1.0.0
 * Author: Jaxweb
 * Text Domain: elementor-inline-translate
 * Domain Path: /languages
 * Elementor tested up to: 3.29.0
 * Elementor Pro tested up to: 3.29.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EIT_PLUGIN_VERSION', '0.1.0' );
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
            'eitEditor', // Objektnavn i JavaScript
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'eit_translate_nonce' ),
                // Du kan tilføje oversættelige strenge her, hvis nødvendigt
                'i18n'     => [
                    'translateButton' => __( 'Oversæt Tekst', 'elementor-inline-translate' ),
                    'selectLanguage'  => __( 'Vælg Sprog:', 'elementor-inline-translate' ),
                    'translating'     => __( 'Oversætter...', 'elementor-inline-translate' ),
                    'error'           => __( 'Fejl under oversættelse.', 'elementor-inline-translate' ),
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
     * Ekstraherer ren tekst fra HTML for oversættelse.
     * Bevarer HTML strukturen og returnerer kun tekst-indholdet.
     *
     * @param string $html HTML indhold.
     * @return string Ren tekst til oversættelse.
     */
    private function extract_text_from_html( $html ) {
        // Brug WordPress' indbyggede funktion til at fjerne HTML tags
        $text = wp_strip_all_tags( $html );
        
        // Decode HTML entities
        $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        
        // Trim whitespace
        $text = trim( $text );
        
        return $text;
    }

    /**
     * Rekonstruerer HTML med oversat tekst.
     * Erstatter den originale tekst i HTML strukturen med den oversatte tekst.
     *
     * @param string $original_html Original HTML struktur.
     * @param string $original_text Original ren tekst.
     * @param string $translated_text Oversat tekst.
     * @return string HTML med oversat indhold.
     */
    private function reconstruct_html_with_translated_text( $original_html, $original_text, $translated_text ) {
        // Simpel tilgang: erstat den originale tekst med den oversatte tekst i HTML'en
        // Dette fungerer for de fleste enkle tilfælde
        
        // Først prøv direkte erstatning hvis den originale tekst findes i HTML'en
        if ( strpos( $original_html, $original_text ) !== false ) {
            return str_replace( $original_text, $translated_text, $original_html );
        }
        
        // Hvis direkte erstatning ikke virker, prøv en mere avanceret tilgang
        // Ved hjælp af DOMDocument for at bevare HTML struktur
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
            
            $combined_text = '';
            foreach ( $textNodes as $textNode ) {
                $combined_text .= trim( $textNode->textContent ) . ' ';
            }
            $combined_text = trim( $combined_text );
            
            // Hvis den kombinerede tekst matcher den originale tekst
            if ( $combined_text === $original_text || similar_text( $combined_text, $original_text ) > 0.8 ) {
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
        
        // Fallback: Hvis alt andet fejler, returner den oversatte tekst omgivet af original HTML tags
        // Dette er en simpel tilgang til at bevare grundlæggende formatering
        preg_match( '/^(<[^>]*>)/', $original_html, $start_matches );
        preg_match( '/(<[^>]*>)$/', $original_html, $end_matches );
        
        $start_tag = isset( $start_matches[1] ) ? $start_matches[1] : '';
        $end_tag = isset( $end_matches[1] ) ? $end_matches[1] : '';
        
        if ( ! empty( $start_tag ) && ! empty( $end_tag ) ) {
            return $start_tag . $translated_text . $end_tag;
        }
        
        // Som sidste udvej, returner bare den oversatte tekst
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
