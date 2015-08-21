## Gelembjuk/Xing package

PHP Package to work with Xing API

### Configuration

To use the class you need to get consumer key and consumer secret. Go to https://dev.xing.com/ to create your application

```php
	$consumer_key = 'your consumer key';
	$consumer_secret = 'your consumer secret';
	
	$xing = new Gelembjuk\Xing\Xing($consumer_key,$consumer_secret);

```

### Usage

```php
	
	$xing = new Gelembjuk\Xing\Xing($consumer_key,$consumer_secret);
	
	// access token can be get with oAuth login process and should be stored somewhere
	// for example in the DB
	$xing->setToken($access_token);
	
	// get my xing profile (profile of a user who is loged in)
	$me = $xing->getMe();
	
	// get 3 users with name John Smith
	$foundusers = $xing->getFindusers('John Smith','3');
	
	// get full profile of found users
	foreach ($foundusers['users']['items'] as $user) {
		$userrecord = 	$xing->getUser($user['user']['id']);
		echo $userrecord['display_name'].' with ID '.$userrecord['id'].'<br>';
	}

```

### Authentification 

Xing API uses oAuth1 authentification process. Result of the process is access token to use for API access

Start of oauth1 process. Get a link to redirect a user to xing to confirm login

```php
	
	// this is the url of your web site where to redirect a user from xing after he confirmed his login
	$redirecturl = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/?action=completelogin&';

	$xing = new Gelembjuk\Xing\Xing($consumer_key,$consumer_secret,$redirecturl);
	
	// get xing url to where to forward a user
	$redirect = $xing->getAuthUrl();

	// remember temporary credentials to use on next step
	$_SESSION['temporary_credentials'] = $xing->getTempCredentials();
	
	header('Location: '.$redirect);
    exit;

```

End of oAuth1 process. A user is redirected from xing back to your web site

```php
	
	$xing = new Gelembjuk\Xing\Xing($consumer_key,$consumer_secret,$redirecturl);
	
	$xing->setTempCredentials($_SESSION['temporary_credentials']);
	
	// xing sends back 2 values to complete teh process
	$xing->completeAuth($_GET['oauth_token'], $_GET['oauth_verifier']);
	
	$access_token = $xing->getToken();
	// $access_token is long time access token. save it somewhere for a user 
	// if you want to use Xing API later again for this user
```

### Supported API

```php
 * The class implements Xing API.
 * https://dev.xing.com/docs/resources
 * 
 * 4 types of requests are supported: GET,POST,PUT and DELETE
 * 
 * for each function list of arguments is same as for relevant API function
 * getMe() -> /v1/users/me
 * getUser($id,$fields) -> /v1/users/:id'
 * getMecard() -> /v1/users/me/id_card
 * getFindusers($keywords,$limit,$offset,$user_fields) -> /v1/users/find
 * getFindbyemail($emails,$hash_function,$user_fields) -> /v1/users/find_by_emails
 * getProfilemessage($user_id) -> /v1/users/:user_id/profile_message
 * get0Llegalinfo($user_id) -> /v1/users/:user_id/legal_information
 * getJob($id,$user_fields) -> /v1/jobs/:id
 * 
 */
 
```

### Author

Roman Gelembjuk (@gelembjuk)

