<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * "Meta Field Link" widget: a button/link built from a link, URL, email or file field.
 */
class MFBE_Widget_Link extends Widget_Base {

	const TYPES = array( 'link', 'url', 'email', 'file' );

	public function get_name() {
		return 'mfbe_link';
	}

	public function get_title() {
		return __( 'Meta Field Link', 'nazari-metafield-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-button';
	}

	public function get_categories() {
		return array( 'mfbe', 'general' );
	}

	public function get_keywords() {
		return array( 'meta', 'field', 'link', 'button', 'download' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_link', array( 'label' => __( 'Link', 'nazari-metafield-for-elementor' ) ) );

		$this->add_control(
			'field',
			array(
				'label'   => __( 'Field', 'nazari-metafield-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => MFBE_Registry::field_choices( self::TYPES ),
				'default' => '',
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => __( 'Button text', 'nazari-metafield-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Leave empty to use the link text (or the URL).', 'nazari-metafield-for-elementor' ),
			)
		);

		$this->add_control(
			'new_tab',
			array(
				'label'        => __( 'Open in new tab', 'nazari-metafield-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'as_button',
			array(
				'label'        => __( 'Show as button', 'nazari-metafield-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
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
					'left'   => array( 'title' => __( 'Left', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'nazari-metafield-for-elementor' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array( '{{WRAPPER}} .mfbe-link-wrap' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .mfbe-link',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Text color', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mfbe-link' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => __( 'Background color', 'nazari-metafield-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mfbe-link.is-button' => 'background-color: {{VALUE}};' ),
				'condition' => array( 'as_button' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => __( 'Padding', 'nazari-metafield-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .mfbe-link.is-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
				'condition'  => array( 'as_button' => 'yes' ),
			)
		);

		$this->add_control(
			'radius',
			array(
				'label'      => __( 'Border radius', 'nazari-metafield-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .mfbe-link.is-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
				'condition'  => array( 'as_button' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * @return array{0:string,1:string,2:bool} URL, link text from the field, opens in new tab by field.
	 */
	private function resolve( $settings ) {
		$name  = (string) $settings['field'];
		$field = $name ? MFBE_Registry::find_field( $name ) : null;
		$id    = (int) get_the_ID();
		if ( ! $field || ! $id || ! MFBE_Shortcode::can_read( $id ) ) {
			return array( '', '', false );
		}

		$value = MFBE_Fields::get_value( $name, $id );
		switch ( $field['type'] ) {
			case 'link':
				if ( ! is_array( $value ) || empty( $value['url'] ) ) {
					return array( '', '', false );
				}
				return array( (string) $value['url'], (string) $value['text'], '_blank' === ( isset( $value['target'] ) ? $value['target'] : '' ) );
			case 'file':
				return array( (string) wp_get_attachment_url( absint( $value ) ), '', false );
			case 'email':
				return array( is_email( (string) $value ) ? 'mailto:' . sanitize_email( (string) $value ) : '', (string) $value, false );
			default:
				return array( (string) $value, '', false );
		}
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		list( $url, $field_text, $field_blank ) = $this->resolve( $settings );

		if ( '' === $url ) {
			if ( 'yes' === $settings['hide_if_empty'] && ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				return;
			}
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="mfbe-empty">' . esc_html__( 'Select a link field. Nothing to show for this post yet.', 'nazari-metafield-for-elementor' ) . '</p>';
			}
			return;
		}

		$text = '' !== (string) $settings['text'] ? (string) $settings['text'] : ( '' !== $field_text ? $field_text : $url );
		$blank = 'yes' === $settings['new_tab'] || $field_blank;
		$class = 'mfbe-link' . ( 'yes' === $settings['as_button'] ? ' is-button elementor-button' : '' );

		printf(
			'<div class="mfbe-link-wrap"><a class="%1$s" href="%2$s"%3$s>%4$s</a></div>',
			esc_attr( $class ),
			esc_url( $url ),
			$blank ? ' target="_blank" rel="noopener noreferrer"' : '',
			esc_html( $text )
		);
	}
}
