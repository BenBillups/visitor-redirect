<?php
/**
 * Plugin Name: Visitor Redirect
 * Description: This plugin redirects all visitors to the place of your choice unless they're logged in or you visit the login page.
 * Version: 0.5
 * Author: Ben Billups
 * Author URI: http://ioLanche.com/
 * License: GPL2
 */

//////////////////////////////////////////////////
// Redirect Function
/////////////////////////////////////////////////
function visitor_redirect() {
	//current page variable
    global $pagenow;
    //get redirect options
    $options = get_option( 'redirect_option' );
    
    if(!empty($options['301_checkbox'])) {
    	$status = '301';
    } else {
    	$status = false;
    }
	
	//redirect if not login page and user is not logged in
	if( empty($options['login_checkbox']) && !empty($options['redirect_link']) ) {
		if(!is_user_logged_in() && $pagenow != 'wp-login.php') {
			wp_redirect( $options['redirect_link'], $status );
		}
	} 
	//if they've check the login page box, send all non logged in users to the login page
	elseif ( !empty($options['login_checkbox']) ) {
		if(!is_user_logged_in() && $pagenow != 'wp-login.php') {
			wp_redirect( home_url('/wp-login.php'), $status );
		}
	}
}
add_action( 'wp', 'visitor_redirect' );

//////////////////////////////////////////////////
// Options Page
/////////////////////////////////////////////////
class VisitorRedirectSettings {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    
    /**
     * Start up
     */
    public function __construct(){
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
    
		// This page will be under "Settings"
		add_options_page(
			'Visitor Redirect', //page title
			'Visitor Redirect', //menu title
			'manage_options', 
			'redirect_option', //page 
			array( $this, 'create_admin_page' )
		);
    }

    /**
     * Options Page
     */
    public function create_admin_page() {
        // Set class property
        $this->voptions = get_option( 'redirect_option' );

        
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Visitor Redirect</h2>
            <?php //settings_errors(); ?>

            <form method="post" action="options.php">
            <?php
				settings_fields( 'visitor_redirect_group' );   
				do_settings_sections( 'redirect_option' );

				submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {

    	////page & option
		register_setting(
			'visitor_redirect_group', //option group
			'redirect_option', //option name
			array($this, 'redirect_sanitize')
		);
		add_settings_section(
			'visitor_redirect',
			'',
			array($this, 'print_redirect_info'),
			'redirect_option'
		);
		
		////settings fields
    	add_settings_field(
    		'redirect_link', //id
    		'Redirect Link', //Title
    		array($this, 'redirect_link_callback'), //callback
    		'redirect_option', //page
    		'visitor_redirect' //section
    	);
		add_settings_field(
			'login_checkbox', //id
			'Login Redirect', //Title
			array($this, 'login_redirect_callback'),
			'redirect_option', //page
			'visitor_redirect' //section
		);
		add_settings_field(
			'301_checkbox', //id
			'Permanent Redirect', //Title
			array($this, 'status_redirect_callback'),
			'redirect_option', //page
			'visitor_redirect' //section
		);
		
	} //end init

    /*
     * Callbacks
     */
    public function print_redirect_info() {
    	print '<p><em>Redirect all non-logged in users to the login page or a page of your choice. If you pick another page, you\'ll have to use the /wp-admin or /wp-login links to get in.</em></p>';
    }
    
    public function redirect_link_callback() {
    	$options = get_option('redirect_option');
		printf(
			"<input type='text' id='redirect_link' name='redirect_option[redirect_link]' value='%s' />", 
			isset( $options['redirect_link'] ) ? esc_attr( $options['redirect_link']) : ''
		);
    }
    public function login_redirect_callback() {
		$is_checked = checked( isset($this->voptions['login_checkbox']), true, false);
		
		printf(
			'<input type="checkbox" id="login_checkbox" name="redirect_option[login_checkbox]" %1$s />
			<label for="redirect_option[login_checkbox]">Send all non-logged in users to the login page. (Overrides any link set above.)</label><br />', 
			$is_checked
		);
    }
    
    public function status_redirect_callback() {
		$is_checked = checked( isset($this->voptions['301_checkbox']), true, false);
		
		printf(
			'<input type="checkbox" id="301_checkbox" name="redirect_option[301_checkbox]" %1$s />
			<label for="redirect_option[301_checkbox]">Make it a permanent (301) redirect. (Default is temporary 302 redirect.)</label><br />', 
			$is_checked
		);
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function redirect_sanitize( $input ) {
    	//validate all inputs
    	
		$output = array();  
		
		// Loop through each of the incoming options  
		foreach( $input as $key => $value ) {
			//if is video link, esc the url
		    if( isset( $input['redirect_link'] ) ) {  
		        $output[$key] = esc_url( $input[$key] );
		    }
			elseif( isset($input['login_checkbox']) || isset($input['301_checkbox']) ) {
				$output[$key] = true;
			}
		}
		
		// Return the array processing any additional functions filtered by this action  
		return apply_filters( 'redirect_sanitize', $output, $input );
    }

} //end class

if( is_admin() ) {
    $redirect_settings = new VisitorRedirectSettings();
}

?>
