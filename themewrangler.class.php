<?php
if(!defined('WPINC')) exit('No direct access permitted');
/**
 * Theme Wrangler for Wordpress
 * 
 * @version 1.0
 * @author Darren Miller
 *
 * This software is copywright DM Logic Ltd
 * http://dmlogic.com
 *
 * You may use this software on commercial and
 * non commercial websites AT YOUR OWN RISK.
 * No warranty is provided nor liability accepted.
 * 
 * See documentation at http://dmlogic.com/add-ons/themewrangler
 *
 */
class Themewrangler {
	
	public static $footer_content = '';
	
	// -----------------------------------------------------------------
	
	private static $available_scripts = array();
	
	private static $default_scripts = array();
	
	private static $available_stylesheets = array();
	
	private static $default_stylesheets = array();
	
	private static $remove_from_head = array();
	
	private static $deregister_scripts = array();
	
	// -----------------------------------------------------------------
	
	/**
	 * set_defaults
	 * 
	 * Sets up default values. Called by your functions file
	 * 
	 * @param array $settings	an array settings values
	 * @access public
	 * @since 1.0
	 */
	public static function set_defaults($settings) {
		
		// most settings are arrays, so we can deal with them all at once
		$arrays = array('available_scripts','default_scripts','available_stylesheets','default_stylesheets','remove_from_head','deregister_scripts');
		foreach($arrays as $item) {
			
			if(isset($settings[$item]) && is_array($settings[$item])) {
				self::$$item = $settings[$item];
			}
		}
		
		// default footer script
		if(isset($settings['footer_content']) && !empty($settings['footer_content'])) {
			self::$footer_content = $settings['footer_content'];
		}
		
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * setup_page
	 * 
	 * Prepares the WP environment for template rendering
	 * 
	 * @param string $styles		an pipe separated list of style handles
	 * @param string $scripts		an pipe separated list of script handles
	 * @param string $page_script	string of additional JS to run
	 * @access public
	 * @since 1.0
	 */
	public static function setup_page($styles = true, $scripts = true, $page_script = '' ) {
		
		// get rid of any default scripts we don't like
		foreach(self::$deregister_scripts as $scrip) {			
			wp_deregister_script( $scrip );
		}
		
		// get rid of any header tags we don't want
		self::clean_head();
		
		// CSS
		self::set_styles($styles);
		
		// Javascript
		self::set_scripts($scripts);
		self::set_footer($page_script);
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * clean_head
	 * 
	 * Removes any head tags we've said we don't want
	 * 
	 * @global array $wp_filter
	 */
	private static function clean_head() {
		
		global $wp_filter;
		
		// don't continue if there's nothing to do
		if(!isset($wp_filter['wp_head']) || !is_array($wp_filter['wp_head']) || empty(self::$remove_from_head)) {
			return;
		}
		
		// because we need the priority value, we have to loop the filters to find it
		foreach($wp_filter['wp_head'] as $pri => $items) {
			
			foreach($items as $k => $v ) {
				
				// found one to remove
				if(in_array($k, self::$remove_from_head)) {
					
					// zap it!
					remove_filter('wp_head', $k, $pri);
				}
			}
		}
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * set_styles
	 * 
	 * Sort out all CSS requirements
	 * 
	 * @param string $styles		an pipe separated list of stylesheet handles
	 * @access private
	 * @since 1.0
	 */
	private static function set_styles($styles = '' ) {
		
		// no styles
		if(false == $styles) {
			return;
		}
		
		// run through any adjustments
		$load = self::parse_page_adjustments($styles, self::$default_stylesheets, 'style' );
		
		// now add all styles to head
		foreach($load as $style) {
			
			
			if( isset(self::$available_stylesheets[$style]) ) {
				
				$media = (isset(self::$available_stylesheets[$style][2])) ? self::$available_stylesheets[$style][2] : 'all';
				
				wp_enqueue_style(	$style,
									self::$available_stylesheets[$style][0],
									array(),
									self::$available_stylesheets[$style][1],
									$media);
			}
		}
		
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * set_scripts
	 * 
	 * Sort out all script loading
	 * 
	 * @param string $scripts		an pipe separated list of script handles
	 * @access private
	 * @since 1.0
	 */
	private static function set_scripts($scripts = '') {
		
		if(false == $scripts) {
			return;
		}
		
		// run through any adjustments
		$load = self::parse_page_adjustments($scripts, self::$default_scripts, 'script' );

		// now enqueue all scripts
		foreach($load as $script) {
			
			if( isset(self::$available_scripts[$script]) ) {
				wp_enqueue_script(	$script,
									self::$available_scripts[$script][0],
									array(),
									self::$available_scripts[$script][1],
									true);
			}
		}
		
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * parse_user_selections
	 * 
	 * Process adjustments for a given $type
	 * 
	 * @param mixed $requested
	 * @param array $defaults
	 * @param string $type
	 * @return array 
	 */
	private static function parse_page_adjustments($requested,$defaults,$type) {
		
		if(true === $requested) {
			return $defaults;
		}
		
		$these = explode('|',$requested);
		foreach($these as $handle) {

			$handle = trim($handle);

			// found a default script we don't want
			$not = (substr($handle,0,4) == 'not ');
			if($not) {
				$handle = str_replace('not ','',$handle);
				
				if($type == 'script') {
					unset(self::$available_scripts[$handle]);
				} else {
					unset(self::$available_stylesheets[$handle]);
				}

			// found an extra handle we do want
			} else {
				$defaults[] = $handle;
			}
		}
		
		return $defaults;
		
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * set_footer
	 * 
	 * @param string $contents	some JS to append to the page
	 */
	private static function set_footer($contents) {
		
		// go with default if nothing specified
		if(false == $contents && !empty(self::$footer_content)) {
			$contents = self::$footer_content;
		}
		
		// add to foot of page
		if(!empty($contents)) {
			self::$footer_content .= "<script>\n";
			self::$footer_content .= "$(function(){\n";
			self::$footer_content .= $contents;
			self::$footer_content .= "\n})\n</script>";
			
			// set the action to make this happen
			add_action('wp_footer', 'themewrangler_add_to_footer');
		}
		
	}
}

/**
 * Function with global scope accessible to WP
 * 
 * @return string 
 * @access public
 * @since 1.0
 */
function themewrangler_add_to_footer() {
	echo Themewrangler::$footer_content;
}