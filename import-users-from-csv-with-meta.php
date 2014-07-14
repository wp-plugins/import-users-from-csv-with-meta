<?php
/*
Plugin Name: Import users from CSV with meta
Plugin URI: http://www.codection.com
Description: This plugins allows to import users using CSV files to WP database automatically
Author: codection
Version: 1.0.0
Author URI: https://codection.com
*/

$url_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__), "", plugin_basename(__FILE__));

function acui_init(){
	acui_activate();
}

function acui_activate(){
}

function acui_deactivate(){
	delete_option("acui_columns");
}

function acui_menu() {
	add_submenu_page( 'tools.php', 'Insert users massively (CSV)', 'Import users from CSV', 'read', 'acui', 'acui_options' ); 
}

function acui_detect_delimiter($file){
	$handle = @fopen($file, "r");
	$sumComma = 0;
	$sumSemiColon = 0;
	$sumBar = 0; 

    if($handle){
    	while (($data = fgets($handle, 4096)) !== FALSE):
	        $sumComma += substr_count($data, ",");
	    	$sumSemiColon += substr_count($data, ";");
	    	$sumBar += substr_count($data, "|");
	    endwhile;
    }
    fclose($handle);
    
    if(($sumComma > $sumSemiColon) && ($sumComma > $sumBar))
    	return ",";
    else if(($sumSemiColon > $sumComma) && ($sumSemiColon > $sumBar))
    	return ";";
    else 
    	return "|";
}

function acui_string_conversion($string){
	if(!preg_match('%(?:
    [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
    |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
    |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
    |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
    |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
    |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
    |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
    )+%xs', $string))
		return utf8_encode($string);
}

function acui_import_users($file, $role){?>
	<div class="wrap">
		<h2>Importing users</h2>	
		<?php
			set_time_limit(0);
			global $wpdb;
			$headers = array();
		
			echo "<h3>Ready to registers</h3>";
			echo "<p>First row represents the form of sheet</p>";
			$row = 0;

			ini_set('auto_detect_line_endings',TRUE);

			$delimiter = acui_detect_delimiter($file);
			
			if (($manager = fopen($file, "r")) !== FALSE):
				while (($data = fgetcsv($manager, 0, $delimiter)) !== FALSE):
					for($i = 0; $i < count($data); $i++)
						$data[$i] = acui_string_conversion($data[$i]);
					
					if($row == 0):
						// check min columns username - password - email
						if(count($data) < 3){
							echo "<div id='message' class='error'>File must contain at least 3 columns: username, password and email</div>";
							break;
						}

						foreach($data as $element)
							$headers[] = $element;

						$columns = count($data);
						update_option("acui_columns", $headers);
						?>
						<h3>Inserting data</h3>
						<table>
							<tr><th>Row</th><?php foreach($headers as $element) echo "<th>" . $element . "</th>"; ?></tr>
						<?php
						$row++;
					else:
						if(count($data) != $columns): // if number of columns is not the same that columns in header
							echo '<script>alert("Row number: ' . $row . ' has no the same columns than header, we are going to skip");</script>';
							continue;
						endif;

						$username = $data[0];
						$password = $data[1];
						$email = $data[2];

						if(username_exists($username)):
							echo '<script>alert("Username: ' . $username . ' already in use, we are going to skip");</script>';
							continue;
						endif;

						$user_id = wp_create_user($username, $password, $email);
						wp_update_user(array ('ID' => $user_id, 'role' => $role)) ;
						
						if($columns > 3)
							for($i=3; $i<$columns; $i++)
								update_user_meta($user_id, $headers[$i], $data[$i]);

						echo "<tr><td>" . ($row - 1) . "</td>";
						foreach ($data as $element)
							echo "<td>$element</td>";

						echo "</tr>\n";

						flush();
					endif;

					$row++;						
				endwhile;
				?>
				</table>
				<br/>
				<p>Process finished you can go <a href="<?php echo get_admin_url() . '/users.php'; ?>">here to see results</a></p>
				<?php
				fclose($manager);
				ini_set('auto_detect_line_endings',FALSE);
			endif;
		?>
	</div>
<?php
}

function acui_options() 
{
	if (!current_user_can('edit_users'))  
	{
		wp_die(__('You are not allowed to see this content.'));
		$acui_action_url = admin_url('options-general.php?page=' . plugin_basename(__FILE__));
	}
	else if(isset($_POST['uploadfile']))
		acui_fileupload_process($_POST['role']);
	else
	{
?>
	<div class="wrap">
		<div id='message' class='updated'>File must contain at least <strong>3 columns: username, password and email</strong>. These should be the first three columns and it should be placed <strong>in this order: username, password and email</strong>. If there are more columns, this plugin will manage it automatically.</div>
		<h2>Import users from CSV</h2>
		<form method="POST" enctype="multipart/form-data" action="" accept-charset="utf-8" onsubmit="return check();">
		<table class="form-table" style="width:50%">
			<tbody>
			<tr class="form-field">
				<th scope="row"><label for="role">Role</label></th>
				<td>
				<select name="role" id="role">
					<option selected="selected" value="subscriber">Subscriber</option>
					<option value="administrator">Administrator</option>
					<option value="editor">Editor</option>
					<option value="author">Author</option>
					<option value="contributor">Contributor</option>			
				</select>
				</td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row"><label for="user_login">CSV file <span class="description">(required)</span></label></th>
				<td><input type="file" name="uploadfiles[]" id="uploadfiles" size="35" class="uploadfiles" /></td>
			</tr>
			</tbody>
		</table>
		<input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Start importing"/>
		</form>

		<h3>Doc</h3>
		<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row">Columns position</th>
				<td><small><em>(Documents should look like the one presented into screenshot. Remember you should fill the first three rows with the next values)</em></small>
					<ol>
						<li>Username</li>
						<li>Password</li>
						<li>Email</li>
					</ol>						
					<small><em>(The next columns are totally customizable and you can use whatever you want. All rows must contains same columns)</em></small>
					<small><em>(User profile will be adapted to the kind of data you have selected)</em></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Important notice</th>
				<td>You can upload as many files as you want, but all must have the same columns. If you upload another file, the columns will change to the form of last file uploaded.</td>
			</tr>
			<tr valign="top">
				<th scope="row">Any question about it</th>
			<td>Please contact: <a href="mailto:contacto@codection.com">contacto@codection.com</a>.</td>
			</tr>
			<tr valign="top">
				<th scope="row">Example</th>
			<td>Download this <a href="<?php echo plugins_url() . "/auto-csv-user-importer/test.csv"; ?>">.csv file</a> to test</td>
			</tr>
		</tbody></table>
		<br/>
		<div style="width:775px;margin:0 auto"><img src="<?php echo plugins_url() . "/auto-csv-user-importer/csv_example.png"; ?>"/></div>
	</div>
	<script type="text/javascript">
	function check(){
		if(document.getElementById("uploadfiles").value == "") {
		   alert("Please choose a file");
		   return false;
		}
	}
	</script>
<?php
	}
}

/**
 * Handle file uploads
 *
 * @todo check nonces
 * @todo check file size
 *
 * @return none
 */
function acui_fileupload_process($role) {
  $uploadfiles = $_FILES['uploadfiles'];

  if (is_array($uploadfiles)) {

	foreach ($uploadfiles['name'] as $key => $value) {

	  // look only for uploded files
	  if ($uploadfiles['error'][$key] == 0) {
		$filetmp = $uploadfiles['tmp_name'][$key];

		//clean filename and extract extension
		$filename = $uploadfiles['name'][$key];

		// get file info
		// @fixme: wp checks the file extension....
		$filetype = wp_check_filetype( basename( $filename ), null );
		$filetitle = preg_replace('/\.[^.]+$/', '', basename( $filename ) );
		$filename = $filetitle . '.' . $filetype['ext'];
		$upload_dir = wp_upload_dir();
		
		if ($filetype['ext'] != "csv") {
		  wp_die('File must be a CSV');
		  return;
		}

		/**
		 * Check if the filename already exist in the directory and rename the
		 * file if necessary
		 */
		$i = 0;
		while ( file_exists( $upload_dir['path'] .'/' . $filename ) ) {
		  $filename = $filetitle . '_' . $i . '.' . $filetype['ext'];
		  $i++;
		}
		$filedest = $upload_dir['path'] . '/' . $filename;

		/**
		 * Check write permissions
		 */
		if ( !is_writeable( $upload_dir['path'] ) ) {
		  wp_die('Unable to write to directory. Is this directory writable by the server?');
		  return;
		}

		/**
		 * Save temporary file to uploads dir
		 */
		if ( !@move_uploaded_file($filetmp, $filedest) ){
		  wp_die("Error, the file $filetmp could not moved to : $filedest ");
		  continue;
		}

		$attachment = array(
		  'post_mime_type' => $filetype['type'],
		  'post_title' => $filetitle,
		  'post_content' => '',
		  'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $filedest );
		require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filedest );
		wp_update_attachment_metadata( $attach_id,  $attach_data );
		
		acui_import_users($filedest, $role);
	  }
	}
  }
}

function acui_extra_user_profile_fields( $user ) {
	$headers = get_option("acui_columns");
	if(count($headers) > 3):
?>
	<h3><?php _e("Extra profile information", "blank"); ?></h3>

	<table class="form-table"><?php

	for($i=3; $i<count($headers); $i++):
	$column = $headers[$i];
	?>
		<tr>
			<th><label for="<?php echo $column; ?>"><?php echo $column; ?></label></th>
			<td><input type="text" name="<?php echo $column; ?>" id="<?php echo $column; ?>" value="<?php echo esc_attr(get_the_author_meta($column, $user->ID )); ?>" class="regular-text" /></td>
		</tr>
		<?php
	endfor;
	?>
	</table><?php
	endif;
}

function acui_save_extra_user_profile_fields( $user_id ){
	if (!current_user_can('edit_user', $user_id)) { return false; }
	
	foreach ($headers as $column)
		update_user_meta( $user_id, $column, $_POST[$column] );
}
	
register_activation_hook(__FILE__,'acui_init'); 
register_deactivation_hook( __FILE__, 'acui_deactivate' );
add_action("plugins_loaded", "acui_init");
add_action("admin_menu", "acui_menu");
add_action("show_user_profile", "acui_extra_user_profile_fields");
add_action("edit_user_profile", "acui_extra_user_profile_fields");
add_action("personal_options_update", "acui_save_extra_user_profile_fields");
add_action("edit_user_profile_update", "acui_save_extra_user_profile_fields");
