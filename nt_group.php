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

/* nt_timeslot support *********************/
global $nt_timeslots;
$nt_timeslots = array (
    	0=>"7am",
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
function nt_create_timeslot_menu( $name, $selected = 0 ){
	global $nt_timeslots;
	nt_create_menu ( $name, $nt_timeslots, $selected ); 

}
function nt_get_timeslot ( $timeslotnumber ) { 
	global $nt_timeslots;
	return $nt_timeslots[$timeslotnumber];
}
/* END nt_timeslot support ******************/



/* nt_dayofweek support *********************/
global $nt_dayofweek;
$nt_dayofweek = array (
    	 0=>"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
    );
function nt_create_day_menu( $name, $selected = 0 ){
	// corresponds to MySQL day of the week
	global $nt_dayofweek;
	nt_create_menu ( $name, $nt_dayofweek, $selected ); 
}
function nt_get_day ( $daynumber ) { 
	global $nt_dayofweek;
	return $nt_dayofweek[$daynumber];
}
/* END nt_dayofweek support *********************/





/* nt_matchDuration support *********************/
global $nt_matchDuration;
$nt_matchDuration = array (
    	60=>"60", 90=>"90"
    );
function nt_create_matchduration_menu( $name,  $selected = 60 ){
	global $nt_matchDuration;
	nt_create_menu ( $name, $nt_matchDuration , $selected ); 
}
function nt_get_matchduration ( $matchdurationnumber ) { 
	global $nt_matchDuration;
	return $nt_matchDuration[$matchdurationnumber];
}
/* END   nt_matchDuration support *********************/


function nt_create_menu( $name, $contents, $selected )
{
	echo "<select name=\"$name\">";
    foreach ( $contents as $key => $value ) {
    	if ( $selected == $key )
    		echo "<option value=\"$key\" selected > $value </option>\n";
    	else
			echo "<option value=\"$key\"> $value </option>\n";
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
		groupMatchDuration int,
		PRIMARY KEY(groupID)
	) engine = InnoDB;";
	dbDelta( $sql );

}
/**  nt_group_delete_table()
 **  matching delete function for group table
 **/
function nt_group_delete_table( $group_table_name ) {
	global $wpdb; 

    $sql = "DROP TABLE IF EXISTS $group_table_name;";    
    $wpdb->query( $sql );
}

/** nt_group_nub() 
 ** Displays groups and group forms for CRUD
 **/
function nt_group_hub(  ) {
		global $debug;

		/* handle form request if pending */
		if ( isset( $_POST['groupAction'] ) ) {
			nt_group_handle_form( $_POST['groupAction'] );
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
function nt_group_handle_form( $action ) { 

	global $debug;

	if (  $debug ){
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
				'groupID'		=>  ( isset( $_POST['groupID']   ) ? $_POST['groupID']   : "" ),
				'groupName'		=>  ( isset( $_POST['groupName'] ) ? $_POST['groupName'] : "" ),
				'groupDay'      =>  ( isset( $_POST['groupDay']  ) ? $_POST['groupDay']  : "" ),
				'groupTime' 	=>  ( isset( $_POST['groupTime'] ) ? $_POST['groupTime'] : "" ),
				'groupMatchDuration'  =>  
									( isset( $_POST['groupMatchDuration'] ) 
																   ? $_POST['groupMatchDuration']	: "" )

	); // put the form input into an array

	if ( $debug ) showgroup( $thisgroup );

	$groupOrganizer = nt_is_user_organizer ();
	

	switch ( $action ) {
		case "Update Group":
			if ( false == $groupOrganizer ) {
				echo "<h2>sorry you are not an organizer, so update group failed</h2>";
			} else {
				updateGroup( $thisgroup );
			}
			break;
			
		case "Delete Group":
			if ( false == $groupOrganizer ) {
				echo "<h2>sorry you are not an organizer, so delete group failed</h2>";
			} else {
				deleteGroup( $thisgroup );
			}
			break;
			
		case "Add Group":
			if ( false == $groupOrganizer ) {
				echo "<h2>sorry you are not an organizer, so add group failed</h2>";
			} else {
				addGroup( $thisgroup );
			}
			break;

		case "Show Group details":
			showGroup( $thisgroup );
			break;
			
		default:
			echo "[nt_group_handle_form]: bad action $action";
	}
} 


/**************************/

/* figure out if the insert is for a known organizer  
 * think about how an admin can do this to select known organizers.
 * think that a user with the role author is an organizer
 * and a user with the role subscriber is player.
 * admins can do anything.
 */
function nt_is_user_organizer(){

	$current_user_id =  get_current_user_id();
	$user_info = get_userdata($current_user_id );

	if ( ! $user_info ) {
		return false;
	}

	$user_role = $user_info->roles;

	if 	( 	in_array ( 'administrator' , 	$user_role ) 
		 	|| in_array ( 'author'        , 	$user_role )
		 //	|| in_array ( 'organizer'     , 	$user_role ) 
		)
		return true;

	return false;
}

/** Event_handler_form helpers - these are CRUD for DB */
function addGroup( $thisgroup ) {
	global $wpdb;
	global $debug;

	if ( ! $debug ){
			echo "[addGroup] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}

	
	
	$thisgroup['organizerID'] = $groupOrganizer;

	$rows_affected = $wpdb->insert( $wpdb->prefix . constant( "GROUP_TABLE_NAME" ), $thisgroup );
	
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
	
	$table_name = $wpdb->prefix . constant( "GROUP_TABLE_NAME" );
	$where = array( 'groupID' => $thisgroup['groupID'] );
	$wpdb->update( $table_name, $thisgroup, $where );
} 

// delete a group with a matching groupID
function deleteGroup( $thisgroup ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . constant( "GROUP_TABLE_NAME" );
	$wpdb->delete( $table_name, $thisgroup );
} 


function showgroup( $thisgroup ) {
	echo "[showgroup] ";
	echo "<pre>"; print_r($thisgroup); echo "</pre>";
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
				<div class="nttablecellnarrow"><input type="text" name="groupName" value="Group Name" /></div>
				<div class="nttablecellnarrow"> <?php nt_create_day_menu( "groupDay" ); ?></div>
				<div class="nttablecellnarrow"> <?php nt_create_timeslot_menu( "groupTime" ); ?></div>
				<div class="nttablecellnarrow"> <?php nt_create_matchduration_menu( "groupMatchDuration" ); ?></div>
				<div class="nttablecellauto">
				<input type="submit" name="groupAction" id="addGroupButton" value="Add Group"/>
				</div>		
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
					<div class="nttablecellnarrow"> <?php nt_create_day_menu( "groupDay" , $group->groupDay );?></div>
					<div class="nttablecellnarrow"> <?php nt_create_timeslot_menu( "groupTime", $group->groupTime ); ?></div>
					<div class="nttablecellnarrow"> <?php nt_create_matchduration_menu( "groupMatchDuration", $group->groupMatchDuration  ); ?></div>
					<input type="hidden" name="groupID" value="<?php echo $group->groupID;?>"/>
				</div>		
				<div class="nttablecellauto">
					<input type="submit" name="groupAction" value="Update Group"/>
					<input type="submit" name="groupAction" value="Delete Group"/>
				</div>
			</form>
		</div>
	<?php
}
?>