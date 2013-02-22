
# Theme Wrangler for Wordpress

A toolkit for WordPress Themes in the form of a PHP class. Clears out surplus markup in the header and footer and also acts as a management tool for loading stylesheets and javascript.

Theme Wrangler simplifies creation of extremely clean Theme markup and encourages a more [Object-oriented][1] approach to Theme development.


## Installation


### System requirements

Tested on WordPress 3 only. As far as we can see it should work right back to version 1. Let us know if not.

### Installing

Place themewrangler.class.php in your theme folder.

Add the following line to your functions.php file:

` require_once(TEMPLATEPATH.'/themewrangler.class.php');`

Below this you must define the default configuration . This is done by calling the function

` Themewrangler::set_defaults(  $settings);`
Where $settings is an array as per [reference below][3].

### Usage

Place a call to Themewrangler::setup_page() early in your templates. See detailed documentation below.

## Public methods

### set_defaults()

This method should only be used in your functions.php file

#### Usage

`Themewrangler::set_defaults( array $settings);`

#### Parameters

##### $settings

An array of settings. The keys of the array should correspond to the options listed below.

##### available_scripts

An array of arrays representing all scripts that will be available to your templates.

Keys of the array relate to the handles of your scripts

 Index 0 of the value array is the full path to the script (required)

 Index 1 of the value array is the version number of the script (required)

##### default_scripts

An array of script handles that will be loaded by default (each must be present in available_scripts above)

##### available_stylesheets

An array of arrays representing all CSS files that will be available to your templates.

Keys of the array relate to the handles of your stylesheets.

 index 0 of the value array is the full path to the stylesheet (required)

 index 1 of the value array is the version number of the stylesheet (required)

 index 2 of the value array is the media for the stylesheet (optional. Default 'all')

##### default_stylesheets

An array of stylesheets handles that will be loaded by default (each must be present in available_styles above)

##### remove\_from\_head

An array of filter handles that will be removed from the  tag using [remove_filter()][4].

##### deregister_scripts

An array of script handles that should be removed from the default WordPress load list. This is particularly useful for removing the default JQuery version.

#### Example

    $settings = array(

          'available_scripts' =&gt; array(
              'jquery' =&gt; array('http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js','1.6'),
              'global' =&gt; array('/assets/js/global.js',1),
              'cycle' =&gt; array('/assets/js/jquery.cycle.lite.js','1.3'),
          ),

          'default_scripts' =&gt; array('jquery'),

          'available_stylesheets' =&gt; array(
              'default' =&gt; array('/assets/css/styles.css',1),
              'blog' =&gt; array('/assets/css/blog.css',1),
              'print' =&gt; array('/assets/css/addons.css',1,'print'),
          ),

          'default_stylesheets' =&gt; array('default','print'),

          'remove_from_head' =&gt; array(
              'rsd_link',
              'wlwmanifest_link',
              'wp_generator',
              'rel_canonical',
              'index_rel_link',
              'parent_post_rel_link',
              'start_post_rel_link',
              'adjacent_posts_rel_link',
              'adjacent_posts_rel_link_wp_head',
              'wp_shortlink_wp_head',
              'wp_shortlink_wp_header',
              'feed_links',
              'feed_links_extra',
          ),

          'deregister_scripts' =&gt; array('jquery','l10n')

      );

    Themewrangler::set_defaults( $settings );

## setup_page()


The method makes Theme Wrangler work. It should only be used by your template files and be inserted once, before any call to get\_header() or wp\_head().


### Usage

    Themewrangler::setup_page( [mixed $styles] [,mixed $scripts] [, string $page_script]   );

### Parameters

#### $styles

This allows you to adjust the styles defined by [Themewrangler::set_defaults()][3] for this page.

Omit or Set to true to include default stylesheets.

Set to false to include no stylesheets.

Include a string of pipe-separated style handles to include extras from those available. Prefix a handle with 'not ' to exclude it. For example:

    Themewrangler::setup_page('blog|not print|extra1')

will include the blog and extra1 stylesheets but exclude the print stylesheet.

#### $scripts

This allows you to adjust the scripts defined by [Themewrangler::set_defaults()][3] for this page.

Omit or Set to true to include default scripts.

Set to false to include no scripts.

Include a string of pipe-separated script handles to include extras from those available. Prefix a handle with 'not ' to exclude it. For example:

    Themewrangler::setup_page(true,'cycle|not global|extra1')

will include default styles, include the cycle and extra1 scripts but exclude the global script.

#### $page_script

A string of javascript to be added to the bottom of the page. The string will be wrapped in a JQuery loader as follows:

    $(function(){ $page_script })

## Template examples

The functions.php file for these templates use the [sample defaults][3] set above.

### Basic page

This example uses all defaults styles and scripts.

     <?php
     Themewrangler::setup_page();

     get_header(); ?>

     ... template content in here as normal ...

     <?php get_footer(); ?>

### Home page

This template uses the default stylesheets and scripts as well as the cycle script. It also applies the jquery cycle plugin to a block of content.

    <?php
    Themewrangler::setup_page( true,
                               'cycle',
                               '$("#gallery").cycle({timeout: 6000});'
                               );
    get_header(); ?>

    ... template content in here as normal ...

    <?php get_footer(); ?>

## Extending Theme Wrangler


A typical WordPress theme will include all sorts of code in functions.php. It can quickly become messy and much of the functionality is better suited to a separate class.


A useful technique is to never use the Themewrangler class directly. Instead, start by creating your own class. For example this site uses dmlogic.class.php which looks like this:

    require_once(TEMPLATEPATH.'/themewrangler.class.php');

    class Dmlogic extends Themewrangler {

        private static $menu = array( 'blog','add-ons' );

        // -----------------------------------------------------------------

        /**
         * top_menu
         *
         * Generate a simple list menu with highlighter
         * based on first segment of URL
         *
         * @return string
         */
        public static function top_menu() {

            $segments = explode('/',trim($_SERVER['REQUEST_URI'],'/'));

            $out = '&lt;menu>';

            foreach(self::$menu as $item) {
                $sel = ($segments[0] == $item) ? 'class="selected"' : '';
                $out .= '&lt;li '.$sel.'>&lt;a href="/'.$item.'">'.$item."&lt;/a>&lt;/li>\n";
            }

            $out .= '&lt;/menu>';

            return $out;

        }

        // -----------------------------------------------------------------

        /**
         * parse_documentation
         *
         * @param string $str   value from get_the_content()
         * @param array $args   array of markers and replacement values from custom fields
         */
        public static function parse_documentation($str,$args) {

            foreach($args as $k => $v) {
                $str = str_replace("[$k]",$v,$str);
            }

            echo $str;
        }
}

As you can see, we have a couple of helper functions to manipulate some content and generate a small navigation menu.

In our theme functions.php file we include dmlogic.class.php instead of themewrangler.class.php and all calls to Themewrangler::setup_page() are replaced by Dmlogic::setup_page() and so on.

By using this approach we benefit from a neat set of template tools and when the time comes to upgrade themewrangler.class.php we simply replace the file.