<?php

global $wpdb;

$user = get_user_by('email', (string) $_REQUEST['email']);
$result = @get_user_meta( $user->ID );
$market = townsquare_get_market();

switch("all") {
	case 'all': // all lists per blog id
		$blog_ids = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM ts_site_newsletter WHERE market = '$market'"));
		foreach ( $blog_ids as $blog_id )
		{
			$bid = $blog_id->blog_id;
			if(is_array($result)){
				foreach($result as $row_name => $row_value) {
					$pattern = '/^ns_'.$bid.'_(.*)/';
					if(preg_match($pattern, $row_name, $matches)) {
						update_user_meta($user->ID, $row_name, '0');
					}
				}
			}
		}
		break;
	case 'blast':
		foreach($result as $row_name => $row_value) {
			$listupdated = str_replace("+", "\+", urlencode($_REQUEST['list']));
			$pattern = '/^ns_(.*)_' . $listupdated . '/';
			if(preg_match($pattern, $row_name)) {
				update_user_meta($user->ID, $row_name, '0');
			}
		}
		break;
}


