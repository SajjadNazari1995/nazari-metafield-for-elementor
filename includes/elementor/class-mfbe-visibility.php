<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Plugin;

/**
 * Dynamic Visibility: show or hide any Elementor element depending on a meta field of the current post.
 */
class MFBE_Visibility {

	/** Elementor element type => section after which the controls are added. */
	const SECTIONS = array(
		'common'    => '_section_style',
		'section'   => 'section_advanced',
		'column'    => 'section_advanced',
		'container' => 'section_layout',
	);

	public static function init() {
		foreach ( self::SECTIONS as $element => $section ) {
			add_action( "elementor/element/{$element}/{$section}/after_section_end", array( __CLASS__, 'add_controls' ), 10, 1 );
		}

		foreach ( array( 'widget', 'section', 'column', 'container' ) as $type ) {
			add_filter( "elementor/frontend/{$type}/should_render", array( __CLASS__, 'should_render' ), 10, 2 );
		}
	}

	public static function add_controls( $element ) {
		$element->start_controls_section(
			'mfbe_visibility',
			array(
				'label' => __( 'Meta Field Visibility', 'nazari-metafield-for-elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'mfbe_vis_enable',
			array(
				'label'        => __( 'Enable', 'nazari-metafield-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$element->add_control(
			'mfbe_vis_action',
			array(
				'label'     => __( 'Action', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'show',
				'options'   => array(
					'show' => __( 'Show this element if…', 'nazari-metafield-for-elementor' ),
					'hide' => __( 'Hide this element if…', 'nazari-metafield-for-elementor' ),
				),
				'condition' => array( 'mfbe_vis_enable' => 'yes' ),
			)
		);

		$element->add_control(
			'mfbe_vis_field',
			array(
				'label'     => __( 'Field', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => MFBE_Registry::field_choices(),
				'default'   => '',
				'condition' => array( 'mfbe_vis_enable' => 'yes' ),
			)
		);

		$element->add_control(
			'mfbe_vis_operator',
			array(
				'label'     => __( 'Condition', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => MFBE_Conditions::operators(),
				'default'   => 'not_empty',
				'condition' => array( 'mfbe_vis_enable' => 'yes' ),
			)
		);

		$element->add_control(
			'mfbe_vis_value',
			array(
				'label'     => __( 'Value', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => array(
					'mfbe_vis_enable'   => 'yes',
					'mfbe_vis_operator' => array( 'eq', 'neq', 'contains', 'gt', 'lt' ),
				),
			)
		);

		$element->add_control(
			'mfbe_vis_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'The element is always visible while you edit in Elementor.', 'nazari-metafield-for-elementor' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'mfbe_vis_enable' => 'yes' ),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Whether the rule of one element is satisfied for the current post.
	 */
	public static function passes( array $settings ) {
		if ( empty( $settings['mfbe_vis_enable'] ) || 'yes' !== $settings['mfbe_vis_enable'] || empty( $settings['mfbe_vis_field'] ) ) {
			return true;
		}

		$name    = (string) $settings['mfbe_vis_field'];
		$post_id = (int) get_the_ID();
		if ( ! MFBE_Registry::find_field( $name ) || ! $post_id || ! MFBE_Shortcode::can_read( $post_id ) ) {
			return true;
		}

		$operator = MFBE_Conditions::sanitize_operator( isset( $settings['mfbe_vis_operator'] ) ? $settings['mfbe_vis_operator'] : 'not_empty' );
		$expected = isset( $settings['mfbe_vis_value'] ) ? (string) $settings['mfbe_vis_value'] : '';
		$matches  = MFBE_Conditions::evaluate( MFBE_Fields::get_value( $name, $post_id ), $operator, $expected );

		return ( isset( $settings['mfbe_vis_action'] ) && 'hide' === $settings['mfbe_vis_action'] ) ? ! $matches : $matches;
	}

	public static function should_render( $should_render, $element ) {
		if ( ! $should_render ) {
			return $should_render;
		}

		$editor = Plugin::$instance->editor;
		if ( $editor->is_edit_mode() || Plugin::$instance->preview->is_preview_mode() ) {
			return $should_render;
		}

		return self::passes( $element->get_settings_for_display() );
	}
}
