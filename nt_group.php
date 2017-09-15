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

/* These court times are from tennisbookings, note that 90 minutes is not available for all slots */

function create_timeslot_menu( $name ){
	$timeslots = array (
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
	create_menu ( $name, $timeslots ); 

}
function create_day_menu( $name ){
	$dayofweek = array (
    	"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    );
   create_menu ( $name, $dayofweek ); 
}
function create_matchduration_menu( $name ){
	$matchDuration = array (
    	"60", "90"
    );
   create_menu ( $name, $matchDuration ); 
}

function create_menu( $name, $contents )
{
	echo "<select name=\"$name\">";
    foreach ( $contents as $key => $value ) {
		echo "<option value=\"$value\"> $value </option>\n";
	}
	echo "</select>";
}

/** nt_group_create_sql
 ** creates the sql for the group table, then creates the table in the db.
 **/
function nt_group_create_table ( $group_table_name ) {
	  
	$sql = 	"CREATE TABLE $group_table_name(
		groupID    int not null auto_increment,
		organizerID int,
		groupName  varchar(50),
		groupDay int,   /*(0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday) */
		groupTime int,  /* see array above */
		groupMatchDuration ENUM ('sixty', 'ninety'),
		PRIMARY KEY(groupID)
	) engine = InnoDB;";
	dbDelta( $sql );

}
/**  nt_group_delete_table()
 **  matching delete function for group table
 **/
function nt_group_delete_table() {
	global $wpdb; 

   	$group_table_name = $wpdb->prefix . "group";      
    $sql = "DROP TABLE IF EXISTS $group_table_name;";    
    $wpdb->query( $sql );
}

/** nt_group_nub() 
 ** Displays groups and group forms for CRUD
 **/
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
 * Should only display groups for the current user.
 *
 **/
function nt_display_groups( ) {

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
		groupName varchar(40),
		groupDay int,   (0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday) 
		groupTime int,  
		groupMatchDuration ENUM ('sixty', 'ninety'),
		PRIMARY KEY(groupID)
	) engine = InnoDB;";*/  

	/** Pull common data out of the form, get specific data in handlers if necessary **/
	$thisgroup = array( 
				/** matchID will be null on insert **/
				'groupID'		=>  ( isset( $_POST['groupID'] ) ? $_POST['groupID']: "" ),
				'groupName'		=>  ( isset( $_POST['groupName'] ) ? $_POST['groupName']: "" ),
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
				<div class="nttablecellnarrow">Name</div>
				<div class="nttablecellnarrow">Day</div>
				<div class="nttablecellnarrow">Time Slot</div>
				<div class="nttablecellnarrow">Match Duration</div>
				<div class="nttablecellauto"></div>
			</div>
	<?php
}
function create_group_table_footer() {
	?></div><?php
}

/**
 ** create group add row()
 ** This function creates a row in the table with a form to add a group
 **  When you add a group, you add name, day, time slot, match duration.
 **  Each group must have a groupID as all matches must be associated with one group.
 **/

//KBL TODO Start here to fix add group form
function create_group_add_row() {
	?>
		<div class="ntaddrow">
			<form method="post" class="groupForm">
				<div class="nttablecellnarrow">
					<input type="text" name="groupName" value="Group Name" />
				</div>
				<div class="nttablecellnarrow"> <?php create_day_menu( "groupDay"); ?></div>
				<div class="nttablecellnarrow"> <?php create_timeslot_menu( "groupTime" ); ?></div>
				<div class="nttablecellnarrow"> <?php create_matchduration_menu( "groupMatchDuration" ); ?></div>
				<div class="nttablecellauto">
				<input type="submit" name="action" id="addGroupButton" value="Add Group"/>
				</div>
				<input type="hidden" name="groupID" value="<?php echo $group->groupID;?>"/>
		
			</form>
		</div><!-- end nttableaddrow -->
	<?php
}

/**
 ** create group_table_row
 ** this function creates one row of the list of groups.
 ** Dump the group passed with options to change group details.
 **/
function create_group_table_row( $group ) {
	?>
		<div class="nttablerow">
			<form method="post" class="groupForm">
				<div class="grouptablecellnarrow">
					<input type="text" name="groupName" value="<?php echo $group->groupName;?>"/>
					<input type="text" name="groupDay" value="<?php echo $group->groupDay;?>"/>
					<input type="text" name="groupTimeSlot" value="<?php echo $group->groupTimeSlot;?>"/>
					<input type="text" name="groupMatchDuration" value="<?php echo $group->groupMatchDuration;?>"/>
					<input type="hidden" name="groupID" value="<?php echo $group->groupID;?>"/>
				</div>		
				<div class="nttablecellauto">
					<input type="submit" name="action" value="Update Group"/>
					<input type="submit" name="action" value="Delete Group"/>
				</div>
			</form>
		</div>
	<?php
}
?>