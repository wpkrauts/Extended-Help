<?php	# -*- coding: utf-8 -*-
/*
Plugin Name: Extended Help
Description: Help texts for super heroes
Version:     0.1
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
	 */
	public function __construct()
	{
		$this->register_help_post_type();
		$this->register_help_position();
	}

	/**
	 * Registers the custom post type for help texts.
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
