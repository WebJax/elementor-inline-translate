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
}