<?php
/**
 **  This file contains all the support functions for the table match.
 **  Matches can only be created by hosts (aka match organizers).
 **  The match table is created with the SQL below.
 **  Players are users.
 **
 **/
 
function nt_match_create_table ( $match_table_name, $group_table_name) {
	
	$sql = 	"CREATE TABLE $match_table_name(
		nt_match_id    int not null auto_increment,
		nt_group_id    int not null,    /* matches must be associated with one group */
		nt_match_date date,
		nt_match_host int,
		nt_match_player_1 int,
		nt_match_player_2 int,
		nt_match_player_3 int,
		nt_host_status ENUM('confirmed', 'unconfirmed', 'needsub'),
		nt_match_player_1_status ENUM('confirmed', 'unconfirmed', 'needsub'),
		nt_match_player_2_status ENUM('confirmed', 'unconfirmed', 'needsub'),
		nt_match_player_3_status ENUM('confirmed', 'unconfirmed', 'needsub'),
		FOREIGN KEY( nt_group_id ) references $group_table_name( nt_group_id ),
		PRIMARY KEY( nt_match_id )
	) engine = InnoDB;";
    dbDelta( $sql );

}
/**  nt_match_delete_table()
 **  matching delete function for match table
 **/
function nt_match_delete_table( $match_table_name ) {
	global $wpdb; 

    $sql = "DROP TABLE IF EXISTS $match_table_name;";
    $wpdb->query( $sql );
}

 function nt_match_hub(  ) {
		global $debug;

		nt_match_handle_form();

		nt_display_matches();
}
/** 
 * Display matches for a group
 * Args - group name
 *
 **/
function nt_display_matches( /* $group_name */ ) {

	global $debug;
	global $wpdb;

	$match_table_name = $wpdb->prefix . constant( "MATCH_TABLE_NAME" );
 
 	$query = "SELECT * FROM $match_table_name";
	$allmatches = $wpdb->get_results( $query );

	nt_create_match_table_header(); 
	nt_create_match_add_row( /* $group_name */ );

	if ( $allmatches ) {
		foreach ( $allmatches as $thismatch ) {
			nt_create_match_table_row( $thismatch );
		}
	} else { 
		?><h3>No matches.  Add one!</h3><?php
	}
			
	nt_create_match_table_footer(); // end the table
}

/**
 * Matches are displayed with three action buttons:
 * Update, Delete, and Show Players. 
 * This function is the form handler for all three.
 * This function is going to add a match for the passed $group_letters
 * This function is called from nt_??? with $group_name 
 **/
function nt_match_handle_form( /* $group_name */ ) { 


	if ( ! isset( $_POST['match_action'] ) ) return;


	global $debug;
	if (  $debug ){
			echo "[nt_match_handle_form] ";
			echo "<pre>"; print_r( $_POST ); echo "</pre>";
	}

	/** Pull common data out of the form, get specific data in handlers if necessary **/
	$thismatch = array( 
				/** match_id will be null on insert **/
				'match_id'		=>  ( isset( $_POST[ 'nt_match_id' ] ) ? $_POST[ 'nt_match_id' ]: "" ),
				'match_date' 	=>  $_POST[ 'nt_match_date' ],

	); // put the form input into an array


	switch ( $_POST[ 'nt_match_action' ] ) {
		case "Update Match":
			nt_update_match( $thismatch );
			break;
			
		case "Delete Match":
			nt_delete_match( $thismatch );
			break;
			
		case "Add Match":
			nt_add_match(  );
			break;

		case "Show Players":
			nt_show_match_players( $thismatch );
			break;
			
		default:
			echo "[nt_match_handle_form]: bad action";
	}
} 


/**************************/

/** Event_handler_form helpers - these are CRUD for DB */
function nt_add_match( $thismatch ) {
	global $wpdb;
	global $debug;

	if ( ! $debug ){
			echo "[nt_add_match] ";
	}

	$table_name = $wpdb->prefix . constant( "MATCH_TABLE_NAME" );
	$rows_affected = $wpdb->insert( $table_name, $thismatch );
	
	if ( 0 == $rows_affected ) {
		echo "INSERT ERROR for " . $thismatch[ 'nt_match_date' ] . $thismatch[ 'nt_group_id' ];
		if ( $debug ){
			echo "[nt_add_match] Fail ";
			echo "<pre>"; print_r( $_POST ); echo "</pre>";
		}
	}

	return $rows_affected;

} // adds a match to the table if addMatch is tagged

/* adds a single match at the correct date an time for this group.
 * the date/time will be the next (in the future) date/time of the standing group
 * meeting time.  The match will be tagged 'placeholder'.  An organizer can 
 * change the status to active if the the match is a real.  
 * Consider taggins 'lastmatch' or something like that, but we can use select
 * to find the last match.
 *
 * How to do this....
 * 
 */
function nt_add_match_placeholder ( $thisgroup ) {

	global $debug;
	if ( $debug ){
		echo "[nt_add_match_placeholder] ";
		echo "<pre>"; print_r( $thisgroup ); echo "</pre>";
	}
			//groupDay int,   (0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday) 

	switch ( $thisgroup['nt_group_day'] ){
		case 0:	
			$next_match_unix_timestamp = strtotime( "next Monday"); 
			break;
		case 1:	
			$next_match_unix_timestamp = strtotime( "next Tuesday"); 
			break;
		case 2:	
			$next_match_unix_timestamp = strtotime( "next Wednesday"); 
			break;
		case 3:	
			$next_match_unix_timestamp = strtotime( "next Thursday"); 
			break;
		case 4:	
			$next_match_unix_timestamp = strtotime( "next Friday"); 
			break;
		case 5:	
			$next_match_unix_timestamp = strtotime( "next Saturday"); 
			break;
		case 6:	
			$next_match_unix_timestamp = strtotime( "next Sunday"); 
			break;
		default: 
			// add error output ?
			break;
	}
	$next_match_date =  date( "Y-m-d", $next_match_unix_timestamp );

	$thismatch = array( 
				/** match_id will be null on insert **/
				'nt_match_date' 	=>  $next_match_date,
				'nt_group_id' 		=>  $thisgroup['nt_group_id']
	); 
	// now I want to insert the placeholder into the db...

	return nt_add_match( $thismatch ) ;
}

function find_match_dates( $thismonth, $thisyear, $groupiddayofweek){
	// find today's date
	// get the month
	// figure out what the first day of the week the first day of this month is
	// find the day of the week for the groupid

	// so now, find the first groupid day of the week, and push it into an array.
	// find the next groupid day of the week, and push it into an array.
	// ... and so on, until you get to the end of the month.

	// return the array.
}
/*
$dayofweek = date('w', strtotime($date));
$result    = date('Y-m-d', strtotime(($day - $dayofweek).' day', strtotime($date)));
*/
function nt_update_match( $thismatch ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . constant( "MATCH_TABLE_NAME" );
	$where = array( 'nt_match_id' => $thismatch[ 'nt_match_id' ] );
	$wpdb->update( $table_name, $thismatch, $where );
} // updates a match with a matching matchID if updateMatch is tagged

function nt_delete_match( $thismatch ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . constant( "MATCH_TABLE_NAME" );
	$wpdb->delete( $table_name, $thismatch );
} // deletes a match if deleteMatch is tagged

function nt_show_match_players ( $thismatch ) {
	
}

/** 
 ** create_match_table_header()
 ** This function creates the header div for the list of matches.
 **/
function nt_create_match_table_header() {
	?>
		<div id="match_error"></div>
		<div class="nttable">
			<div class="nttablerow">
				<div class="nttablecellnarrow">Date</div>
				<div class="nttablecellnarrow">Title</div>
				<div class="nttablecellauto"></div>
			</div>
	<?php
}
function nt_create_match_table_footer() {
	?></div><?php
}

/**
 ** create match add row()
 ** This function creates a row in the table with a form to add a match
 **  When you add a match, you add data, time, title.  Players add themselves later.
 **  Each match must have a groupID as all matches must be associated with one group.
 **  KBL TODO - how to find groupID?
 **/
function nt_create_match_add_row() {
	?>
		<div class="ntaddrow">
			<form method="post" class="matchForm">
				<div class="nttablecellnarrow">
					<!-- <input type="text" name="match_date" id="addDate" class="datepicker" value="select date"> -->
				</div>
				<div class="nttablecellauto">
					<input type="submit" name="nt_match_action" id="addMatchButton" value="Add Match"/>
				</div>
			</form>
		</div><!-- end nttableaddrow -->
	<?php
}

/**
 ** create match_table_row
 ** this function creates one row of the list of matches.
 ** Dump the match passed with options to change match details.
 ** KBL TODO - we may dump players here
 **/
function nt_create_match_table_row( $thismatch ) {
	?>
		<div class="nttablerow">
			<form method="post" class="matchForm">
				<div class="matchtablecellnarrow">
					<input type="text" name="nt_match_date" class="datepicker" value="<?php echo $thismatch->nt_match_date; ?>" />
					<input type="hidden" name="nt_match_id" value="<?php echo $thismatch->nt_match_id; ?>" />
				</div>		
				<div class="nttablecellauto">
					<input type="submit" name="nt_match_action" value="Update Match"/>
					<input type="submit" name="nt_match_action" value="Delete Match"/>
					<input type="submit" name="nt_match_action" value="Show Players"/>
				</div>
			</form>
		</div>
	<?php
}
?>