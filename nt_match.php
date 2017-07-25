<?php
/**
 **  This file contains all the support functions for the table match.
 **  Matches can only be created by hosts (aka match organizers).
 **  The match table is created with the SQL below.
 **  Players are users.
 **
 **  KBL -TODO add groups later
 **
 ** CREATE TABLE $match_table_name(
		matchID    int not null auto_increment,
		matchDate date,
		matchTime int,
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
 **/
 
 function nt_match_hub(  ) {
		global $debug;

		/* handle form request if pending */
		if ( isset( $_POST['action'] ) ) {
			nt_match_handle_form();
		} 

		nt_display_matches();
}
/** 
 * Display matches for a group
 * Args - group name
 *
 **/
function nt_display_matches( /* $group_name */ ) {

	global $wpdb;

	$match_table_name = $wpdb->prefix . "match";
	/** KBL TODO - add group names SELECT * FROM $match_table_name where ID='$_letters'";  **/
	$query = "SELECT * FROM $match_table_name";
	$allmatches = $wpdb->get_results( $query );

	create_match_table_header(); 
	create_match_add_row( /* $group_name */ );

	if ( $allmatches ) {
		foreach ( $allmatches as $thismatch ) {
			create_match_table_row( $thismatch );
		}
	} else { 
		?><h3>No matches.  Add one!</h3><?php
	}
			
	create_match_table_footer(); // end the table
}

/**
 * Matches are displayed with three action buttons:
 * Updated, Delete, and Show Players. 
 * This function is the form handler for all three.
 * This function is going to add a match for the passed $group_letters
 * This function is called from nt_??? with $group_name 
 **/
function nt_match_handle_form( /* $group_name */ ) { 

	global $debug;
	if (  ! $debug ){
			echo "[nt_match_handle_form] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}

	/** Pull common data out of the form, get specific data in handlers if necessary **/
	$thismatch = array( 
				/** matchID will be null on insert **/
				'matchID'		=>  ( isset( $_POST['matchID'] ) ? $_POST['matchID']: "" ),
				'matchDate' 	=>  $_POST['matchDate'],

	); // put the form input into an array

	switch ( $_POST['action'] ) {
		case "Update Match":
			updateMatch( $thismatch );
			break;
			
		case "Delete Match":
			deleteMatch( $thismatch );
			break;
			
		case "Add Match":
			addMatch( $thismatch );
			break;

		case "Show Players":
			showPlayerMatch( $thismatch );
			break;
			
		default:
			echo "[nt_match_handle_form]: bad action";
	}
} 


/**************************/

/** Event_handler_form helpers - these are CRUD for DB */
function addMatch( $thismatch ) {
	global $wpdb;
	global $debug;

	if ( ! $debug ){
			echo "[addMatch] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}
	
	$table_name = $wpdb->prefix . "match";
	$rows_affected = $wpdb->insert( $table_name, $thismatch );
	
	if (0 == $rows_affected ) {
		echo "INSERT ERROR for " . $thismatch['matchDate'] . " " .$thismatch['matchHost'];
		if ( $debug ){
			echo "[addMatch] Fail ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
		}
	}

	

	return $rows_affected;

} // adds a match to the table if addMatch is tagged

function updateMatch( $thismatch ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "match";
	$where = array( 'matchID' => $thismatch['matchID'] );
	$wpdb->update( $table_name, $thismatch, $where );
} // updates a match with a matching matchID if updateMatch is tagged

function deleteMatch( $thismatch ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "match";
	$wpdb->delete( $table_name, $thismatch );
} // deletes a match if deleteMatch is tagged


function showPlayerMatch ( $thismatch ) {
	
}

/** 
 ** create_match_table_header()
 ** This function creates the header div for the list of matches.
 **/
function create_match_table_header() {
	?>
		<div id="matchError"></div>
		<div class="nttable">
			<div class="nttablerow">
				<div class="nttablecellnarrow">Date</div>
				<div class="nttablecellnarrow">Title</div>
				<div class="nttablecellauto"></div>
			</div>
	<?php
}
function create_match_table_footer() {
	?></div><?php
}

/**
 ** create match add row()
 ** This function creates a row in the table with a form to add a match
 **  When you add a match, you add data, time, title.  Players add themselves later.
 **/
function create_match_add_row() {
	?>
		<div class="ntaddrow">
			<form method="post" class="matchForm">
				<div class="nttablecellnarrow">
					<input type="text" name="matchDate" id="addDate" class="datepicker" value="select date">
				</div>
				<div class="nttablecellauto">
					<input type="submit" name="action" id="addMatchButton" value="Add Match"/>
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
function create_match_table_row( $match ) {
	?>
		<div class="nttablerow">
			<form method="post" class="matchForm">
				<div class="matchtablecellnarrow">
					<input type="text" name="matchDate" class="datepicker" value="<?php echo $match->matchDate;?>"/>
					<input type="hidden" name="matchID" value="<?php echo $match->matchID;?>"/>
				</div>		
				<div class="nttablecellauto">
					<input type="submit" name="action" value="Update Match"/>
					<input type="submit" name="action" value="Delete Match"/>
					<input type="submit" name="action" value="Show Players"/>
				</div>
			</form>
		</div>
	<?php
}
?>