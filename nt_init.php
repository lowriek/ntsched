<?php
/**
 * @package Racketeers_Scheduler
 * @version 0.0
 */
/*
Plugin Name: Racketeers 
Plugin URI: 
Description: This is a plugin to handle scheduling for the racketeers
Version: 0.0
Author URI: 
*/


global $nt_db_version;
$nt_db_version = "1.0";
global $debug;
$debug = 1;

/**
 * nt_install() - creates the match table
 **/
function nt_install(){

	global $wpdb;
	global $nt_db_version;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

/* kbl todo - add group table, group info
    Name
    Organizer
    Day of Week
    Time
    Duration
    --  Note Day of Week should inform date selection for matches
     */

    $courtTimes[] = array (
    	"7am",
    	"8am",
    	"9am",
    	"10am",
    	"11am",
    	"12pm",
    	"1:30pm",
    	"2:30pm",
    	"3:30pm",
    	"4:30pm",
    	"5:30pm",
    	"6:30pm",
    	"7:00pm",
    	"7:30pm",
    	"8:00pm",
    	"8:30pm",
    	"9:00pm",
    	"9:30pm"
    );
	$group_table_name = $wpdb->prefix . "group";    
	$sql = 	"CREATE TABLE $group_table_name(
		groupID    int not null auto_increment,
		organizerID int,
		groupDay int,   /*(0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday) */
		groupTime int,  /* see array above */
		groupMatchDuration ENUM ('sixty', 'ninety'),
		PRIMARY KEY(groupID)
	) engine = InnoDB;";
    dbDelta( $sql );

    
   	$match_table_name = $wpdb->prefix . "match";    
	$sql = 	"CREATE TABLE $match_table_name(
		matchID    int not null auto_increment,
		matchDate date,
		matchTime int,   /* KBL todo - create array of possible court times */
		matchHost int,
		matchPlayer1 int,
		matchPlayer2 int,
		matchPlayer3 int,
		hostStatus ENUM('confirmed', 'unconfirmed', 'needsub'),
		player1Status ENUM('confirmed', 'unconfirmed', 'needsub'),
		player2Status ENUM('confirmed', 'unconfirmed', 'needsub'),
		player3Status ENUM('confirmed', 'unconfirmed', 'needsub'),
		PRIMARY KEY(matchID)
	) engine = InnoDB;";
    dbDelta( $sql );
   add_option( "nt_db_version", $nt_db_version );
}
register_activation_hook( __FILE__, 'nt_install' );


/** 
 * nt_deactivate() - cleans up when the plugin is deactived. 
 * delete database tables.  
 *
 * JBL - You and I need to find out more about updates.
 **/
function nt_deactivate()
{
    global $wpdb; 
    
	/** drop this first before deleting event **/    
	$match_table_name = $wpdb->prefix . "match";    
    $sql = "DROP TABLE IF EXISTS $match_table_name;";
    $wpdb->query( $sql );

	$group_table_name = $wpdb->prefix . "group";      
    $sql = "DROP TABLE IF EXISTS $group_table_name;";
    /* kbl todo - add group table */

    $wpdb->query( $sql );
}
register_deactivation_hook( __FILE__, 'nt_deactivate');

/**
 * Add stylesheets
 **/
function safely_add_stylesheet() {
	wp_enqueue_style( 'prefix-style', plugins_url('css/ntstyle.css', __FILE__) );
}
//add_action( 'wp_enqueue_scripts', 'safely_add_stylesheet' ); /* maybe later */

/**
 * supposedly the correct way to load jquery 
 **/
function load_jquery() {
	//wp_enqueue_style( 'jquery-style', "http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css" );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-accordion' );
}
add_action( 'wp_enqueue_scripts', 'load_jquery' ); 
/**
 * load_nt() - this loads jquery for nt form validation
 **/
function load_nt(){
    wp_enqueue_script( 'nt_script', plugins_url( 'js/formvalidate.js' , __FILE__ ), array(), null, true);
    wp_enqueue_script( 'nt_script', plugins_url( 'js/reportsupport.js' , __FILE__ ), array(), null, true);
}
add_action( 'wp_enqueue_scripts', 'load_nt' ); 

/**
 * nt_admin_init() - do initialization needed for nt admin pages
 **/
function nt_admin_init() {
    /* Register our script. */
	load_jquery();
	wp_enqueue_script( 'nt_script', plugins_url( 'js/reportsupport.js' , __FILE__ ), array(), null, true);
}
add_action( 'admin_init', 'nt_admin_init' );

/**
 * These are the functions to wire in the shortcodes
 **/ 

include 'nt_admin.php';
add_action( 'show_user_profile', 'racketeers_extra_user_profile_fields' );
add_action( 'edit_user_profile', 'racketeers_extra_user_profile_fields' );
// add_action( 'register_form', 'ifcrush_register_form' );

// include 'ifcrush_frat.php';  /** This has all the Frat table support **/
// add_shortcode( 'ifcrush_frat',   'ifcrush_frat' );

// include 'ifcrush_pnm.php';  /** This has all the Rushee table support **/
// add_shortcode( 'ifcrush_pnm',   'ifcrush_pnm' );

// include 'nt_admin.php';  /** This has all the admin support **/
add_action( 'admin_menu', 'nt_admin_menu' );

/* wire in the match stuff */
include 'nt_match.php';
add_shortcode( 'nt_match_hub',   'nt_match_hub' );


/**
 * Redirect user after successful login. - this needs to be after the include
 * for nt_user_support.php because it uses the user functions
 *
 * @param string $redirect_to URL to redirect to.
 * @param string $request URL the user is coming from.
 * @param object $user Logged users data.
 * @return string
 */

function my_login_redirect( $redirect_to, $request, $user ) {
	//is there a user to check?
	global $user;
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		//check for admins
		if ( in_array( 'administrator', $user->roles ) ) {
			// redirect them to the default place
			return $redirect_to;
		} else if ( is_user_a_pnm( $user ) ) {
			return home_url("/?page_id=66");  // HACK HACK HACK fix the number
		} else if ( is_user_an_rc( $user ) ) {
			return home_url("/?page_id=64"); // HACK HACK HACK fix the number
		} 
	} else {
		return $redirect_to;
	}
}
//add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );