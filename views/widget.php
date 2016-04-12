<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Jon
 * Date: 3/7/13
 * Time: 2:48 PM
 * To change this template use File | Settings | File Templates.
 */
$selected_newsletters = $instance["list"];
$test = count($selected_newsletters);
  global $json_tsm_api;
  if ($json_tsm_api->webservice) {
      $json_post = new JSON_API_Post(null, false);
      $data[] = $json_post;
      $selected_lists = array();
	  $instance["list"] = array_filter( (array)$instance["list"] );
      foreach($instance["list"] as $key => $value) {
      	$selected_lists[] = $value;
      }
	    $json_post->set_value('title',$instance['title'],true);
	    $json_post->set_value('description', $instance['description'],true);
      $json_post->set_value('confirmation', $instance['confirmation'],true);
      if (count($selected_newsletters)>1){
      	$json_post->set_value('multilist', 'multilist',true);
		  $json_post->set_value('lists', $selected_lists,true);
      }else{
		  $json_post->set_value('lists', $selected_lists[0],true);
	  }

      $st_instance_lists['data'] = $data;
      $json_tsm_api->dynamic_leads = $st_instance_lists;
      return;
  }

$return = '';
$selectfield = '';
if ( is_array( $selected_newsletters ) && count( $selected_newsletters ) > 1 ) {
	foreach($selected_newsletters as $key=>$value) {
		$dropdown .= '<option value="' .$value. '"> ' .$value. '</option>';
	}
	$selectfield = '<select name="list">'.$dropdown.'</select><input class="sailthru-email" type="text" name="email" value="" placeholder="email address" />';
} else {
	$selectfield = '<input class="sailthru-email" type="text" name="email" value="" placeholder="email address" />';
}


$form = '<div class="gform_wrapper">
				<form action="" method="post">
					%body%
				</form>
			</div>';
$list = '<input type="hidden" name="list" value="' . $instance['list'] . '" />';
if ( is_array( $selected_newsletters ) ) {
	if( count( $selected_newsletters ) > 1 ){
		$list = '';
	}else{
		$list = '<input type="hidden" name="list" value="' . $instance['list'][0] . '" />';
	}
}

$submitbtn = '<input type="submit" class="button sailthru-button" value="SIGN UP NOW" />';

$form_body = '<div class="gform_heading">
					<span class="gform_description">' . $instance['description'] . '</span>
				</div>
				<div class="sailthru-newsletter">
						%selectField%
					<div class="default-button">
						%hiddenList%
						<input type="hidden" name="' . self::PREFIX_LOG . '-' . 'is_submitted" value="true" />
						%submitbtn%
					</div>
				</div>';

if(empty($_POST) && !$_POST[self::PREFIX_LOG . '-is_submitted']) {
	$content = str_replace(
		array(
			'%body%', '%hiddenList%', '%selectField%', '%submitbtn%'
		), array(
			$form_body, $list, $selectfield, $submitbtn
		),
		$form
	);
} else {
	try { //b if list does not exist, automatically created. this is exploitable.
		if (self::$post_valid_email) {
			if(self::$post_user_is_subscribed) {
				$selectfield = '';
				$list = '';
				$submitbtn = '';
				$content = str_replace(
					array('%body%', '%hiddenList%', '%selectField%', '%submitbtn%'),
					array(
						$form_body . '<span class="gform_error">You have already subscribed to this newsletter.</span>', $list, $selectfield, $submitbtn
					),
					$form
				);
			} else {
				if(get_option(Sailthru_Admin_Interface::IS_ACTIVE_PROMOTE_VIP,1) && get_option('loyalty_plugin_is_activate') && empty(self::$post_user->ID) && !is_user_logged_in()) {
					$content = '<div class="gform_wrapper gform_heading">
									<span class="gform_description completed">' . $instance['confirmation'] . '<br/><br/>Would you like to become a full member? Sign up for a free account to win points and prizes in our VIP club. We will give you <b>2500 points</b> to get started!<br/><br/>
									<p class="more_act default-button"><a class="fb-auth-registration-link" href="#fb-auth-registration">SIGN UP NOW</a></p>
									</span>
								</div>';
				} else {
					$content = '<div class="gform_wrapper gform_heading">
									<span class="gform_description completed">' . $instance['confirmation'] . '</span>
								</div>';
				}
			}
		} else {
			$content = str_replace(
				array(
					'%body%', '%hiddenList%', '%selectField%', '%submitbtn%'
				),
				array(
					$form_body . '<span class="gform_error">You need to submit a valid email address.</span>', $list, $selectfield, $submitbtn
				),
				$form
			);
		}

	} catch (Exception $e) {
		$content = '<div class="gform_heading">
						<span class="gform_description">We could not subscribe you at this time. Please try again later.</span>
					</div>
					<textarea style="display:none;">' . $e->getMessage() . '</textarea>';
	}
}