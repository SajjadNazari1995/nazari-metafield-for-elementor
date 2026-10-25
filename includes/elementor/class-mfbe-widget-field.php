<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * "Meta Field" widget: prints any field of any group, for the current post or a chosen one.
 */
class MFBE_Widget_Field extends Widget_Base {

	public function get_name() {
		return 'mfbe_field';
	}

	public function get_title() {
		return __( 'Meta Field', 'nazari-metafield-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-database';
	}

	public function get_categories() {
		return array( 'mfbe', 'general' );
	}

	public function get_keywords() {
		return array( 'meta', 'field', 'custom field', 'acf', 'jet' );
	}

	protected function register_controls() {

		/* Content ---------------------------------------------------- */
		$this->start_controls_section( 'section_field', array( 'label' => __( 'Field', 'nazari-metafield-for-elementor' ) ) );

		$this->add_control(
			'field',
			array(
				'label'   => __( 'Field', 'nazari-metafield-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => MFBE_Registry::field_choices(),
				'default' => '',
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Source', 'nazari-metafield-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'current'  => __( 'Current post', 'nazari-metafield-for-elementor' ),
					'specific' => __( 'Specific post ID', 'nazari-metafield-for-elementor' ),
				),
				'default' => 'current',
			)
		);

		$this->add_control(
			'post_id',
			array(
				'label'     => __( 'Post ID', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'condition' => array( 'source' => 'specific' ),
			)
		);

		$this->add_control(
			'html_tag',
			array(
				'label'   => __( 'HTML tag', 'nazari-metafield-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'div'  => 'div',
					'p'    => 'p',
					'span' => 'span',
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
				),
				'default' => 'div',
			)
		);

		$this->add_control(
			'before',
			array(
				'label'       => __( 'Text before', 'nazari-metafield-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'after',
			array(
				'label'   => __( 'Text after', 'nazari-metafield-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'fallback',
			array(
				'label'       => __( 'Fallback', 'nazari-metafield-for-elementor' ),
				'description' => __( 'Shown when the field is empty.', 'nazari-metafield-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'hide_if_empty',
			array(
				'label'        => __( 'Hide when empty', 'nazari-metafield-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		/* Image / gallery -------------------------------------------- */
		$this->start_controls_section( 'section_media', array( 'label' => __( 'Image & Gallery', 'nazari-metafield-for-elementor' ) ) );

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'    => 'image',
				'default' => 'large',
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => __( 'Gallery columns', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 12,
				'default'   => 3,
				'selectors' => array(
					'{{WRAPPER}} .mfbe-gallery' => 'display: grid; grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'     => __( 'Gallery gap', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 10, 'unit' => 'px' ),
				'selectors' => array(
					'{{WRAPPER}} .mfbe-gallery' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		/* Style ------------------------------------------------------- */
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'nazari-metafield-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'    => array( 'title' => __( 'Left', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-left' ),
					'center'  => array( 'title' => __( 'Center', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-center' ),
					'right'   => array( 'title' => __( 'Right', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-right' ),
					'justify' => array( 'title' => __( 'Justified', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-justify' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .mfbe-field' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text color', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mfbe-field' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .mfbe-field',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$name     = isset( $settings['field'] ) ? $settings['field'] : '';
		$field    = $name ? MFBE_Registry::find_field( $name ) : null;
		$is_edit  = \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $field ) {
			if ( $is_edit ) {
				echo '<div class="mfbe-field">' . esc_html__( 'Select a field in the widget settings.', 'nazari-metafield-for-elementor' ) . '</div>';
			}
			return;
		}

		$post_id = ( 'specific' === $settings['source'] && ! empty( $settings['post_id'] ) ) ? absint( $settings['post_id'] ) : (int) get_the_ID();

		$html = '';
		if ( $post_id && MFBE_Shortcode::can_read( $post_id ) ) {
			$value = MFBE_Fields::get_value( $name, $post_id );
			$html  = MFBE_Fields::render_html(
				$field,
				$value,
				array(
					'size'    => $this->image_size( $settings ),
					'columns' => 0, // The grid is styled by the widget controls.
				)
			);
		}

		if ( '' === $html ) {
			if ( ! empty( $settings['fallback'] ) ) {
				$html = esc_html( $settings['fallback'] );
			} elseif ( 'yes' === $settings['hide_if_empty'] ) {
				if ( $is_edit ) {
					echo '<div class="mfbe-field">' . esc_html__( 'This field is empty for the current post.', 'nazari-metafield-for-elementor' ) . '</div>';
				}
				return;
			}
		} else {
			$html = esc_html( $settings['before'] ) . $html . esc_html( $settings['after'] );
		}

		$tag = in_array( $settings['html_tag'], array( 'div', 'p', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $settings['html_tag'] : 'div';

		// Block-level output (lists, figures, paragraphs) must not live inside <p> / <span> / headings.
		if ( in_array( $field['type'], array( 'wysiwyg', 'gallery', 'textarea' ), true ) && 'div' !== $tag ) {
			$tag = 'div';
		}

		printf( '<%1$s class="mfbe-field mfbe-field-%2$s">%3$s</%1$s>', esc_attr( $tag ), esc_attr( $field['type'] ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput -- $html is built from escaped / sanitized parts.
	}

	private function image_size( $settings ) {
		$size = isset( $settings['image_size'] ) ? $settings['image_size'] : 'large';
		if ( 'custom' === $size && ! empty( $settings['image_custom_dimension']['width'] ) ) {
			return array(
				absint( $settings['image_custom_dimension']['width'] ),
				absint( $settings['image_custom_dimension']['height'] ),
			);
		}
		return $size;
	}
}
