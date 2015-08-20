<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// path to your composer autoloader
require ('vendor/autoload.php');

$integrations = array(
	'xing' => array(
		'consumer_key' => 'fake xing consumer key',
		'consumer_secret' => 'fake xing counsumer secret'
		)
	);

if (file_exists('init.real.php')) {
	// this is small trick to hide real working credentials from Git
	// on practice you will not need this
	include('init.real.php');
}

session_start();
