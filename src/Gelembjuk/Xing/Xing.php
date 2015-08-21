<?php

/**
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
    
    public function getMe() {
        $response = $this->__call('getme',array());
        
        if (isset($response['users'][0])) {
            return $response['users'][0];
        }
        
        return null;
    }
    public function getUser($id,$fields = '') {
        $response = $this->__call('getuser',array($id,$fields));
        
        if (isset($response['users'][0])) {
            return $response['users'][0];
        }
        
        return null;
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
                'job' => array('/v1/jobs/:id','id','user_fields'),
            ),
            );
        }
        if (preg_match('!^(get|post|put|delete)(.+)$!i', $name, $matched)) {
            $httpmethod = strtoupper($matched[1]);
            $function = strtolower($matched[2]);
        
            if (!isset($allapimethods[$httpmethod]) ||
            isset($allapimethods[$httpmethod]) &&
            !isset($allapimethods[$httpmethod][$function])) {
                throw new \Exception('API Method is not supported');
            }
            
            $functionname = strtolower($httpmethod).ucfirst($function);
            
            // order in $arguments must be same as in declared listing

            // first item of the aray is url
            $url = array_shift($allapimethods[$httpmethod][$function]);
        
            // all other array items are arguments
            $data = $allapimethods[$httpmethod][$function];
            
            // create hash of arguments
            $argdata = array();
            
            if (is_array($arguments[0])) {
                // means arguments were posted as a hash and names must be same as in the API
                // we have to trash for what  auser entered
                $argdata = $arguments[0];
            } else {
                for($i = 0; $i < count($data); $i++) {
                    $argdata[$data[$i]] = (isset($arguments[$i])) ? $arguments[$i] : null;
                }
            }
        
            while (count($arguments) > 0 && count($data > 0) && strpos($url, ':'.$data[0]) > 0) {
                if ($data[0] === null || $data[0] == '') {
                    throw new \Exception(sprintf('Mandatory argument %s not provided for the API function %s call', $data[0],$functionname));
                }
                
                $url = str_replace(':'.$data[0],$argdata[$data[0]],$url);
                
                unset($argdata[$data[0]]);
                array_shift($data);
            }
            
            $url = self::XING_API_ENDPOINT . $url;
            
            $response = $this->executeAPI($httpmethod, $url, $argdata);
            
            $resp_code = $response[0];
            
            if (!in_array($resp_code,array(200,201,204))) {
                throw new \Exception(sprintf('API returned error with the response code %d', $resp_code));
            }
            
            return $response[1];
        }
    
        throw new \Exception('Method not found');
    }
    protected function executeAPI($method, $url, $data = array())
    {
        $client = $this->createHttpClient();
        
        if ($method == 'GET') {
             // add argumemnts to url
                $url .= (strpos($url,'?') > 0) ? '&' : '?';
                
                foreach ($data as $k => $v) {
                    $url .= $k . '=' . urlencode($v).'&';
                }
                
                $data = array();
        }

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
