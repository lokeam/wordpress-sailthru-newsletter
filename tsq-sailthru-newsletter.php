<?php
/*
Plugin Name: TSQ Sailthru Newsletter Plugin
Plugin URI: http://http://townsquaremedia.com/
Description: To be written
Author: Townsquare Digital
Version: 1
Author URI: http://www.townsquaremedia.com
*/

include_once(dirname(__FILE__) . '/includes/My_Sailthru.php');
include_once(dirname(__FILE__) . '/Sailthru_Newsletter.php');
include_once(dirname(__FILE__) . '/Sailthru_Newsletter_Widget.php');
include_once(dirname(__FILE__) . '/Sailthru_Admin_Interface.php');

SailThru_Newsletter::init();
SailThru_Newsletter_Widget::init();
Sailthru_Admin_Interface::init();