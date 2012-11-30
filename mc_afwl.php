<?php 

/*
Plugin name: AJAX Favorite posts
Description: Enable users to mark their favorite posts for both logged in and anonymous users using AJAX. Works for any post type.
Author: Marian Cerny
Author URI: http://mariancerny.com

*/

class mc_afwl_plugin
{


// *******************************************************************
// ------------------------------------------------------------------
//					VARIABLES AND CONSTRUCTOR
// ------------------------------------------------------------------
// *******************************************************************


private $s_table_name;
private $s_cookie_name;

private $a_default_texts;

public function __construct()
{
	// ACTIONS - ENQUEUE SCRIPTS
	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
	// ACTIONS - AJAX BUTTON CLICK
	add_action('wp_ajax_button_click', array( $this, 'handle_click' ) );
	add_action('wp_ajax_nopriv_button_click', array( $this, 'handle_click' ) );
	
	// INITIALIZE VARIABLES
	global $wpdb;
	$this->s_table_name = $wpdb->prefix . "mc_afwl_wishlist";
	$this->s_cookie_name = "mc_afwl_watches_wishlist";
	
	$this->a_default_texts = array(
		'add' => 'Add to favourites',
		'remove' => 'Remove from favourites',
		'add_title' => 'Add to favourites',
		'remove_title' => 'Remove from favourites'
	);

	// CREATE WISHLIST QUERY
	$s_wishlist_sql = "CREATE TABLE $this->s_table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		iID int(11) NOT NULL,
		uID int(11) NOT NULL,
		UNIQUE KEY id (id)
	);";

	// CREATE DATABASE TABLES IF DON'T EXIST 
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($s_wishlist_sql);
}


// *******************************************************************
// ------------------------------------------------------------------
//					FRONT-END FUNCTIONS
// ------------------------------------------------------------------
// *******************************************************************

public function get_link( $i_item_id, $a_link_texts )
{	
	// GET DEFAULT LINK TEXTS
	$a_link_texts = array_merge( $this->a_default_texts, $a_link_texts );
	
	// MERGE DEFAULT AND 
	
	// GET CURRENT ITEM STATUS
	$b_is_added = $this->is_added( $i_item_id );
	
	// GET ACTION - 'REMOVE' OR 'ADD' DEPENDING ON CURRENT STATUS
	$s_action = ($b_is_added) ? 'remove' : 'add';

	// START LINK STRING
	$s_link = "<a ";
	
	// ADD HREF
	$s_link .= "href='#' ";
	
	// ADD ID	
	$s_link .= "id='afwl-button-".$i_item_id."' ";
	
	// ADD ID	
	$s_link .= "title='".$a_link_texts[$s_action.'_title']."' ";
	
	// ADD CLASS 
	$s_link .= "class='afwl-button ".$s_action."'";
	
	// FINISH LINK STRING
	$s_link .= ">" . $a_link_texts[$s_action] . "</a>";
	
	// ADD HIDDEN DATA TO LINK
	$s_link .= "<div class='afwl-button-hidden' style='display: none;'>";
	$s_link .= "<div class='add'>".$a_link_texts['add']."</div>";
	$s_link .= "<div class='remove'>".$a_link_texts['remove']."</div>";
	$s_link .= "<div class='add_title'>".$a_link_texts['add_title']."</div>";
	$s_link .= "<div class='remove_title'>".$a_link_texts['remove_title']."</div>";
	$s_link .= "</div>";
	
	return $s_link;
}

public function handle_click()
{
	// GET POST VARIABLES
	$i_item_id = $_POST['iid'];
	
	// CALL APPROPRIATE METHOD 
	if ( $this->is_added( $i_item_id ) )
	{	
		if ( is_user_logged_in() )		
			$this->remove_from_db(  $i_item_id );	
		else 
			$this->remove_from_cookie( $i_item_id );
	} else
	{ 
		if ( is_user_logged_in() )
			$this->add_to_db( $i_item_id );
		else 
			$this->add_to_cookie( $i_item_id );
	}
	
	exit;
}


public function get_count()
{
	if ( is_user_logged_in() )		
		return $this->get_count_from_db();	
	else 
		return $this->get_count_from_cookie();
}


public function get_items()
{
	if ( is_user_logged_in() )		
		return $this->get_items_from_db();	
	else 
		return $this->get_items_from_cookie();
}


public function clear()
{
	$a_items = $this->get_items();
	foreach ( $a_items as $i_item )
		if ( is_user_logged_in() )
			$this->remove_from_db( $i_item );
		else
			$this->remove_from_cookie( $i_item );
}


// *******************************************************************
// ------------------------------------------------------------------
//					BACK-END FUNCTIONS
// ------------------------------------------------------------------
// *******************************************************************


function enqueue_styles_and_scripts()
{
	// GET PLUGIN BASE DIR
	$s_script_base = plugin_dir_url( __FILE__ );
	// ENQUEUE MAIN SCRIPT
	wp_enqueue_script(
		'mc_afwl_script', 
		$s_script_base.'/mc_afwl_script.js', 
		array( 'jquery' ) 
	);
	// CREATE AJAX VARIABLES USED BY SCRIPT
	$a_ajax_vars = array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'loader_src' => $s_script_base . 'ajax-loader.gif',
		'default_texts' => $this->a_default_texts,
	);
	// PASS AJAX VARS TO SCRIPT
	wp_localize_script( 
		'mc_afwl_script', 
		'afwl_ajax_vars', 
		$a_ajax_vars
	);
}


private function is_added( $i_item_id )
{
	if ( is_user_logged_in() )
		return $this->is_added_in_db( $i_item_id );
	else
		return $this->is_added_in_cookie( $i_item_id );
}


// **********************************************
// ----------------------------------------------
//				DATABASE FUNCTIONS
// ----------------------------------------------
// **********************************************


private function add_to_db( $i_item_id )
{
	global $wpdb;
	// CREATE AN ARRAY OF VALUES TO BE INSERTED
	$a_values = array(
		'iID' => $i_item_id,
		'uID' => get_current_user_id(),
	);
	// INSERT VALUES	
	$wpdb->insert( $this->s_table_name, $a_values );
}


private function remove_from_db( $i_item_id )
{
	global $wpdb;
	// GET USER ID
	$i_uid = get_current_user_id();
	// CREATE AND EXECUTE QUERY	
	$s_query = "DELETE FROM {$this->s_table_name} WHERE uID={$i_uid} AND iID={$i_item_id};";
	$wpdb->query( $wpdb->prepare( $s_query ) );
}


private function get_count_from_db()
{
	global $wpdb;
	// GET USER ID
	$i_uid = get_current_user_id();
	// CREATE SELECTION QUERY
	$s_query = "SELECT COUNT(*) FROM {$this->s_table_name} WHERE uID={$i_uid}";
	
	// GET THE VALUES	
	$i_count = $wpdb->get_var( $wpdb->prepare( $s_query ) );
	$s_result_string = "<span class='afwl-count'>{$i_count}</span>";
	return $s_result_string;
}


private function get_items_from_db()
{
	global $wpdb;
	// GET USER ID
	$i_uid = get_current_user_id();
	// CREATE SELECTION QUERY
	$s_query = "SELECT * FROM {$this->s_table_name} WHERE uID={$i_uid}";
	
	// GET THE VALUES	
	$a_items = $wpdb->get_col( $wpdb->prepare( $s_query ), 1 );
	return $a_items;
}


private function is_added_in_db( $i_item_id )
{ 
	// BUILD SQL QUERY
	$s_sql = "SELECT iID FROM {$this->s_table_name} WHERE uID=" . get_current_user_id() . " AND iID={$i_item_id}";
	// CHECK IF ANY RECORDS ARE RETURNED
	global $wpdb;
	$result = $wpdb->get_var( $wpdb->prepare( $s_sql ) );
	return !empty ( $result );
}


// **********************************************
// ----------------------------------------------
//				COOKIE FUNCTIONS
// ----------------------------------------------
// **********************************************


private function add_to_cookie( $i_item_id )
{
		
	// GET THE UNIX TIMESTAMP OF ONE WEEK FROM NOW
	$i_next_week = time()+60*60*24*7; 
	
	// GET CURRENT COOKIE SIZE
	$i_cookie_size = count( $_COOKIE[$this->s_cookie_name] );
	
	// SET A NEW COOKIE
	setcookie( $this->s_cookie_name.'['.$i_cookie_size.']', $i_item_id, $i_next_week, '/' );
}


private function remove_from_cookie( $i_item_id )
{	
	// GET COOKIE ARRAY INDEX
	$i_item_index = array_search( $i_item_id, $_COOKIE[$this->s_cookie_name] );
	// DELETE COOKIE
	setcookie( $this->s_cookie_name.'['.$i_item_index.']', '', 0, '/' );	
}


private function get_count_from_cookie()
{
	// GET COOKIE ARRAY SIZE
	$i_count = count($_COOKIE[$this->s_cookie_name]);
	// BUILD RESULT STRING
	$s_result_string = "<span class='afwl-count'>{$i_count}</span>";
	return $s_result_string;
}


private function get_items_from_cookie()
{	
	// RETURN COOKIE ARRAY
	return $_COOKIE[$this->s_cookie_name];
}


private function is_added_in_cookie( $i_item_id )
{	
	// GET COOKIE ARRAY
	$a_cookie = $_COOKIE[$this->s_cookie_name];
	// CHECK IF COOKIE IS EMPTY AND IF CONTAINS ITEM
	return ( !empty( $a_cookie ) && in_array( $i_item_id, $a_cookie ) );
}

}
	
	
// *******************************************************************
// ------------------------------------------------------------------
// 						FUNCTION SHORTCUTS
// ------------------------------------------------------------------
// *******************************************************************

// GLOBALIZE AND INITIALIZE VARIABLE
global $mc_afwl_plugin; 
$mc_afwl_plugin = new mc_afwl_plugin();

// WISHLIST LINK
function mc_afwl_get_link( $i_item_id, $a_link_texts = array() )
{
	global $mc_afwl_plugin;
	return $mc_afwl_plugin->get_link( $i_item_id, $a_link_texts );
}
// WISHLIST COUNT
function mc_afwl_get_count()
{
	global $mc_afwl_plugin;
	return $mc_afwl_plugin->get_count();
}
// WISHLIST ITEMS
function mc_afwl_get_items()
{
	global $mc_afwl_plugin;
	return $mc_afwl_plugin->get_items();
}
// CLEAR WISHLIST
function mc_afwl_clear()
{
	global $mc_afwl_plugin;
	return $mc_afwl_plugin->clear();
}
// BUTTON CLICK
function mc_afwl_handle_click()
{
	global $mc_afwl_plugin;
	return $mc_afwl_plugin->handle_click();
}


?>