<?php

namespace Slim\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Exception;

class IsLoggedIn
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
    public function __construct($settings = []) {
        				
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
		
    }
}
