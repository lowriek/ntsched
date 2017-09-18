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






/** nt_group_create_sql
 ** creates the sql for the group table, then creates the table in the db.
 **/
function nt_group_create_table ( $group_table_name ) {
	  
	$sql = 	"CREATE TABLE $group_table_name(
		nt_group_id    int not null auto_increment,
		nt_group_organizer_id int,
		nt_group_name  varchar(50),
		nt_group_day int,   /*(0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday) */
		nt_group_time int,  /* see array above */
		nt_group_match_duration int,
		PRIMARY KEY(nt_group_id)
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
		nt_group_handle_form( );

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

	nt_create_group_table_header(); 
	nt_create_group_add_row( /* $group_name */ );

	if ( $allgroups ) {
		foreach ( $allgroups as $thisgroup ) {
			nt_create_group_table_row( $thisgroup );
		}
	} else { 
		?><h3>No groups.  Add one!</h3><?php
	}
			
	nt_create_group_table_footer(); // end the table
}

/**
 * Matches are displayed with three action buttons:
 * Updated, Delete, and Show Players. 
 * This function is the form handler for all three.
 * This function is going to add a match for the passed $group_letters
 * This function is called from nt_??? with $group_name 
 **/
function nt_group_handle_form(  ) { 

	global $debug;

	if (  $debug ){
			echo "[nt_group_handle_form] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}

	if ( ! isset( $_POST['nt_group_action'] ) ) {
		return;
	} 

	/** Pull common data out of the form, get specific data in handlers if necessary **/
	$thisgroup = array( 
				/** matchID will be null on insert **/
				'nt_group_id'		=>  ( isset( $_POST['nt_group_id'] )   ? $_POST['nt_group_id']   : "" ),
				'nt_group_name'	=>  ( isset( $_POST['nt_group_name'] ) ? $_POST['nt_group_name'] : "" ),
				'nt_group_day'     =>  ( isset( $_POST['nt_group_day'] )  ? $_POST['nt_group_day']  : "" ),
				'nt_group_time' 	=>  ( isset( $_POST['nt_group_time'] ) ? $_POST['nt_group_time'] : "" ),
				'nt_group_match_duration'  =>  
									( isset( $_POST['nt_group_match_duration'] ) 
																   ? $_POST['nt_group_match_duration']	: "" )

	); // put the form input into an array

	if ( $debug ) nt_show_group( $thisgroup );

	switch ( $_POST['nt_group_action'] ) {
		case "Update Group":
		case "Delete Group":
		case "Add Group":

			$thisgroup['nt_group_organizer_id'] = nt_is_user_organizer ();
			if ( false == $thisgroup['nt_group_organizer_id'] ) {
				echo "<h3>sorry you are not an organizer, no permission to modify groups</h3>";
				return;
			} 
			switch ( $_POST['nt_group_action'] ) {
				case "Update Group":
					if ( ! nt_update_group( $thisgroup ) ) {
						echo "<h3>Update Failed</h3>";
					}
					break;

				case "Delete Group":
					if ( ! nt_delete_group( $thisgroup ) ) {
						echo "<h3>Delete Failed</h3>";
					}
					break;

				case "Add Group":
					if ( ! nt_add_group( $thisgroup ) ) {
						echo "<h3>Add Failed</h3>";
					}
					break;

				default:
					break; // really can't get here...
				
			}
			break;

		case "Show Group details":
			nt_show_group( $thisgroup );
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
function nt_add_group( $thisgroup ) {
	global $wpdb;
	global $debug;

	if ( ! $debug ){
			echo "[nt_add_group] ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
	}
	
	$rows_affected = $wpdb->insert( $wpdb->prefix . constant( "GROUP_TABLE_NAME" ), $thisgroup );
	
	if ( 0 == $rows_affected ) {
		echo "INSERT ERROR for " . $thisgroup[ 'nt_group_name' ] ;
		if ( $debug ){
			echo "[nt_add_group] Fail ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
		}
		return false;
	}
	$thisgroup[ 'nt_group_id' ] = $wpdb->insert_id;

	if( $debug ) {
		echo "[nt_add_group] success $rows_affected";
	}
						
	if ( 0 == nt_add_match_placeholder ( $thisgroup ) ){
		if ( $debug ){
			echo "[nt_add_group] Failed added placeholder match ";
			echo "<pre>"; print_r($_POST); echo "</pre>";
		}
		return false;
	}

	if( $debug ) {
		echo "[nt_add_group] nt_add_match_placeholder success ";
	}

	return $rows_affected;

} 

// updates a group in the table 
function nt_update_group( $thisgroup ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . constant( "GROUP_TABLE_NAME" );
	$where = array( 'group_id' => $thisgroup['group_id'] );
	$wpdb->update( $table_name, $thisgroup, $where );
} 

// delete a group with a matching groupID
function nt_delete_group( $thisgroup ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . constant( "GROUP_TABLE_NAME" );
	$wpdb->delete( $table_name, $thisgroup );
} 


function nt_show_group( $thisgroup ) {
	global $debug;

	if ($debug) {
		echo "[nw_show_group] ";
		echo "<pre>"; print_r( $thisgroup ); echo "</pre>";
	}
}

/** 
 ** create_match_table_header()
 ** This function creates the header div for the list of matches.
 **/
function nt_create_group_table_header() {
	?>
		<div id="groupError"></div>
		<div class="nttable">
			<div class="nttablerow">
				<div class="nttablecellnarrow">Name</div>
				<div class="nttablecellnarrow">Organized by</div>
				<div class="nttablecellnarrow">Day</div>
				<div class="nttablecellnarrow">Time Slot</div>
				<div class="nttablecellnarrow">Match Duration</div>
				<div class="nttablecellauto"></div>
			</div>
	<?php
}
function nt_create_group_table_footer() {
	?></div><?php
}

/**
 ** create group add row()
 ** This function creates a row in the table with a form to add a group
 **  When you add a group, you add name, day, time slot, match duration.
 **  Each group must have a groupID as all matches must be associated with one group.
 **/

//KBL TODO Start here to fix add group form
function nt_create_group_add_row() {
	?>
		<div class="ntaddrow">
			<form method="post" class="groupForm">
				<div class="nttablecellnarrow"><input type="text" name="nt_group_name" value="Group Name" /></div>
				<div class="nttablecellnarrow"> <?php nt_create_day_menu( "nt_group_day" ); ?></div>
				<div class="nttablecellnarrow"> <?php nt_create_timeslot_menu( "nt_group_time" ); ?></div>
				<div class="nttablecellnarrow"> <?php nt_create_match_duration_menu( "nt_group_match_duration" ); ?></div>
				<div class="nttablecellauto">
				<input type="submit" name="nt_group_action" id="addGroupButton" value="Add Group"/>
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
function nt_create_group_table_row( $thisgroup ) {
	?>
		<div class="nttablerow">
			<form method="post" class="groupForm">
				<div class="grouptablecellnarrow">
					<input type="text" name="nt_group_name" value="<?php echo $thisgroup->nt_group_name;?>"/>
					<div class="nttablecellnarrow"> <?php $user_info = get_userdata($thisgroup->nt_group_organizer_id);
															echo $user_info->user_login;  ?></div>
					<div class="nttablecellnarrow"> <?php nt_create_day_menu( "nt_group_day" , $thisgroup->nt_group_day );?></div>
					<div class="nttablecellnarrow"> <?php nt_create_timeslot_menu( "nt_group_time", $thisgroup->nt_group_time ); ?></div>
					<div class="nttablecellnarrow"> <?php 
								nt_create_match_duration_menu( "nt_group_match_duration", $thisgroup->nt_group_match_duration  ); ?></div>
					<input type="hidden" name="group_id" value="<?php echo $thisgroup->nt_group_id;?>"/>
				</div>		
				<div class="nttablecellauto">
					<input type="submit" name="nt_group_action" value="Update Group"/>
					<input type="submit" name="nt_group_action" value="Delete Group"/>
				</div>
			</form>
		</div>
	<?php
}


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
function nt_get_timeslot ( $timeslot_number ) { 
	global $nt_timeslots;
	return $nt_timeslots[ $timeslot_number ];
}
/* END nt_timeslot support ******************/



/* nt_dayofweek support *********************/
global $nt_day_of_week;
$nt_day_of_week = array (
    	 0=>"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
    );
function nt_create_day_menu( $name, $selected = 0 ){
	// corresponds to MySQL day of the week
	global $nt_day_of_week;
	nt_create_menu ( $name, $nt_day_of_week, $selected ); 
}
function nt_get_day ( $day_number ) { 
	global $nt_day_of_week;
	return $nt_day_of_week[ $day_number ];
}
/* END nt_dayofweek support *********************/


/* nt_match_duration support *********************/
global $nt_match_duration;
$nt_match_duration = array (
    	60=>"60", 90=>"90"
    );
function nt_create_match_duration_menu( $name,  $selected = 60 ){
	global $nt_match_duration;
	nt_create_menu ( $name, $nt_match_duration , $selected ); 
}
function nt_get_match_duration ( $match_duration_number ) { 
	global $nt_match_duration;
	return $nt_match_duration[ $match_duration_number ];
}
/* END   nt_match_duration support *********************/

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
?>