<?php
/*
 * This is support for creating groups.  
 * A user with group creation privs?  or some sort of admin?  Maybe should be wordpress user priv 
 * KBL Todo - how to create a user priv
 *
 * 1. User with priv can create group, or many groups (maybe limit? maybe not)
 * 2. User with priv can create matches -- after they create a group.
 * 3. Should any user be able to create a group?
 * 
 * Current user should be able to create a group.
 * Current user should be able to list all their groups.
 * Current user should be able to delete a group they created.
 * Current user should be able to edit their group.
 *
 * Todo
 *    Figure out user privs in wordpress
 *    Add CRUD for groups (copy from matches for now)
 */
 function nt_group_hub(  ) {
		global $debug;

		/* handle form request if pending */
		if ( isset( $_POST['action'] ) ) {
			nt_group_handle_form();
		} 

		nt_display_groups();
}
/** 
 * Display matches for a group
 * Args - group name
 *
 **/
function nt_display_groups( /* $group_name */ ) {

	global $debug;
	global $wpdb;

	$group_table_name = $wpdb->prefix . "group";
	$query = "SELECT * FROM $group_table_name";  /* KBL todo: add for this user */
	$allgroups = $wpdb->get_results( $query );

	create_group_table_header(); 
	create_group_add_row( /* $group_name */ );

	if ( $allgroups ) {
		foreach ( $allgroups as $thisgroup ) {
			create_group_table_row( $thisgroup );
		}
	} else { 
		?><h3>No groups.  Add one!</h3><?php
	}
			
	create_group_table_footer(); // end the table
}

/**
 * Matches are displayed with three action buttons:
 * Updated, Delete, and Show Players. 
 * This function is the form handler for all three.
 * This function is going to add a match for the passed $group_letters
 * This function is called from nt_??? with $group_name 
 **/
function nt_group_handle_form( /* $group_name */ ) { 

	global $debug;
	if (  ! $debug ){
			echo "[nt_group_handle_form] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}


/*    CREATE TABLE $group_table_name(
		groupID    int not null auto_increment,
		organizerID int,
		groupDay int,   (0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday) 
		groupTime int,  
		groupMatchDuration ENUM ('sixty', 'ninety'),
		PRIMARY KEY(groupID)
	) engine = InnoDB;";*/  

	/** Pull common data out of the form, get specific data in handlers if necessary **/
	$thisgroup = array( 
				/** matchID will be null on insert **/
				'groupID'		=>  ( isset( $_POST['groupID'] ) ? $_POST['groupID']: "" ),
				'groupDay'      =>  $_POST['groupDay'],
				'groupTime' 	=>  $_POST['groupTime'],
				'groupMatchDuration'  =>  $_POST['groupMatchDuration']

	); // put the form input into an array

	switch ( $_POST['action'] ) {
		case "Update Group":
			updateGroup( $thisgroup );
			break;
			
		case "Delete Group":
			deleteMatch( $thisgroup );
			break;
			
		case "Add Group":
			addMatch( $thisgroup );
			break;

		case "Show Group details":
			showGroup( $thisgroup );
			break;
			
		default:
			echo "[nt_group_handle_form]: bad action";
	}
} 


/**************************/

/** Event_handler_form helpers - these are CRUD for DB */
function addGroup( $thisgroup ) {
	global $wpdb;
	global $debug;

	if ( ! $debug ){
			echo "[addGroup] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}
	
	$table_name = $wpdb->prefix . "group";
	$rows_affected = $wpdb->insert( $table_name, $thisgroup );
	
	if (0 == $rows_affected ) {
		echo "INSERT ERROR for " . $thisgroup['groupDay'] . " " .$thisgroup['groupTime'];
		if ( $debug ){
			echo "[addGroup] Fail ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
		}
	}

	

	return $rows_affected;

} 

// updates a group in the table 
function updateGroup( $thisgroup ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "group";
	$where = array( 'groupID' => $thisgroup['groupID'] );
	$wpdb->update( $table_name, $thisgroup, $where );
} 

// delete a group with a matching groupID
function deleteGroup( $thismatch ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "group";
	$wpdb->delete( $table_name, $thisgroup );
} 


function showgroup( $thisgroup ) {
	
}

/** 
 ** create_match_table_header()
 ** This function creates the header div for the list of matches.
 **/
function create_group_table_header() {
	?>
		<div id="groupError"></div>
		<div class="nttable">
			<div class="nttablerow">
				<div class="nttablecellnarrow">Date</div>
				<div class="nttablecellnarrow">Title</div>
				<div class="nttablecellauto"></div>
			</div>
	<?php
}
function create_group_table_footer() {
	?></div><?php
}

/**
 ** create match add row()
 ** This function creates a row in the table with a form to add a match
 **  When you add a match, you add data, time, title.  Players add themselves later.
 **  Each match must have a groupID as all matches must be associated with one group.
 **  KBL TODO - fix form for groups
 **/
function create_group_add_row() {
	?>
		<div class="ntaddrow">
			<form method="post" class="groupForm">
				<div class="nttablecellnarrow">
					<input type="text" name="groupDay" id="addDate" class="datepicker" value="select date">
				</div>
				<div class="nttablecellauto">
					<input type="submit" name="action" id="addGroupButton" value="Add Match"/>
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