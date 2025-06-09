<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Elementor_Inline_Translate_Integration {

    public function __construct() {
        error_log('EIT Debug: Elementor_Inline_Translate_Integration constructor called');
        
        // Tilføj kontrol til specifikke widgets
        // Eksempel: Overskrift widget
        add_action( 'elementor/element/heading/section_title/before_section_end', [ $this, 'add_translate_button_to_widget' ], 10, 2 );
        // Eksempel: Teksteditor widget
        add_action( 'elementor/element/text-editor/section_editor/before_section_end', [ $this, 'add_translate_button_to_widget' ], 10, 2 );
        // Eksempel: Button widget
        add_action( 'elementor/element/button/section_button/before_section_end', [ $this, 'add_translate_button_to_widget' ], 10, 2 );
        
        // Tilføj page settings for bulk oversættelse
        add_action( 'elementor/documents/register_controls', [ $this, 'add_page_bulk_translation_controls' ] );
        
        // Du kan tilføje lignende hooks for andre widgets og sektioner.
        // Find de korrekte hooks ved at inspicere Elementors kode eller bruge `elementor/element/after_section_end` og tjekke `$section_id`.
    }

    public function add_translate_button_to_widget( $element, $args ) {
        error_log('EIT Debug: add_translate_button_to_widget called for widget: ' . $element->get_name());
        
        // $element er \Elementor\Controls_Stack objektet (widget eller sektion)
        // $args er argumenter sendt til hooken

        // Vi skal bruge navnet på det kontrolfelt, der indeholder teksten.
        // Dette varierer fra widget til widget.
        $text_control_name = '';
        if ( 'heading' === $element->get_name() ) {
            $text_control_name = 'title'; // For overskrift widget er det typisk 'title'
        } elseif ( 'text-editor' === $element->get_name() ) {
            $text_control_name = 'editor'; // For teksteditor er det typisk 'editor'
        } elseif ( 'button' === $element->get_name() ) {
            $text_control_name = 'text'; // For button widget er det typisk 'text'
        }
        // Tilføj flere 'else if' for andre widgets

        if ( empty( $text_control_name ) ) {
            return; // Kan ikke finde tekstkontrol for dette widget
        }

        $element->add_control(
            'eit_translate_button_section',
            [
                'label' => __( 'Inline Oversættelse', 'elementor-inline-translate' ),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $element->add_control(
            'eit_target_language',
            [
                'label' => __( 'Målsprog', 'elementor-inline-translate' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'EN-GB', // Standard sprog
                'options' => [
                    'DA' => __( 'Dansk', 'elementor-inline-translate' ),
                    'DE' => __( 'Tysk', 'elementor-inline-translate' ),
                    'EN-GB' => __( 'Engelsk', 'elementor-inline-translate' ),
                ],
                'label_block' => true,
            ]
        );

        // Tilføj reference tekst felt (kun hvis PolyLang er aktivt og dette er en oversættelse)
        $main_plugin = \Elementor_Inline_Translate::instance();
        if ( $main_plugin->is_polylang_active() && $main_plugin->is_current_page_translation() ) {
            $element->add_control(
                'eit_reference_text_' . $text_control_name,
                [
                    'label' => sprintf( 
                        __( 'Reference (%s)', 'elementor-inline-translate' ), 
                        strtoupper( $main_plugin->get_default_language() )
                    ),
                    'type' => \Elementor\Controls_Manager::TEXTAREA,
                    'default' => __( 'Indlæser reference tekst...', 'elementor-inline-translate' ),
                    'description' => __( 'Tekst fra hovedsprogets version af dette element', 'elementor-inline-translate' ),
                    'rows' => 3,
                    'classes' => 'eit-reference-field eit-reference-text',
                    'separator' => 'before',
                    'dynamic' => [
                        'active' => false, // Disable dynamic content
                    ],
                ]
            );

            $element->add_control(
                'eit_copy_from_reference',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => '<button type="button" class="elementor-button elementor-button-default eit-copy-reference-button" data-control-name="' . $text_control_name . '">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-text">' . __( 'Kopier fra hovedsprog', 'elementor-inline-translate' ) . '</span>
                        </span>
                    </button>',
                    'separator' => 'none',
                    'content_classes' => 'eit-copy-reference-control',
                ]
            );
        }

        $element->add_control(
            'eit_translate_button',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'label' => __( 'Oversæt', 'elementor-inline-translate' ),
                'raw' => '<button type="button" class="elementor-button elementor-button-default eit-translate-button" data-event="eit:translate" data-control-name="' . $text_control_name . '">
                    <span class="elementor-button-content-wrapper">
                        <span class="elementor-button-text">' . __( 'Start Oversættelse', 'elementor-inline-translate' ) . '</span>
                    </span>
                </button>',
                'separator' => 'none',
                'content_classes' => 'eit-translate-control',
            ]
        );

         // Tilføj en skjult kontrol til at gemme navnet på tekstfeltet for nemmere JS adgang
         $element->add_control(
            'eit_text_control_name_holder',
            [
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'default' => $text_control_name,
            ]
        );
    }

    /**
     * Tilføj page-level controls for bulk oversættelse.
     * 
     * @param \Elementor\Core\DocumentTypes\PageBase $document
     */
    public function add_page_bulk_translation_controls( $document ) {
        // Kun tilføj controls til page documents
        if ( ! $document instanceof \Elementor\Core\DocumentTypes\PageBase ) {
            return;
        }

        error_log('EIT Debug: Adding page bulk translation controls');

        $document->start_controls_section(
            'eit_bulk_translation_section',
            [
                'label' => __( 'Bulk Oversættelse', 'elementor-inline-translate' ),
                'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'eit_bulk_translation_notice',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div style="padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; margin: 10px 0;">' .
                         '<h4 style="margin: 0 0 10px 0; color: #1976d2;">' . __( '🌐 Bulk Oversættelse', 'elementor-inline-translate' ) . '</h4>' .
                         '<p style="margin: 0 0 10px 0;">' . __( 'Brug Navigator panelet til venstre for at oversætte hele siden på én gang.', 'elementor-inline-translate' ) . '</p>' .
                         '<p style="margin: 0; font-size: 12px; color: #666;">' . __( 'Finder du "Bulk Oversættelse" sektionen øverst i Navigator panelet.', 'elementor-inline-translate' ) . '</p>' .
                         '</div>',
            ]
        );

        $document->add_control(
            'eit_bulk_default_target_language',
            [
                'label' => __( 'Standard Målsprog', 'elementor-inline-translate' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'EN-GB',
                'options' => [
                    'DA' => __( 'Dansk', 'elementor-inline-translate' ),
                    'DE' => __( 'Tysk', 'elementor-inline-translate' ),
                    'EN-GB' => __( 'Engelsk', 'elementor-inline-translate' ),
                    'FR' => __( 'Fransk', 'elementor-inline-translate' ),
                    'ES' => __( 'Spansk', 'elementor-inline-translate' ),
                    'IT' => __( 'Italiensk', 'elementor-inline-translate' ),
                    'NL' => __( 'Hollandsk', 'elementor-inline-translate' ),
                    'SV' => __( 'Svensk', 'elementor-inline-translate' ),
                    'NO' => __( 'Norsk', 'elementor-inline-translate' ),
                ],
                'description' => __( 'Dette sprog bruges som standard i bulk oversættelsen.', 'elementor-inline-translate' ),
            ]
        );

        // Kun vis exclude controls hvis PolyLang er aktivt
        $main_plugin = \Elementor_Inline_Translate::instance();
        if ( $main_plugin->is_polylang_active() ) {
            $document->add_control(
                'eit_bulk_polylang_notice',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => '<div style="padding: 10px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 4px; margin: 10px 0;">' .
                             '<strong>' . __( 'PolyLang Integration Aktiv', 'elementor-inline-translate' ) . '</strong><br>' .
                             __( 'Reference tekst fra hovedsproget vises automatisk.', 'elementor-inline-translate' ) .
                             '</div>',
                    'separator' => 'before',
                ]
            );
        }

        $document->add_control(
            'eit_bulk_translation_stats',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div id="eit-bulk-stats" style="padding: 10px; background: #f9f9f9; border-radius: 4px; margin: 10px 0;">' .
                         '<strong>' . __( 'Seneste Bulk Oversættelse:', 'elementor-inline-translate' ) . '</strong><br>' .
                         '<span id="eit-last-bulk-result">' . __( 'Ingen bulk oversættelse udført endnu.', 'elementor-inline-translate' ) . '</span>' .
                         '</div>',
                'separator' => 'before',
            ]
        );

        $document->end_controls_section();
    }
}