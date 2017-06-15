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

add_action( 'show_user_profile', 'racketeers_extra_user_profile_fields' );
add_action( 'edit_user_profile', 'racketeers_extra_user_profile_fields' );
function racketeers_extra_user_profile_fields( $user ) {
?>
  <h3><?php _e("Extra profile information", "blank"); ?></h3>
  <table class="form-table">
    <tr>
      <th><label for="phone"><?php _e("Phone"); ?></label></th>
      	<td>
	        <input type="text" name="phone" id="phone" class="regular-text" 
	            value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>" /><br />
	        <span class="description"><?php _e("Please enter your phone."); ?></span>
    	</td>
    </tr>
    <tr>
      <th><label for="playerStatus"><?php _e("Player Status"); ?></label></th>
      	<td>
      		<span class="description"><?php _e("Please select a player status."); ?></span>
	         &nbsp;&nbsp;
	        <?php 
	        	$playerStatus = esc_attr( get_the_author_meta( 'playerStatus', $user->ID ) ); 
				if ( $playerStatus == "active" ) {
		         	?>
			         Active:   <input type="radio" name="playerStatus" class="regular-text" value="active"
			         	checked="checked"/>&nbsp;&nbsp;&nbsp;&nbsp;
			         Sub only: <input type="radio" name="playerStatus" class="regular-text" value="sub"/>&nbsp;&nbsp;&nbsp;&nbsp;
			         Inactive: <input type="radio" name="playerStatus" class="regular-text" value="inactive"/>
			        <?php
			  
		        } else if ( $playerStatus == "sub" ) {
		         	?>
			         Active:   <input type="radio" name="playerStatus" class="regular-text" value="active"
			         	/>&nbsp;&nbsp;&nbsp;&nbsp;
			         Sub only: <input type="radio" name="playerStatus" class="regular-text" value="sub" checked="checked"/>&nbsp;&nbsp;&nbsp;&nbsp;
			         Inactive: <input type="radio" name="playerStatus" class="regular-text" value="inactive"/>
			        <?php

		        } else {
					?>
			         Active:   <input type="radio" name="playerStatus" class="regular-text" value="active"
			         	/>&nbsp;&nbsp;&nbsp;&nbsp;
			         Sub only: <input type="radio" name="playerStatus" class="regular-text" value="sub" />&nbsp;&nbsp;&nbsp;&nbsp;
			         Inactive: <input type="radio" name="playerStatus" class="regular-text" value="inactive" checked="checked"/>
			     	<?php
			  
		        }
		      
		    ?>
    	</td>
    </tr>
    <tr>
      <th><label for="playerSMS"><?php _e("Player Texts"); ?></label></th>
      	<td>
      		<span class="description"><?php _e("Please select a text message preference."); ?></span>&nbsp;&nbsp;
	        Do you want text message reminders?   &nbsp;&nbsp;
	        <?php 
	        	$playerSMS = esc_attr( get_the_author_meta( 'playerSMS', $user->ID ) ); 
		        if ( $playerSMS == "yes" ) {
		         	?>
		         	yes <input type="radio" name="playerSMS" class="regular-text" value="yes" checked="checked"/>
		         		&nbsp;&nbsp;&nbsp;&nbsp;
		            no  <input type="radio" name="playerSMS" class="regular-text" value="no"/>
		            <?php
		        } else {
		         	?>
		         	yes <input type="radio" name="playerSMS" class="regular-text" value="yes" />&nbsp;&nbsp;&nbsp;&nbsp;
		            no  <input type="radio" name="playerSMS" class="regular-text" value="no" checked="checked"/>
		            <?php
		        }
		    ?>
    	</td>
    </tr>
  </table>
 <h2>Dump Meta</h2>
  <?php echo "phone: ";        echo  esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?><br />
  <?php echo "playerStatus: "; echo  esc_attr( get_the_author_meta( 'playerStatus', $user->ID ) ); ?><br />
  <?php echo "playerSMS: ";    echo  esc_attr( get_the_author_meta( 'playerSMS', $user->ID ) ); ?><br />


<?php
}

add_action( 'personal_options_update', 'racketeers_save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'racketeers_save_extra_user_profile_fields' );
function racketeers_save_extra_user_profile_fields( $user_id ) {
  $saved = false;
  if ( current_user_can( 'edit_user', $user_id ) ) {
    update_user_meta( $user_id, 'phone', $_POST['phone'] );
    update_user_meta( $user_id, 'playerStatus', $_POST['playerStatus'] );
    update_user_meta( $user_id, 'playerSMS', $_POST['playerSMS'] );


    $saved = true;
  }
  return true;
}
