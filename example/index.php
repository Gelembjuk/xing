<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This is the file index.php . It shows a user login status and displays login links if a user is not in a system yet
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// settings and composer autoloader connection are in a separate file
require('init.php');

if($_REQUEST['action'] == 'logout') {
	unset($_SESSION['token']);
	
	header('Location: index.php');
        exit;
} elseif ($_SESSION['token']) {
	$xing = new \Gelembjuk\Xing\Xing($integrations['xing']['consumer_key'],$integrations['xing']['consumer_secret']);
	$xing->setToken($_SESSION['token']);
	
	$user = $xing->getMe();
	
	// user is already in the system. Show view for authorized users
	echo '<h2>Hello '.$user['display_name'].'</h2>';
	echo '<p> <a href="index.php?action=logout">Logout</a></p>';
	
	echo '<p>Run tests:</p>';

	// find 3 users with name last name Berger
	echo 'Find users by keyword `Berger`:<br>';
	$foundusers = $xing->getFindusers('Berger','3');
	
	echo 'Found ' . $foundusers['users']['total'].' users<br>';
	
	foreach ($foundusers['users']['items'] as $user) {
		$userrecord = 	$xing->getUser($user['user']['id']);
		echo $userrecord['display_name'].' with ID '.$userrecord['id'].'<br>';
	}
	
	exit;
} elseif($_REQUEST['action'] == 'login') {

	$redirecturl = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/index.php?action=completelogin&';

	$xing = new \Gelembjuk\Xing\Xing($integrations['xing']['consumer_key'],$integrations['xing']['consumer_secret'],$redirecturl);
	
	$redirect = $xing->getAuthUrl();

	$_SESSION['temporary_credentials'] = $xing->getTempCredentials();
	
	header('Location: '.$redirect);
        exit;
} elseif($_REQUEST['action'] == 'completelogin') {
	
	$xing = new \Gelembjuk\Xing\Xing($integrations['xing']['consumer_key'],$integrations['xing']['consumer_secret']);
	
	$xing->setTempCredentials($_SESSION['temporary_credentials']);
	
	$xing->completeAuth($_GET['oauth_token'], $_GET['oauth_verifier']);
	
	$_SESSION['token'] = $xing->getToken();
	
	header('Location: index.php');
        exit;
} else {
	// user is not authorised. Show login options for him
	echo '<h2>Hello Guest</h2>';
	
	echo '<li><a href="index.php?action=login">Login with xing</a></li>';
	
	echo '</ul>';
	
}
