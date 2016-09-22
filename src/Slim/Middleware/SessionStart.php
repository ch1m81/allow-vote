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
            'name'        => 'supportfaq',
            'autorefresh' => true,
        ];
				
        $settings = array_merge($defaults, $settings);

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }				
				
        $this->settings = $settings;

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
    public function __invoke(Request $request, Response $response, callable $next)
    {
			
			// get the session
			session_name($this->settings["name"]); 
			session_start();	
			
			$isCapthaValid = Array("valid"=>false);
			
			$noOfAttempts = $this->getNumberOfAttempt();
			
			// there is more then 3 failed attempts, activate captcha
			if ($noOfAttempts > 3) {
				
				// expecting header to have "gCaptcha" filed with values from the client-verification				
				if ($request->hasHeader('gCaptcha') && $request->getHeaderLine('gCaptcha') !== "false") {
						
						$isCapthaValid = $this->tryCaptcha($request->getHeaderLine('gCaptcha'));						
				} else {			

					$attemptNo = $this->increaseLoginAttempt();					
					
					// captcha is expeced not it is missing
					return $response->withHeader('Attempt', $attemptNo)->withJson("The captcha is missing, please try again", 422);
				}
			}
			
			
			if ($isCapthaValid["valid"] || $noOfAttempts < 4) {
				// call MAIN FUNCTION
				$response = $next($request, $response); 

				// returns code of main function, 200 ||403 ... 
				$statusCode = $response->getStatusCode();			
				
				// "OK" if ok
				$statusMessage = (!empty($response->getHeader('status'))) ? $response->getHeader('status')[0] : false;				
				
				if ($statusCode === 200 && $statusMessage === "OK") {
					
					$this->startSession();					
				
				} else {				
					
					$attemptNo = $this->increaseLoginAttempt();	
					
					return $response->withHeader('Attempt', $attemptNo);
				}
		
				return $response;		
				
			} else {
				
				$attemptNo = $this->increaseLoginAttempt();	
				
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
			
			$recaptcha = new \ReCaptcha\ReCaptcha("6LcYMQcUAAAAANa7cTGOpIxhYAR32BH92HEFYYB4");
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
		
		
		protected function startSession()
    {
        $settings = $this->settings;
        $name = $settings['name'];

        session_set_cookie_params(
            $settings['lifetime'],
            $settings['path'],
            $settings['domain'],
            $settings['secure'],
            $settings['httponly']
        );

        /*if (session_id()) {
            if ($settings['autorefresh'] && isset($_COOKIE[$name])) {
                setcookie(
                    $name,
                    $_COOKIE[$name],
                    time() + $settings['lifetime'],
                    $settings['path'],
                    $settings['domain'],
                    $settings['secure'],
                    $settings['httponly']
                );
            }
        }*/
				
				//session_destroy();
				//$_SESSION = array();
        //session_name($name);
        //session_cache_limiter(false);
				//session_start();
				session_regenerate_id( true );
				$_SESSION['attemptNo'] = 0;
				$_SESSION['token'] = session_id();
    }
}
