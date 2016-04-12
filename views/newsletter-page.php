<?php
$confirmationMessage = Sailthru_Admin_Interface::get_sailthru_default_message();
$descriptionMessage = Sailthru_Admin_Interface::get_sailthru_default_description();
the_widget(
	'Sailthru_Newsletter_Widget',
	array(
		'title' => '',
		'description' => $descriptionMessage,
		'confirmation' => $confirmationMessage,
		'list' => urldecode(get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT)),
	),
	array(
		'before_widget' => '<div class="sailthru-newsletter">',
		'after_widget' => '</div>',
		'before_title' => '',
		'after_title' => ''
	)
);