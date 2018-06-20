<?php

/**
 * @since 1.0.0
 * @author shennemann
 * @licence MIT
 */
class PostratingsThemeCustomizer {
    private $sectionId;
    private $textdomain;
    private $fields;
    private $postgalleryAdmin;
    private $postgallery;

    public function __construct() {
        $id = 'postrating';
        $this->textdomain = 'postrating';
        $this->sectionId = $id;

        $this->postgalleryAdmin = \Admin\PostratingsAdmin::getInstance();


        $this->fields = array();

        $this->fields['postrating-base'] =
            array(
                'title' => __( 'Main-Settings', $this->textdomain ),
                'fields' => array(
                    'onlyLoggedIn' => array(
                        'type' => 'checkbox',
                        'label' => __( 'Only logged users can vote', $this->textdomain ),
                        'default' => false,
                    ),

                    'noDashicons' => array(
                        'type' => 'checkbox',
                        'label' => __( 'Don´t load dashicons', $this->textdomain ),
                        'default' => false,
                    ),

                    'noDefaultStyle' => array(
                        'type' => 'checkbox',
                        'label' => __( 'Don´t load default styles', $this->textdomain ),
                        'default' => false,
                    ),

                    'commentFields' => array(
                        'type' => 'textarea',
                        'label' => __( 'Fields for Comments (on per Line)', $this->textdomain ),
                        'default' => '',
                    ),
                    'showCommentSummary' => array(
                        'type' => 'checkbox',
                        'label' => __( 'Show summary in comments', $this->textdomain ),
                        'default' => false,
                    ),
                ),
            );
    }

    public function actionCustomizeRegister( $wp_customize ) {
        $prefix = 'postratings_';
        $wp_customize->add_panel( 'postratings-panel', array(
            'title' => __( 'Postratings' ),
            'section' => 'postratings',
        ) );


        foreach ( $this->fields as $sectionId => $section ) {
            $wp_customize->add_section( $sectionId, array(
                'title' => __( $section['title'], $this->textdomain ),
                'panel' => 'postratings-panel',
            ) );

            foreach ( $section['fields'] as $fieldId => $field ) {
                $settingId = $prefix . ( !is_numeric( $fieldId ) ? $fieldId : $field['id'] );
                $controlId = $settingId . '-control';

                $wp_customize->add_setting( $settingId, array(
                    'default' => !empty( $field['default'] ) ? $field['default'] : '',
                    'transport' => !empty( $field['transport'] ) ? $field['transport'] : 'refresh',
                ) );

                $wp_customize->add_control( $controlId, array(
                    'label' => __( $field['label'], $this->textdomain ),
                    'section' => $sectionId,
                    'type' => !empty( $field['type'] ) ? $field['type'] : 'text',
                    'settings' => $settingId,
                    'description' => !empty( $field['description'] ) ? __( $field['description'], $this->textdomain ) : '',
                    'choices' => !empty( $field['choices'] ) ? $field['choices'] : null,
                    'input_attrs' => !empty( $field['input_attrs'] ) ? $field['input_attrs'] : null,
                ) );
            }
        }
    }
}

/*if( class_exists( 'WP_Customize_Control' ) ) {
    class WP_Customize_Headline_Control extends WP_Customize_Control {
        public $type = 'headline';

        public function render_content() {
            echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
        }
    }
}*/