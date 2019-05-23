<?php
/*
Plugin Name: WP Custom CSS
Plugin URI: https://www.tipsandtricks-hq.com/wordpress-custom-css-plugin-6413
Description: This plugin allows you to add custom css code to your site negating the need to modify themes and plugins for CSS tweaks.
Author: Tips and Tricks HQ, WPSolutions-HQ
Version: 1.4
Author URI: https://www.tipsandtricks-hq.com/
*/

if (!defined('ABSPATH'))exit; //Exit if accessed directly

if(!class_exists('WPCustomCSSInserter')) 
{
    class WPCustomCSSInserter 
    {
        /*
         * Declare keys here as well as our tabs array which 
         * is populated when registering settings
         */
        private $edit_wpcci_settings_page_key = 'edit_wpcci_settings_page';
        private $wpcci_options_key = 'wpcci_plugin_options';
        private $wpcci_settings_tabs = array();

        function __construct() {
            $this->define_constants();
            $this->loader_operations();
        }

        function define_constants() {
            global $wpdb;
            define('WPCCI_PLUGIN_DB_VERSION', '1.0');
            define('WPCCI_PLUGIN_PATH', dirname(__FILE__));
            define('WPCCI_PLUGIN_URL', plugins_url('',__FILE__));
        }

        function loader_operations(){
            add_action('plugins_loaded', array( &$this, 'wpcci_execute_plugins_loaded_operations'));
        }

        function wpcci_execute_plugins_loaded_operations()
        {	
            add_action( 'init', array( &$this, 'load_settings' ) );
            add_action( 'init', array( &$this, 'wpcci_load_libraries' ) );
            add_action( 'admin_init', array( &$this, 'register_wpcci_settings_page' ) );
            add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
            add_filter('plugin_action_links', array( &$this, 'wp_custom_css_add_settings_link' ), 10, 2 );
        }

        function wp_custom_css_add_settings_link($links, $file)
        {
            if ($file == plugin_basename(__FILE__)){
                    $settings_link = '<a href="options-general.php?page=wpcci_plugin_options">Settings</a>';
                    array_unshift($links, $settings_link);
            }
            return $links;
        }
        
        function add_custom_css_file()
        {
            global $blog_id;
            $wp_custom_css_settings = get_option('wpcci_settings');
            if($wp_custom_css_settings['blog_id'] == $blog_id){
                $css_code = $wp_custom_css_settings['css_code'];
            }else{
                $css_code = NULL;
            }

            //Only include the custom CSS file for the relevant blog_id AND if the CSS custom code isn't blank
            if ($css_code)
            {
                // Add to page html source
                echo "\n<!-- WP Custom CSS Plugin - http://www.tipsandtricks-hq.com/wordpress-custom-css-plugin-6413 -->\n<style type=\"text/css\">\n@import url('".$this->get_css_url()."?".filemtime($this->get_css_path())."');\n</style>\n<!-- WP Custom CSS End -->\n";
            }
        }

        function get_css_path()
        {
            global $blog_id; 
            //Get multi-site blog_id if applicable
            $cssid = ( $blog_id > "1" ) ? $cssid = "_blog_id-".$blog_id : $cssid = null;
            $css_path = plugin_dir_path(__FILE__)."wp-custom-css".$cssid.".css";
            return $css_path;
        }            

        function get_css_url()
        {
            global $blog_id; 
            //Get multi-site blog_id if applicable
            $cssid = ( $blog_id > "1" ) ? $cssid = "_blog_id-".$blog_id : $cssid = null;
            $css_path = plugin_dir_url(__FILE__)."wp-custom-css".$cssid.".css";
            return $css_path;
        }


        /*
         * Loads tab settings from
         * the database into their respective arrays. Uses
         * array_merge to merge with default values if they're
         * missing.
         */
        function load_settings() 
        {
            $this->edit_wpcci_settings_page = (array) get_option( $this->edit_wpcci_settings_page_key );

            // Merge with defaults
            $this->edit_wpcci_settings_page = array_merge( array(
                    'edit_wpcci_option' => 'WP Custom CSS Settings Page'
            ), $this->edit_wpcci_settings_page );
        }

        function wpcci_load_libraries()
        {
            add_action('wp_head', array( &$this, 'add_custom_css_file'));
            wp_enqueue_style('wpcci-admin-css', WPCCI_PLUGIN_URL. '/css/wp-custom-css-admin-styles.css');
            wp_enqueue_style('wpcci-codemirror-css', WPCCI_PLUGIN_URL. '/lib/codemirror/codemirror.css');
            wp_register_script('wpcci-codemirror-js', WPCCI_PLUGIN_URL. '/lib/codemirror/codemirror.js', array('jquery')); // We will enqueue this in the menu page

        }


        /*
         * Registers the display templates page via the Settings API,
         * appends the setting to the tabs array of the object.
         */
        function register_wpcci_settings_page() {
                $this->wpcci_settings_tabs[$this->edit_wpcci_settings_page_key] = 'WP Custom CSS Settings';
                register_setting( $this->edit_wpcci_settings_page_key, $this->edit_wpcci_settings_page_key );
        }

        /******************************************************************************
         * Now we just need to define an admin page.
         ******************************************************************************/

        /*
         * Called during admin_menu, adds an options
         * page under Settings 
        */

        function add_admin_menus(){	    
            $wpcci_page = add_options_page('Custom CSS', 'Custom CSS', 'manage_options', $this->wpcci_options_key, array(&$this, 'wpcci_plugin_option_page'));
            add_action('admin_head-'. $wpcci_page, array(&$this, 'wpcci_load_syntax_items'));
        }

        function wpcci_load_syntax_items(){	    
            //Add the JS library for codemirror editor
            //wp_enqueue_script('wpcci-codemirror-js');
            ?>
            <script language="javascript" src="<?php echo WPCCI_PLUGIN_URL. '/lib/codemirror/codemirror.js'; ?>"></script>
            <script language="javascript" src="<?php echo WPCCI_PLUGIN_URL. '/lib/codemirror/css.js'; ?>"></script>
            <?php
        }


        /*
         * Plugin Options page rendering goes here, checks
         * for active tab and replaces key with the related
         * settings key. Uses the plugin_options_tabs method
         * to render the tabs.
         */
        function wpcci_plugin_option_page() {
            $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->edit_wpcci_settings_page_key;
            ?>
            <div class="wrap">
                    <?php 
                    $this->plugin_options_tabs();
                    if ($tab == $this->edit_wpcci_settings_page_key)
                    {
                        include_once('wp-custom-css-settings.php');
                        displayCustomCSSSettings();				
                    }
                    ?>
            </div>
            <?php
        }

        function current_tab() {
                $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->edit_wpcci_settings_page_key;
                return $tab;
        }

        /*
         * Renders our tabs in the plugin options page,
         * walks through the object's tabs array and prints
         * them one by one. Provides the heading for the
         * plugin_options_page method.
         */
        function plugin_options_tabs() {
                $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->edit_wpcci_settings_page_key;

                echo '<h2 class="nav-tab-wrapper">';
                foreach ( $this->wpcci_settings_tabs as $tab_key => $tab_caption ) {
                        $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
                        echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->wpcci_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
                }
                echo '</h2>';
        }

    } //end class
}
$GLOBALS['wpcci_plugin'] = new WPCustomCSSInserter();
