<?php
/*
Plugin Name: Import users from CSV with meta
Plugin URI: http://www.codection.com
Description: This plugins allows to import users using CSV files to WP database automatically
Author: codection
Version: 1.5.1
Author URI: https://codection.com
*/

$url_plugin = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
$wp_users_fields = array("user_nicename", "user_url", "display_name", "nickname", "first_name", "last_name", "description", "jabber", "aim", "yim", "user_registered", "password");
$wp_min_fields = array("Username", "Email");

function acui_init(){
	acui_activate();
}

function acui_activate(){
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}
	
	add_option( "acui_columns" );
	add_option( "acui_mail_from", 'wordpress@' . $sitename, '', false );
	add_option( "acui_mail_from_name", 'WordPress', '', false );
	add_option( "acui_mail_subject", 'Welcome to ' . get_bloginfo("name"), '', false );
	add_option( "acui_mail_body", 'Welcome,<br/>Your data to login in this site is:<br/><ul><li>URL to login: **loginurl**</li><li>Username = **username**</li><li>Password = **password**</li></ul>', '', false );
}

function acui_deactivate(){
	delete_option( "acui_columns" );
	delete_option( "acui_mail_from" );
	delete_option( "acui_mail_from_name" );
	delete_option( "acui_mail_subject" );
	delete_option( "acui_mail_body" );
}

function acui_menu() {
	add_submenu_page( 'tools.php', 'Insert users massively (CSV)', 'Import users from CSV', 'manage_options', 'acui', 'acui_options' ); 
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

function acui_string_conversion( $string ){
	if(!preg_match('%(?:
    [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
    |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
    |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
    |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
    |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
    |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
    |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
    )+%xs', $string)){
		return utf8_encode($string);
    }
	else
		return $string;
}

function acui_import_users( $file, $form_data, $attach_id ){?>
	<div class="wrap">
		<h2>Importing users</h2>	
		<?php
			set_time_limit(0);
			global $wpdb;
			$headers = array();
			$headers_filtered = array();
			$role = $form_data["role"];
			$empty_cell_action = $form_data["empty_cell_action"];
			$activate_users_wp_members = $form_data["activate_users_wp_members"];
			
			global $wp_users_fields;
			global $wp_min_fields;	
	
			echo "<h3>Ready to registers</h3>";
			echo "<p>First row represents the form of sheet</p>";
			$row = 0;
			$positions = array();

			ini_set('auto_detect_line_endings',TRUE);

			$delimiter = acui_detect_delimiter($file);

			$manager = new SplFileObject($file);
			while ( $data = $manager->fgetcsv($delimiter) ):
				if( empty($data[0]) )
					continue;

				if( count($data) == 1 )
					$data = $data[0];
				
				foreach ($data as $key => $value){
					$data[$key] = trim($value);
				}

				for($i = 0; $i < count($data); $i++){
					$data[$i] = acui_string_conversion($data[$i]);
				}
				
				if($row == 0):
					// check min columns username - email
					if(count($data) < 2){
						echo "<div id='message' class='error'>File must contain at least 2 columns: username and email</div>";
						break;
					}

					$i = 0;
					$password_position = false;
					
					foreach ( $wp_users_fields as $wp_users_field ) {
						$positions[ $wp_users_field ] = false;
					}

					foreach($data as $element){
						$headers[] = $element;

						if( in_array( strtolower($element) , $wp_users_fields ) )
							$positions[ strtolower($element) ] = $i;

						if( !in_array( strtolower( $element ), $wp_users_fields ) && !in_array( $element, $wp_min_fields ) )
							$headers_filtered[] = $element;

						$i++;
					}

					$columns = count($data);

					update_option("acui_columns", $headers_filtered);
					?>
					<h3>Inserting and updating data</h3>
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
					$email = $data[1];
					$user_id = 0;
					$problematic_row = false;
					$password_position = $positions["password"];

					if($password_position === false)
						$password = wp_generate_password();
					else
						$password = $data[ $password_position ];

					if( username_exists($username) ){ // if user exists, we take his ID by login
						$user_object = get_user_by( "login", $username );
						$user_id = $user_object->ID;

						if( !empty($password) )
							wp_set_password( $password, $user_id );
					}
					elseif( email_exists( $email ) ){ // if the email is registered, we take the user from this
	                    $user_object = get_user_by( "email", $email );
	                    $user_id = $user_object->ID;
	                    
	                    $data[0] = "User already exists as: " . $user_object->user_login . "<br/>(in this CSV file is called: " . $username . ")";
	                    $problematic_row = true;

	                    if( !empty($password) )
	                        wp_set_password( $password, $user_id );
					}
					else{
						if( empty($password) ) // if user not exist and password is empty but the column is set, it will be generated
							$password = wp_generate_password();

						$user_id = wp_create_user($username, $password, $email);
					}
						
					if( is_wp_error($user_id) ){ // in case the user is generating errors after this checks
						echo '<script>alert("Problems with user: ' . $username . ', we are going to skip");</script>';
						continue;
					}

					if(!( in_array("administrator", acui_get_roles($user_id), FALSE) || is_multisite() && is_super_admin( $user_id ) ))
						wp_update_user(array ('ID' => $user_id, 'role' => $role)) ;

					// WP Members activation
					if( $activate_users_wp_members == "activate" )
						update_user_meta( $user_id, "active", true );
						
					if($columns > 2){
						for( $i=2 ; $i<$columns; $i++ ):
							if( !empty( $data ) ){
								if(strtolower( $headers[$i] ) == "password"){ // passwords -> continue
									continue;
								}
								else{
									if( in_array( $headers[$i], $wp_users_fields ) ){ // wp_user data
										
										if( empty( $data[ $i ] ) && $empty_cell_action == "leave" )
											continue;
										else
											wp_update_user( array( 'ID' => $user_id, $headers[$i] => $data[$i] ) );
										
									}
									else{ // wp_usermeta data
										
										if( empty( $data[ $i ] ) ){
											if( $empty_cell_action == "delete" )
												delete_post_meta( $user_id, $headers[$i] );
											else
												continue;	
										}
										else
											update_user_meta( $user_id, $headers[$i], $data[$i] );

									}
								}
							}
						endfor;
					}

					$styles = "";
					if( $problematic_row )
						$styles = "background-color:red; color:white;";

					echo "<tr style='$styles' ><td>" . ($row - 1) . "</td>";
					foreach ($data as $element)
						echo "<td>$element</td>";

					echo "</tr>\n";

					flush();

					// send mail
					if( isset( $form_data["sends_email"] ) && $form_data["sends_email"] ):
						update_option( "acui_mail_from_name", stripslashes( $form_data["from_name"] ) );
						update_option( "acui_mail_from", stripslashes( $form_data["from_email"] ) );
						update_option( "acui_mail_body", stripslashes( $form_data["body_mail"] ) );
						update_option( "acui_mail_subject", stripslashes( $form_data["subject_mail"] ) );
						
						$body_mail = stripslashes($form_data["body_mail"] );
						$subject = stripslashes( $form_data["subject_mail"] );
												
						$body_mail = str_replace("**loginurl**", "<a href='" . home_url() . "/wp-login.php" . "'>" . home_url() . "/wp-login.php" . "</a>", $body_mail);
						$body_mail = str_replace("**username**", $username, $body_mail);
						$body_mail = str_replace("**password**", $password, $body_mail);

						foreach ( $wp_users_fields as $wp_users_field ) {								
							if( $positions[ $wp_users_field ] != false && $wp_users_field != "password" ){
								$body_mail = str_replace("**" . $wp_users_field .  "**", $data[ $positions[ $wp_users_field ] ] , $body_mail);							
							}
						}

						add_filter( 'wp_mail_from', 'acui_mail_from' );
						add_filter( 'wp_mail_from_name', 'acui_mail_from_name' );
						add_filter( 'wp_mail_content_type', 'set_html_content_type' );
						
						wp_mail( $email, $subject, $body_mail );

						remove_filter( 'wp_mail_from', 'acui_mail_from' );
						remove_filter( 'wp_mail_from_name', 'acui_mail_from_name' );
						remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

					endif;

				endif;

				$row++;						
			endwhile;

			wp_delete_attachment( $attach_id );

			?>
			</table>
			<br/>
			<p>Process finished you can go <a href="<?php echo get_admin_url() . '/users.php'; ?>">here to see results</a></p>
			<?php
			//fclose($manager);
			ini_set('auto_detect_line_endings',FALSE);
		?>
	</div>
<?php
}

function acui_mail_from(){
	return get_option( "acui_mail_from" );
}

function acui_mail_from_name(){
	return get_option( "acui_mail_from_name" );
}

function acui_get_roles($user_id){
	$roles = array();
	$user = new WP_User( $user_id );

	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role )
			$roles[] = $role;
	}

	return $roles;
}

function acui_get_editable_roles() {
    global $wp_roles;

    $all_roles = $wp_roles->roles;
    $editable_roles = apply_filters('editable_roles', $all_roles);
    $list_editable_roles = array();

    foreach ($editable_roles as $key => $editable_role)
		$list_editable_roles[$key] = $editable_role["name"];
	
    return $list_editable_roles;
}

function acui_check_options(){
	if( get_option( "acui_mail_from" ) == "" )
		update_option( "acui_mail_from", 'wordpress@' . $sitename );

	if( get_option( "acui_mail_from_name" ) == "" )
		update_option( "acui_mail_from_name", 'WordPress' );

	if( get_option( "acui_mail_body" ) == "" )
		update_option( "acui_mail_body", 'Welcome,<br/>Your data to login in this site is:<br/><ul><li>URL to login: **loginurl**</li><li>Username = **username**</li><li>Password = **password**</li></ul>' );

	if( get_option( "acui_mail_subject" ) == "" )
		update_option( "acui_mail_subject", 'Welcome to ' . get_bloginfo("name") );
}

function acui_options() 
{
	global $url_plugin;

	if (!current_user_can('create_users'))  
	{
		wp_die(__('You are not allowed to see this content.'));
		$acui_action_url = admin_url('options-general.php?page=' . plugin_basename(__FILE__));
	}
	else if( isset( $_POST['uploadfile'] ) ){
		acui_fileupload_process( $_POST );
	}
	else
	{
		
	$args_old_csv = array( 'post_type'=> 'attachment', 'post_mime_type' => 'text/csv', 'post_status' => 'inherit', 'posts_per_page' => -1 );
	$old_csv_files = new WP_Query( $args_old_csv );

	acui_check_options();

	$from_email = get_option( "acui_mail_from" );
	$from_name = get_option( "acui_mail_from_name" );
	$body_mail = get_option( "acui_mail_body" );
	$subject_mail = get_option( "acui_mail_subject" );

?>
	<script>	
	jQuery(document).ready(function($) {
		$('.postbox').children('h3, .handlediv').click(function(){ $(this).siblings('.inside').toggle();});
	});
	</script>

	<div class="wrap">	

		<div class="postbox">
		    <div title="Click to open/close" class="handlediv">
		      <br>
		    </div>

		    <h3 class="hndle"><span>&nbsp;Do you like it?</span></h3>

		    <div class="inside" style="display: block;">
		        <img src="<?php echo $url_plugin; ?>icon_coffee.png" alt="buy me a coffee" style=" margin: 5px; float:left;">
		        <p>Hi! we are <a href="https://twitter.com/fjcarazo" target="_blank" title="Javier Carazo">Javier Carazo</a> and <a href="https://twitter.com/ahornero" target="_blank" title="Alberto Hornero">Alberto Hornero</a>  from <a href="http://codection.com">Codection</a>, developers of this plugin.</p>
		        <p>We have been spending many hours to develop this plugin. <br>If you like and use this plugin, you can <strong>buy us a cup of coffee</strong>.</p>
		        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="QPYVWKJG4HDGG">
					<input type="image" src="https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
					<img alt="" border="0" src="https://www.paypalobjects.com/es_ES/i/scr/pixel.gif" width="1" height="1">
				</form>
		        <div style="clear:both;"></div>
		    </div>
		</div>

		<?php if( $old_csv_files->found_posts > 0 ): ?>
		<div class="postbox">
		    <div title="Click to open/close" class="handlediv">
		      <br>
		    </div>

		    <h3 class="hndle"><span>&nbsp;Old CSV files uploaded</span></h3>

		    <div class="inside" style="display: block;">
		    	<p>For security reasons you should delete this files, probably they would be visible in the Internet if a bot or someone discover the URL. You can delete each file or maybe you want delete all CSV files you have uploaded:</p>
		    	<input type="button" value="Delete all CSV files uploaded" id="bulk_delete_attachment" style="float:right;"></input>
		    	<ul>
		    		<?php while($old_csv_files->have_posts()) : 
		    			$old_csv_files->the_post(); 

		    			if( get_the_date() == "" )
		    				$date = "undefined";
		    			else
		    				$date = get_the_date();
		    		?>
		    		<li><a href="<?php echo wp_get_attachment_url( get_the_ID() ); ?>"><?php the_title(); ?></a> uploaded the <?php echo $date; ?> <input type="button" value="Delete" class="delete_attachment" attach_id="<?php the_ID(); ?>"></input></li>
		    		<?php endwhile; ?>
		    		<?php wp_reset_postdata(); ?>
		    	</ul>
		        <div style="clear:both;"></div>
		    </div>
		</div>
		<?php endif; ?>	

		<div id='message' class='updated'>File must contain at least <strong>2 columns: username and email</strong>. These should be the first two columns and it should be placed <strong>in this order: username and email</strong>. If there are more columns, this plugin will manage it automatically.</div>
		<div id='message-password' class='error'>Please, read carefully how <strong>passwords are managed</strong> and also take note about capitalization, this plugin is <strong>case sensitive</strong>.</div>

		<div style="float:left; width:80%;">
			<h2>Import users from CSV</h2>
		</div>

		<div style="clear:both;"></div>

		<div style="width:100%;">
			<form method="POST" enctype="multipart/form-data" action="" accept-charset="utf-8" onsubmit="return check();">
			<table class="form-table">
				<tbody>
				<tr class="form-field">
					<th scope="row"><label for="role">Role</label></th>
					<td>
					<select name="role" id="role">
						<?php 
							$list_roles = acui_get_editable_roles(); 
							foreach ($list_roles as $key => $value) {
								if($key == "subscriber")
									echo "<option selected='selected' value='$key'>$value</option>";
								else
									echo "<option value='$key'>$value</option>";
							}
						?>
					</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label>CSV file <span class="description">(required)</span></label></th>
					<td><input type="file" name="uploadfiles[]" id="uploadfiles" size="35" class="uploadfiles" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label>What should do the plugin with empty cells?</label></th>
					<td>
						<select name="empty_cell_action">
							<option value="delete">Delete the metadata</option>
							<option value="leave">Leave the old value for this metadata</option>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label><strong>(Only for <a href="https://wordpress.org/plugins/wp-members/">WP Members</a> users)</strong> Activate user at the same time is being created</label></th>
					<td>
						<select name="activate_users_wp_members">
							<option value="no_activate">Do not activate users</option>
							<option value="activate">Activate users when they are being imported</option>
						</select>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="user_login">Send mail</label></th>
					<td>
						<p>Do you wish to send a mail with credentials to new users? <input type="checkbox" name="sends_email" value = "yes" onclick="showMe('email_div')"></p>
						<div id="email_div" style="display:none">
						<p>From name: <input name="from_name" size="100" value="<?php echo $from_name; ?>" id="title" autocomplete="off" type="text"></p>
						<p>From email : <input name="from_email" size="100" value="<?php echo $from_email; ?>" id="title" autocomplete="off" type="text"></p>
						<p>Mail subject : <input name="subject_mail" size="100" value="<?php echo $subject_mail; ?>" id="title" autocomplete="off" type="text"></p>
						<?php wp_editor( $body_mail , 'body_mail'); ?>
						**username** = username to login - **password** = user password - **loginurl** = current site login url, for example www.site.com/wp-login.php<br/>
						You can also use any WordPress user standard field, if you have used it in your CSV. For example, if you have a first_name column, you could use **first_name**
						</div>
					</td>
				</tr>
				</tbody>
			</table>
			<input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Start importing"/>
			</form>
		</div>

		<div style="clear:both; width:100%;"></div>

		<?php 
		$headers = get_option("acui_columns"); 

		if(is_array($headers) && !empty($headers)):
		?>

		<h3>Custom columns loaded</h3>
		<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row">Columns loaded in previous files</th>
				<td><small><em>(if you load another CSV with different columns, the new ones will replace this list)</em></small>
					<ol>
						<?php foreach ($headers as $column): ?>
							<li><?php echo $column; ?></li>
						<?php endforeach; ?>						
					</ol>
				</td>
			</tr>
		</tbody></table>

		<?php endif; ?>

		<h3>Doc</h3>
		<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row">Columns position</th>
				<td><small><em>(Documents should look like the one presented into screenshot. Remember you should fill the first two columns with the next values)</em></small>
					<ol>
						<li>Username</li>
						<li>Email</li>
					</ol>						
					<small><em>(The next columns are totally customizable and you can use whatever you want. All rows must contains same columns)</em></small>
					<small><em>(User profile will be adapted to the kind of data you have selected)</em></small>
					<small><em>(If you want to disable the extra profile information, please deactivate this plugin after make the import)</em></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Passwords</th>
				<td>A string that contains user passwords. We have different options for this case:
					<ul style="list-style:disc outside none; margin-left:2em;">
						<li>If user is created: if you set a value for the password, it will be used; if not, it will be generated</li>
						<li>If user is updated: 
							<ul style="list-style:disc outside none;margin-left:2em;">
								<li>If you <strong>don't create a column for passwords</strong>: passwords will be generated automatically</li>
								<li>If you <strong>create a column for passwords</strong>: if cell is empty, password won't be updated; if cell has a value, it will be used</li>
							</ul>
					</ul>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">WordPress default profile data</th>
				<td>You can use those labels if you want to set data adapted to the WordPress default user columns (the ones who use the function <a href="http://codex.wordpress.org/Function_Reference/wp_update_user">wp_update_user</a>)
					<ol>
						<li><strong>user_nicename</strong>: A string that contains a URL-friendly name for the user. The default is the user's username.</li>
						<li><strong>user_url</strong>: A string containing the user's URL for the user's web site.	</li>
						<li><strong>display_name</strong>: A string that will be shown on the site. Defaults to user's username. It is likely that you will want to change this, for both appearance and security through obscurity (that is if you don't use and delete the default admin user).</li>
						<li><strong>nickname</strong>: The user's nickname, defaults to the user's username.	</li>
						<li><strong>first_name</strong>: The user's first name.</li>
						<li><strong>last_name</strong>: The user's last name.</li>
						<li><strong>description</strong>: A string containing content about the user.</li>
						<li><strong>jabber</strong>: User's Jabber account.</li>
						<li><strong>aim</strong>: User's AOL IM account.</li>
						<li><strong>yim</strong>: User's Yahoo IM account.</li>
						<li><strong>user_registered</strong>: Using the WordPress format for this kind of data Y-m-d H:i:s.</li>
					</ol>
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
			<td>Download this <a href="<?php echo plugins_url() . "/import-users-from-csv-with-meta/test.csv"; ?>">.csv file</a> to test</td> 
			</tr>
		</tbody></table>
		<br/>
		<div style="width:775px;margin:0 auto"><img src="<?php echo plugins_url() . "/import-users-from-csv-with-meta/csv_example.png"; ?>"/></div>
	</div>
	<script type="text/javascript">
	function check(){
		if(document.getElementById("uploadfiles").value == "") {
		   alert("Please choose a file");
		   return false;
		}
	}

	function showMe (box) {
	    var chboxs = document.getElementsByName("sends_email");
	    var vis = "none";
	    for(var i=0;i<chboxs.length;i++) { 
	        if(chboxs[i].checked){
	         vis = "block";
	            break;
	        }
	    }
	    document.getElementById(box).style.display = vis;
	}

	jQuery( document ).ready( function( $ ){
		$( ".delete_attachment" ).click( function(){
			var answer = confirm( "Are you sure to delete this file?" );
			if( answer ){
				var data = {
					'action': 'acui_delete_attachment',
					'attach_id': $( this ).attr( "attach_id" )
				};

				$.post(ajaxurl, data, function(response) {
					if( response != 1 )
						alert( "There were problems deleting the file, please check file permissions" );
					else{
						alert( "File successfully deleted" );
						document.location.reload();
					}
				});
			}
		});

		$( "#bulk_delete_attachment" ).click( function(){
			var answer = confirm( "Are you sure to delete ALL CSV files uploaded? There can be CSV files from other plugins." );
			if( answer ){
				var data = {
					'action': 'acui_bulk_delete_attachment',
				};

				$.post(ajaxurl, data, function(response) {
					if( response != 1 )
						alert( "There were problems deleting the files, please check files permissions" );
					else{
						alert( "Files successfully deleted" );
						document.location.reload();
					}
				});
			}
		});

	} );
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
function acui_fileupload_process( $form_data ) {
  $uploadfiles = $_FILES['uploadfiles'];
  $role = $form_data["role"]; 
  
  if ( is_array($uploadfiles) ) {

	foreach ( $uploadfiles['name'] as $key => $value ) {

	  // look only for uploded files
	  if ($uploadfiles['error'][$key] == 0) {
		$filetmp = $uploadfiles['tmp_name'][$key];

		//clean filename and extract extension
		$filename = $uploadfiles['name'][$key];

		// get file info
		// @fixme: wp checks the file extension....
		$filetype = wp_check_filetype( basename( $filename ), array('csv' => 'text/csv') );
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
		
		acui_import_users($filedest, $form_data, $attach_id);
	  }
	}
  }
}

function acui_extra_user_profile_fields( $user ) {
	global $wp_users_fields;
	global $wp_min_fields;

	$headers = get_option("acui_columns");
	if( is_array($headers) && !empty($headers) ):
?>
	<h3>Extra profile information</h3>
	
	<table class="form-table"><?php

	foreach ($headers as $column):
		if(in_array($column, $wp_min_fields) || in_array($column, $wp_users_fields))
			continue;
	?>
		<tr>
			<th><label for="<?php echo $column; ?>"><?php echo $column; ?></label></th>
			<td><input type="text" name="<?php echo $column; ?>" id="<?php echo $column; ?>" value="<?php echo esc_attr(get_the_author_meta($column, $user->ID )); ?>" class="regular-text" /></td>
		</tr>
		<?php
	endforeach;
	?>
	</table><?php
	endif;
}

function acui_save_extra_user_profile_fields( $user_id ){
	if (!current_user_can('create_users', $user_id)) { return false; }

	global $wp_users_fields;
	global $wp_min_fields;
	$headers = get_option("acui_columns");

	$post_filtered = filter_input_array( INPUT_POST );

	if( is_array($headers) && count($headers) > 0 ):
		foreach ($headers as $column){
			if(in_array($column, $wp_min_fields) || in_array($column, $wp_users_fields))
				continue;

			$column_sanitized = str_replace(" ", "_", $column);
			update_user_meta( $user_id, $column, $post_filtered[$column_sanitized] );
		}
	endif;
}

function acui_modify_user_edit_admin(){
	global $pagenow;

	if(in_array($pagenow, array("user-edit.php", "profile.php"))){
    	$acui_columns = get_option("acui_columns");
    	
    	if(is_array($acui_columns) && !empty($acui_columns)){
        	$new_columns = array();
        	$core_fields = array(
	            'username',
	            'user_email',
	            'first_name',
	            'role',
	            'last_name',
	            'nickname',
	            'display_name',
	            'description',
	            'billing_first_name',
	            'billing_last_name',
	            'billing_company',
	            'billing_address_1',
	            'billing_address_2',
	            'billing_city',
	            'billing_postcode',
	            'billing_country',
	            'billing_state',
	            'billing_phone',
	            'billing_email',
	            'shipping_first_name',
	            'shipping_last_name',
	            'shipping_company',
	            'shipping_address_1',
	            'shipping_address_2',
	            'shipping_city',
	            'shipping_postcode',
	            'shipping_country',
	            'shipping_state'
        	);
        
        	foreach ($acui_columns as $key => $column) {
            	
            	if(in_array($column, $core_fields)) {
                	// error_log('removing column because core '.$column);
                	continue;
            	}
            	if(in_array($column, $new_columns)) {
                	// error_log('removing column because not unique '.$column);
                	continue;
                }
            	
            	array_push($new_columns, $column);
        	}
        	
        	update_option("acui_columns", $new_columns);
 		}
 	}
}

function acui_delete_attachment() {
	$attach_id = intval( $_POST['attach_id'] );

	$result = wp_delete_attachment( $attach_id, true );

	if( $result === false )
		echo 0;
	else
		echo 1;

	wp_die();
}

function acui_bulk_delete_attachment(){
	$args_old_csv = array( 'post_type'=> 'attachment', 'post_mime_type' => 'text/csv', 'post_status' => 'inherit', 'posts_per_page' => -1 );
	$old_csv_files = new WP_Query( $args_old_csv );
	$result = 1;

	while($old_csv_files->have_posts()) : 
		$old_csv_files->the_post(); 

		if( wp_delete_attachment( get_the_ID(), true ) === false )
			$result = 0;
	endwhile;
	
	wp_reset_postdata();

	echo $result;

	wp_die();
}
	
register_activation_hook(__FILE__,'acui_init'); 
register_deactivation_hook( __FILE__, 'acui_deactivate' );
add_action("plugins_loaded", "acui_init");
add_action("admin_menu", "acui_menu");
add_action('admin_init', 'acui_modify_user_edit_admin' );
add_action("show_user_profile", "acui_extra_user_profile_fields");
add_action("edit_user_profile", "acui_extra_user_profile_fields");
add_action("personal_options_update", "acui_save_extra_user_profile_fields");
add_action("edit_user_profile_update", "acui_save_extra_user_profile_fields");
add_action( 'wp_ajax_acui_delete_attachment', 'acui_delete_attachment' );
add_action( 'wp_ajax_acui_bulk_delete_attachment', 'acui_bulk_delete_attachment' );

// misc
if (!function_exists('str_getcsv')) { 
    function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = '\\', $eol = '\n') { 
        if (is_string($input) && !empty($input)) { 
            $output = array(); 
            $tmp    = preg_split("/".$eol."/",$input); 
            if (is_array($tmp) && !empty($tmp)) { 
                while (list($line_num, $line) = each($tmp)) { 
                    if (preg_match("/".$escape.$enclosure."/",$line)) { 
                        while ($strlen = strlen($line)) { 
                            $pos_delimiter       = strpos($line,$delimiter); 
                            $pos_enclosure_start = strpos($line,$enclosure); 
                            if ( 
                                is_int($pos_delimiter) && is_int($pos_enclosure_start) 
                                && ($pos_enclosure_start < $pos_delimiter) 
                                ) { 
                                $enclosed_str = substr($line,1); 
                                $pos_enclosure_end = strpos($enclosed_str,$enclosure); 
                                $enclosed_str = substr($enclosed_str,0,$pos_enclosure_end); 
                                $output[$line_num][] = $enclosed_str; 
                                $offset = $pos_enclosure_end+3; 
                            } else { 
                                if (empty($pos_delimiter) && empty($pos_enclosure_start)) { 
                                    $output[$line_num][] = substr($line,0); 
                                    $offset = strlen($line); 
                                } else { 
                                    $output[$line_num][] = substr($line,0,$pos_delimiter); 
                                    $offset = ( 
                                                !empty($pos_enclosure_start) 
                                                && ($pos_enclosure_start < $pos_delimiter) 
                                                ) 
                                                ?$pos_enclosure_start 
                                                :$pos_delimiter+1; 
                                } 
                            } 
                            $line = substr($line,$offset); 
                        } 
                    } else { 
                        $line = preg_split("/".$delimiter."/",$line); 

                        /* 
                         * Validating against pesky extra line breaks creating false rows. 
                         */ 
                        if (is_array($line) && !empty($line[0])) { 
                            $output[$line_num] = $line; 
                        }  
                    } 
                } 
                return $output; 
            } else { 
                return false; 
            } 
        } else { 
            return false; 
        } 
    } 
} 

if (!function_exists('set_html_content_type')) { 
	function set_html_content_type() {
		return 'text/html';
	}
}