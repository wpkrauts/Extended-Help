<?php	# -*- coding: utf-8 -*-
/*
Plugin Name: Extended Help
Description: Help texts for super heroes
Version:     0.2
Author:      Thomas Scholz
Author URI:  http://toscho.de
License:     GPL
*/

// 'admin_init' is too late, doesn’t work.
add_action( 'init', array ( 'Extended_Help', 'init' ) );

/**
 * Creates help text post type and a position taxonomy.
 * @author Thomas Scholz
 * @todo I18n
 * @todo Output on help tabs
 */
class Extended_Help
{
	public $post_type         = 'helptext';
	public $position_taxonomy = 'helpposition';

	/**
	 * Handler for the action 'init'. Instantiates this class.
	 *
	 * @since 0.1
	 * @return void
	 */
	public static function init()
	{
		// If want to use another class (an extension maybe),
		// change the class name here.
		$class = __CLASS__ ;

		// Named global variable to make access for other scripts easier.
		if ( empty ( $GLOBALS[ $class ] ) )
		{
			$GLOBALS[ $class ] = new $class;
		}
	}

	/**
	 * Basic setup.
	 *
	 * @since 0.1
	 */
	public function __construct()
	{
		$this->register_help_post_type();
		$this->register_help_position();

		add_filter( 'contextual_help', array ( $this, 'help_to_tab' ), 10, 3);
	}

	/**
	 * Replaces the help tab content with custom text if available.
	 *
	 * @since 0.2
	 * @param  string $contextual_help Default text
	 * @param  string $screen_id
	 * @param  object $screen
	 * @return string
	 */
	public function help_to_tab( $contextual_help, $screen_id, $screen )
	{
		if ( ! ( $output = $this->list_help( $screen->id ) ) )
		{
			return $contextual_help;
		}

		return $output;
	}

	/**
	 * HTML formatted help text. May be used for a shortcode.
	 *
	 * @since 0.2
	 * @param  string $screen_id
	 * @param  array $args 'heading_open', 'heading_close' and 'separator'
	 * @return string|FALSE
	 */
	public function list_help( $screen_id = NULL, $args = array () )
	{
		if ( ! ( $help = $this->get_help( $screen_id ) ) )
		{
			return FALSE;
		}

		$defaults = array (
			'heading_open'  => '<h3>'
		,	'heading_close' => '</h3>'
		,	'separator'     => '<hr />'
		);

		$options = array_merge( $defaults, $args );
		$out     = array ();

		while ( $help->have_posts() )
		{
			$text  = $help->next_post();
			$out[] = $options['heading_open'] . $text->post_title . $options['heading_close']
				. wpautop( do_shortcode( $text->post_content ) );
		}
		return implode( $options['separator'], $out );
	}

	/**
	 * Queries the DB for help texts
	 *
	 * @since 0.2
	 * @param  string $screen_id
	 * @return object|FALSE
	 */
	public function get_help( $screen_id = NULL )
	{
		if ( is_null( $screen_id ) )
		{
			if ( isset ( $GLOBALS['menu']->id ) )
			{
				$screen_id = $GLOBALS['menu']->id;
			}
			else
			{
				return FALSE;
			}
		}

		// @see http://codex.wordpress.org/Function_Reference/term_exists
		$term = term_exists( $screen_id, $this->position_taxonomy );

		// Screen not found.
		// Should be a Term Array
		// format: array( 'term_id' => term id, 'term_taxonomy_id' => taxonomy id )
		if ( ! is_array( $term ) )
		{
			return FALSE;
		}

		$term_data = get_term( $term['term_id'], $this->position_taxonomy );

		$args      = array (
			'orderby'         => 'date'
		,	'order'           => 'ASC'
		,	'post_type'       => $this->post_type
		// No limit.
		,	'posts_per_page'  => -1
		,	'taxonomy'        => $this->position_taxonomy
		,	'term'            => $term_data->slug
		);

		$help = new WP_Query( $args );

		if ( ! $help->have_posts() )
		{
			return FALSE;
		}

		return $help;
	}

	/**
	 * Registers the custom post type for help texts.
	 *
	 * @since 0.1
	 * @return void
	 */
	public function register_help_post_type()
	{
		// The activation hook may have set up the type already.
		if ( post_type_exists( $this->post_type ) )
		{
			return;
		}

		$labels = array (
			'name'      => 'Hilfetexte'
		,	'menu_name' => 'Hilfetexte'
		);
		register_post_type(
			$this->post_type
		,	array (
				'label'         => 'Hilfetexte'
			,	'labels'        => $labels
			,	'menu_position' => 100
			,	'show_ui'       => TRUE
			,	'show_in_menu'  => TRUE
			,	'supports'      => array ( 'editor', 'title' )
			)
		);
	}

	/**
	 * Registers the custom taxonomy for help texts.
	 *
	 * @since 0.1
	 * @return void
	 */
	public function register_help_position()
	{
		if ( taxonomy_exists( $this->position_taxonomy ) )
		{
			return;
		}

		$args = array (
			'hierarchical' => TRUE
		,	'label'        => 'Position'
		,	'public'       => FALSE
		,	'show_ui'      => TRUE
		);

		register_taxonomy(
			$this->position_taxonomy
		,	array ( $this->post_type )
		,	$args
		);

		// @todo extend
		// @todo I18n
		// @todo update automatically when new screens are registered
		// @todo better labels
		$predefined_positions = array (
			'dashboard'
		,	'update-core'
		,	'edit'
		,	'post'
		,	'edit-tags'
		,	'plugins'
		,	'plugin-install'
		,	'plugin-editor'
		);

		foreach ( $predefined_positions as $pos )
		{
			if ( ! term_exists( $pos, $this->position_taxonomy ) )
			{
				wp_insert_term( $pos, $this->position_taxonomy );
			}
		}
	}
}

# -----------------------------------------

// Debug helper
function eh_screen_info( $contextual_help, $screen_id, $screen )
{
	global $menu;
	$menuhtml = '<pre>' . var_export( $menu, 1) . '</pre>';
	$screenhtml = '<pre>' . var_export( $screen, 1) . '</pre>';
	return "$contextual_help
	<hr>screen->id: $screen->id
	<br>screen->parent_base: $screen->parent_base
	<br>screen->base: <input size=20 value=\"'$screen->base'\">"
		. $screenhtml . $menuhtml;
}

# debug info
# add_filter( 'contextual_help', 'eh_screen_info', 10, 3);
