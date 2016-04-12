<?php

require_once(''.$_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
require_once(''.$_SERVER['DOCUMENT_ROOT'].'/wp-includes/template-loader.php');

if (isset($_POST['sailthru_settings'])) {

	if( !isset( $_POST[ Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE ] ) ){
		$_POST[ Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE ] = 'no';
	}

	update_option(Sailthru_Admin_Interface::SAILTHRU_MARKET, $_POST[Sailthru_Admin_Interface::SAILTHRU_MARKET]);
	update_option(Sailthru_Admin_Interface::SAILTHRU_API_KEY, $_POST[Sailthru_Admin_Interface::SAILTHRU_API_KEY]);
	update_option(Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME, $_POST[Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME]);
	update_option(Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP, $_POST[Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP]);

	if( get_option( Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE ) !== $_POST[ Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE ] ){
		$cache = get_blog_option( 1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE );
		unset( $cache[ $_POST[ Sailthru_Admin_Interface::SAILTHRU_API_KEY ] ] );
		update_blog_option( 1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE, $cache );
	}

	update_option(Sailthru_Admin_Interface::SAILTHRU_SECRET_KEY, $_POST[Sailthru_Admin_Interface::SAILTHRU_SECRET_KEY]);
	update_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT, $_POST[Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT]);
	update_option(Sailthru_Admin_Interface::SAILTHRU_WELCOME_TEMPLATE, $_POST[Sailthru_Admin_Interface::SAILTHRU_WELCOME_TEMPLATE]);
	update_option(Sailthru_Admin_Interface::SAILTHRU_MESSAGE_DEFAULT, $_POST[Sailthru_Admin_Interface::SAILTHRU_MESSAGE_DEFAULT]);
	update_option(Sailthru_Admin_Interface::SAILTHRU_DESCRIPTION_DEFAULT, $_POST[Sailthru_Admin_Interface::SAILTHRU_DESCRIPTION_DEFAULT]);
	update_option(Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE, $_POST[Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE]);

} elseif (isset($_POST['sailthru_file_sync'])) {
	if ($_FILES["file"]["error"] > 0)
		echo "Error: " . $_FILES["file"]["error"] . "<br />";
	else
		Sailthru_Admin_Interface::handleCreateMarketTable();
}

$welcome_template = get_option(Sailthru_Admin_Interface::SAILTHRU_WELCOME_TEMPLATE);
if($welcome_template === false) {
	$welcome_template = 'Welcome Email';
}

?>

<ul id="menu" class="menu">
	<li class="active"><a href="#description">Settings</a></li>
	<li><a href="#usage">Create/Update market table</a></li>
	<li><a href="#sync">Import Users</a></li>
	<li><a href="#horizon">Horizon Status</a></li>
</ul>
<div id="description" class="content">
	<?php screen_icon('themes'); ?> <h2>Sailthru Settings</h2>
	<br/>
	<form id="sailthru-main-form" method="POST" action="">
		<input type="hidden" name="sailthru_settings" value="1" />
		<div id="sailthru_market_loader">Loading values....</div>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME; ?>">
						Activate Sailthru Widget:
					</label>
				</th>
				<td>
					<input class="checkbox" type="checkbox" value="1" <?php checked( get_option(Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME), 1 ); ?> id="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME; ?>" name="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME; ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP; ?>">
						Promote VIP:
					</label>
				</th>
				<td>
					<input class="checkbox" type="checkbox" value="1" <?php checked( get_option(Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP, 1), 1 ); ?> id="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP; ?>" name="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP; ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::SAILTHRU_MARKET; ?>">
						Sailthru Market:
					</label>
				</th>
				<td>
					<select name="<?php echo Sailthru_Admin_Interface::SAILTHRU_MARKET; ?>" id="<?php echo Sailthru_Admin_Interface::SAILTHRU_MARKET; ?>" onchange="populateKeysAndLists();" >
						<?php
						$sailthruDefaultFeedValue = get_option(Sailthru_Admin_Interface::SAILTHRU_MARKET);
						$markets = wp_cache_get('sailthru_markets');
						if ( !$markets ) {
							$markets = Sailthru_Admin_Interface::get_markets();
							wp_cache_set( 'sailthru_markets', $markets);
						}
						foreach ($markets as $key){
							$option = '<option '.selected(str_replace("_"," ",$key->market),$sailthruDefaultFeedValue)  .'value="'.$key->market.'">';
							$option .= str_replace("_"," ",$key->market);
							$option .= '</option>';
							echo $option;
						}
						?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::SAILTHRU_API_KEY; ?>">
						Sailthru API Key:
					</label>
				</th>
				<td>
					<input type="text" name="<?php echo Sailthru_Admin_Interface::SAILTHRU_API_KEY; ?>" id="<?php echo Sailthru_Admin_Interface::SAILTHRU_API_KEY; ?>" value="<?php echo Sailthru_Admin_Interface::get_sailthru_api_key(); ?>" size="25" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::SAILTHRU_SECRET_KEY; ?>">
						Sailthru Secret Key:
					</label>
				</th>
				<td>
					<input type="text" name="<?php echo Sailthru_Admin_Interface::SAILTHRU_SECRET_KEY; ?>" id="<?php echo Sailthru_Admin_Interface::SAILTHRU_SECRET_KEY; ?>" value="<?php echo Sailthru_Admin_Interface::get_sailthru_secret_key(); ?>" size="25" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label><?php _e('Default List'); ?></label></th>
				<td>
					<select name="<?php echo Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT; ?>" id="<?php echo Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT; ?>" data-option-value="<?php echo get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT); ?>"></select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label><?php _e('Enable Primary List Filtering'); ?></label></th>
				<td>
					<input class="checkbox" type="checkbox" value="yes" <?php checked( get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE, "yes"), "yes" ); ?> id="<?php echo Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE; ?>" name="<?php echo Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE; ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::SAILTHRU_WELCOME_TEMPLATE; ?>">
						Sailthru Welcome Template:
					</label>
				</th>
				<td>
					<input type="text" name="<?php echo Sailthru_Admin_Interface::SAILTHRU_WELCOME_TEMPLATE; ?>" id="<?php echo Sailthru_Admin_Interface::SAILTHRU_WELCOME_TEMPLATE; ?>" value="<?php echo $welcome_template; ?>" size="25" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::SAILTHRU_MESSAGE_DEFAULT; ?>">
						Sailthru Default Message:
					</label>
				</th>
				<td>
					<input type="text" name="<?php echo Sailthru_Admin_Interface::SAILTHRU_MESSAGE_DEFAULT; ?>" value="<?php echo Sailthru_Admin_Interface::get_sailthru_default_message(); ?>" size="40" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::SAILTHRU_DESCRIPTION_DEFAULT; ?>">
						Sailthru Default Description:
					</label>
				</th>
				<td>
					<input type="text" name="<?php echo Sailthru_Admin_Interface::SAILTHRU_DESCRIPTION_DEFAULT; ?>" value="<?php echo Sailthru_Admin_Interface::get_sailthru_default_description(); ?>" size="40" />
				</td>
			</tr>
		</table>
		<p>
			<input type="submit" value="Submit settings" class="button-primary"/>
		</p>
	</form>
</div>

<div id="usage" class="content">
	<?php
	if (isset($_POST['sailthru_file_sync'])) {
		if ($_FILES["file"]["error"] > 0)
			echo "Error: " . $_FILES["file"]["error"] . "<br />";
		else
			echo "<div style='color: green; font-size: 14px;font-weight:bold;'>Successfully Imported</div>";
	}
	?>
	<h2>Upload File</h2>
	<form id="sailthru-main-form" method="POST" action="" enctype="multipart/form-data">
		<input type="hidden" name="sailthru_file_sync" value="1" />
		<input type="file" name="file" id="market_file" value="<?php echo $_FILES['file']['name'];?>" />
		<p>
			<input type="submit" value="Submit" class="button-primary"/>
		</p>
	</form>
</div>


<div id="sync" class="content">
	<form id="sailthru-main-form" onsubmit="return handleFormSubmit();" method="POST" action="<?php echo plugin_dir_url(__FILE__).'/fupload.php';?>"  ENCTYPE="multipart/form-data" target="upload_target" >

		<input type="hidden" name="sailthru_db_sync" value="1" />
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME; ?>">
						Markets
					</label>
				</th>
				<td>
					<select name="sailthru_dir" id="sailthru_dir" onchange="populateFiles();">
						<?php
						$markets = wp_cache_get('sailthru_markets');
						if ( !$markets ) {
							$markets = Sailthru_Admin_Interface::get_markets();
							wp_cache_set( 'sailthru_markets', $markets);
						}
						foreach ($markets as $key){
							$option ='<option value="'.$key->market.'">';
							$option .= str_replace("_"," ",$key->market);
							$option .= '</option>';
							echo $option;
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME; ?>">
						Sailthru List
					</label>
				</th>
				<td>
					<?php
					echo "<script>";
					echo "var serverFilesList= ".json_encode($dirs_and_files);
					echo "</script>";
					?>
					<select name="sailthru_files" id="sailthru_files">
					</select>
					<div id="list_loader">Loading...</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="<?php echo Sailthru_Admin_Interface::IS_ACTIVE_OPTION_NAME; ?>">
						File CSV:
					</label>
				</th>
				<td>
					<input type="file" name="usersCSVFile" id="usersCSVFile" value="<?php echo $_FILES['file']['name'];?>" />
				</td>
			</tr>
		</table>
		<p>
			<a href="#" class="button" id="sync-db">Submit</a>
		</p>
		<div class="confirm_screen">
			<p class="confirm_text"> Are you sure you want to update the user to DB?</p>
			<div class="confirm_buttons">
				<input type="submit" value="Ok" class="button-primary"/>
				<a class="button" href="#">Cancel</a>
			</div>
		</div>
	</form>
	<div id="refresh"><a href="#" onclick="reload_iframe_for_latest_log();">Refresh</a></div>
	<iframe id="upload_target" name="upload_target" src="#" style="width:0;height:0;border:0px solid #fff;" onload="file_upload_done_update();"></iframe>
	<div id="sailthru_loader">
		Loading...
	</div>
	<iframe id="sailthru_response" src="<?php echo plugin_dir_url(__FILE__).'/file_update.php?command=print';?>" width="400" height="400" onerror="reload_iframe_for_latest_log();"></iframe>
</div>

<div id="horizon" class="content">

</div>

<div id="error">
</div>

<?php
$tsq_newletter_plugin_path = plugin_dir_url(__FILE__);
?>
<script>
	function reload_iframe_for_latest_log(){
		var url = "<?php echo plugin_dir_url(__FILE__).'/file_update.php?command=print' ?>";
//		console.log('This message was sent by a timer.');
		jQuery("#sailthru_response").attr("src", url);
	}
</script>
