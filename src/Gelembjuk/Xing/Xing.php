<?php

/**
 * The class makes auth to the Xing API,executes some basic API calls
 * and helps to create API objects, works like a factory
 */

namespace Gelembjuk\Xing;

class Xing extends XingOAuth
{
    protected $token;
    protected $temporarycredentials = null;
    
    public function __construct($consumer_key, $consumer_secret, $callback_url = '')
    {
        $credentials = array(
            'identifier' => $consumer_key,
            'secret' => $consumer_secret
        );

        if ($callback_url != '') {
            $credentials['callback_uri'] = $callback_url;
        }

        parent::__construct($credentials);
    }

    public function setRedirectUrl($redirect_url)
    {
        $this->clientCredentials->setCallbackUri($redirect_url);
    }

    public function getTempCredentials()
    {
        return $this->temporarycredentials;
    }
    public function setTempCredentials($temporarycredentials)
    {
        $this->temporarycredentials = $temporarycredentials;
    }

    public function getToken()
    {
        return $this->token;
    }
    
    public function setToken($token)
    {
        $this->token = $token;
    }

    public function checkToken($token = '')
    {
        if ($token == '') {
            $token = $this->token;
        }
        
        try {
            $user = $this->getUserDetails($token);
            return true;
        } catch (Exception $e) {
        }

        return false;
    }
    
    public function getAuthUrl()
    {
        $this->temporarycredentials = $this->getTemporaryCredentials();
        return $this->getAuthorizationUrl($this->temporarycredentials);
    }
    public function completeAuth($auth_token, $auth_verifier)
    {
        $this->token = $this->getTokenCredentials($this->temporarycredentials, $auth_token, $auth_verifier);
        
        return true;
    }
    
    public function getUserProfile()
    {
        return $this->getUserDetails($this->token);
    }
    public function __call($name, $arguments)
    {
	static $allapimethods = null;
	
	if (!is_array($allapimethods)) {
		$allapimethods = array(
			'GET' => array(
				'user' => array('/v1/users/:id','id','fields'),
				'me' => array('/v1/users/me','fields'),
				'mecard' => array('/v1/users/me/id_card'),
				'findusers' => array('/v1/users/find','keywords','limit','offset','user_fields'),
				'findbyemail' => array('/v1/users/find_by_emails','emails','hash_function','user_fields'),
				'profilemessage' => array('/v1/users/:user_id/profile_message','user_id'),
				'legalinfo' => array('/v1/users/:user_id/legal_information','user_id'),
			),
		);
	}
	
        echo "Calling object method '$name' "
             . implode(', ', $arguments). "\n";
    }
    protected function executeAPI($method, $url, $data = array())
    {
        $client = $this->createHttpClient();

        $headers = $this->getHeaders($this->token, $method, $url, $data);

        try {
            switch ($method) {
                case 'POST':
                    $response = $client->post($url, $headers)->send();
                    break;
                case 'PUT':
                    $response = $client->put($url, $headers)->send();
                    break;
                case 'DELETE':
                    $response = $client->delete($url, $headers)->send();
                    break;
                default:
                    $response = $client->get($url, $headers)->send();
            }

            $statusCode = $response->getStatusCode();

        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when executing Xing API request."
            );
        }

        switch ($this->responseType) {
            case 'json':
                $response_parsed = $response->json();
                break;
            case 'xml':
                $response_parsed = $response->xml();
                break;

            case 'string':
                parse_str($response->getBody(), $response_parsed);
                break;

            default:
                throw new \InvalidArgumentException("Invalid response type [{$this->responseType}].");
        }
        return array($statusCode,$response_parsed);
    }
}
