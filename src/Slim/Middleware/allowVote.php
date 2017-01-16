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
				
				$guideID = $request->getParam('subjectID');	     
				
				$allowToVote = $this->allowToVote ($guideID);
							
				$request = $request->withAttribute('allowVote', $allowToVote);			
				
				$response = $next($request, $response);	
				
				return $response;				
				
    }
		
		private function allowToVote ($guideID) {						 
				
			session_start();
			
			if (!isset($_SESSION['votedGuides'])) {	
			
				$_SESSION['votedGuides'] = Array();
				$_SESSION['votedGuides'][] = $guideID;
				
				return true;
			
			}	else {
				
				if (in_array($guideID , $_SESSION['votedGuides'])) {					
					
					return false;
					
				} else {	
				
					$_SESSION['votedGuides'][] = $guideID;
					
					return true;
					
				}
			}
			
		}
}

