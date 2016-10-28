<?php

namespace Slim\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Exception;

class SessionStart
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        				
	$defaults = [
		'lifetime'    => '30 minutes',
		'path'        => '/',
		'domain'      => null,
		'secure'      => false,
		'httponly'    => false,
		'name'        => 'xxx',
		'autorefresh' => true,
	];
				
        $settings = array_merge($defaults, $settings);

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }				
				
        $this->settings = $settings;
	$this->isCapthaValid = Array("valid"=>false);

        ini_set('session.gc_probability', 0);
        ini_set('session.gc_divisor', 1);
        ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
    }

    /**
     * Called when middleware needs to be executed.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next) {
			
			// set the "login" session & start a session
			session_name("wannabe"); 
			session_start();	
			
			// per default captcha is not valid
			$isCapthaValid = Array("valid"=>false);
			
			// get number of attepts sofar (out of $_SESSION)
			$noOfAttempts = $this->getNumberOfAttempt();
			
			// there is more then 3 failed attempts, activate captcha
			if ($noOfAttempts > 3) {
				
				// expecting header to have "gCaptcha" filed with values from the client-verification				
				if ($request->hasHeader('gCaptcha') && $request->getHeaderLine('gCaptcha') !== "false") {
						
						// validate captcha - server side
						$this->isCapthaValid = $this->tryCaptcha($request->getHeaderLine('gCaptcha'));						
				} else {			

					// log attept + 1
					$attemptNo = $this->increaseLoginAttempt();					
					
					// pass response + N° of attepts to the client 
					return $response->withHeader('Attempt', $attemptNo)->withJson("The captcha is missing, please try again", 422);
				}
			}
			
			// captcha = OK || no need for captcha
			if ($this->isCapthaValid["valid"] || $noOfAttempts < 4) {
				
				// call MAIN FUNCTION
				$response = $next($request, $response); 

				// returns code of main function, 200 ||403 ... 
				$statusCode = $response->getStatusCode();			
				
				// "YEA" if ok
				$statusMessage = (!empty($response->getHeader('passed'))) ? $response->getHeader('passed')[0] : false;	
				
				// user data responded after successfully logged in
				$userData = json_decode($response->getBody(), true);
				
				// if the auth passed start session
				if ($statusCode === 200 && $statusMessage === "YEA") {
					
					// start session
					$this->startSession($userData);					
				
				// auth failed, log attempt 
				} else {				
					
					// log attept + 1
					$attemptNo = $this->increaseLoginAttempt();	
					
					// pass response + N° of attepts to the client 
					return $response->withHeader('Attempt', $attemptNo);
				}
		
				// return
				return $response;		
				
			// captca is invalid 
			} else {
				
				// log attept + 1
				$attemptNo = $this->increaseLoginAttempt();	
				
				// pass response + N° of attepts to the client 
				return $response->withJson("The captcha is not valid, please try again", 422 )->withHeader('Attempt', $attemptNo);
			}
    }

    /**
     * just store how menu attempts user tried with wrong credentials,
		 // if much then X then show captcha
     */
    protected function increaseLoginAttempt()  { 
					
			if(!empty($_SESSION)){
				if (isset($_SESSION['attemptNo'])) {
					$_SESSION['attemptNo']++;
				} else {
					$_SESSION['attemptNo'] = 1;
				}
							
			}else{
				$_SESSION['attemptNo'] = 1;
			};
			
			return $_SESSION['attemptNo'];
    }
		
		
		/**
		// validate captcha server side
		// returns Array(status, error message)
		*/
		protected function tryCaptcha($clientHash)  { 					
			
			$recaptcha = new \ReCaptcha\ReCaptcha("xxxx");
			$resp = $recaptcha->verify($clientHash);
			
			// captcha ok? proceed with auth
			return ($resp->isSuccess()) ? Array("valid" => true, "message" => "all ok") : Array("valid" => false, "message" => $resp->getErrorCodes());						
		}
		
		
		/**
		// returns number of attempts INT
		*/
		protected function getNumberOfAttempt()  { 
					
			if(!empty($_SESSION)){				
				if (isset($_SESSION['attemptNo'])) {					
					return $_SESSION['attemptNo'];					
				} 							
			}
			
			$_SESSION['attemptNo'] = 1;				
			return $_SESSION['attemptNo'];
    }
		
		/**
		// destroy current session and start new one
		*/
		protected function startSession($userData)    {
        
				$settings = $this->settings;
        $name = $settings['name'];

        session_set_cookie_params(
            $settings['lifetime'],
            $settings['path'],
            $settings['domain'],
            $settings['secure'],
            $settings['httponly']
        );
				
				setcookie("wannabe", "", time()-3600);				
				unset($_COOKIE["wannabe"]);
				unset($_COOKIE[$name]);
				session_destroy();
       
				session_name($name);
        session_cache_limiter(false);
				session_start();
				
				$_SESSION = array(); // reset session
				$_SESSION['user'] = $userData; // array of user data
				$_SESSION['token'] = session_id();				
    }
}
