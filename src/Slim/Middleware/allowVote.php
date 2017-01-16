<?php

namespace Slim\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Exception;

class allowVote
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
    public function __construct() {
			
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
				
				$hasVoted = $this->hasVoted ();
							
				$request = $request->withAttribute('hasVoted', $hasVoted);			
				
				$response = $next($request, $response);	
				
				return $response;				
				
    }
		
		private function hasVoted () {	
					 
				if (!$token && !isset($_SESSION['token'])) 	return false;
				
				if (empty(session_id())) {				
					session_name("supportfaq");       
					session_start();
				}
			
				if (isset($_SESSION['token']) && $_SESSION['token'] && $_SESSION['token'] === $token) {					
					return true;
				}						
				
				return false;
			
		}
}
