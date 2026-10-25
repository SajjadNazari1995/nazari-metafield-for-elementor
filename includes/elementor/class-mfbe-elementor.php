<?php
defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration: widget category, "Meta Field" widget and the dynamic tags group.
 */
class MFBE_Elementor {

	public static function init() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_tags' ) );
	}

	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'mfbe',
			array(
				'title' => __( 'Meta Fields', 'nazari-metafield-for-elementor' ),
				'icon'  => 'eicon-database',
			)
		);
	}

	public static function register_widgets( $widgets_manager ) {
		require_once MFBE_PATH . 'includes/elementor/class-mfbe-widget-field.php';
		$widgets_manager->register( new MFBE_Widget_Field() );
	}

	public static function register_tags( $tags_manager ) {
		$tags_manager->register_group(
			'mfbe',
			array( 'title' => __( 'Meta Fields', 'nazari-metafield-for-elementor' ) )
		);

		require_once MFBE_PATH . 'includes/elementor/tags/class-mfbe-tag-base.php';
		require_once MFBE_PATH . 'includes/elementor/tags/class-mfbe-tags.php';

		$tags_manager->register( new MFBE_Tag_Text() );
		$tags_manager->register( new MFBE_Tag_Number() );
		$tags_manager->register( new MFBE_Tag_URL() );
		$tags_manager->register( new MFBE_Tag_Color() );
		$tags_manager->register( new MFBE_Tag_Image() );
		$tags_manager->register( new MFBE_Tag_Gallery() );
	}
}
