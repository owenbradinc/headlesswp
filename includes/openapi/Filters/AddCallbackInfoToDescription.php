<?php

namespace HeadlessWP\OpenAPI\Filters;

use HeadlessWP\OpenAPI\Callback;
use HeadlessWP\OpenAPI\CallbackFinder;
use HeadlessWP\OpenAPI\Filters;
use HeadlessWP\OpenAPI\Spec\Path;
use HeadlessWP\OpenAPI\View;

/**
 * This class enhances the OpenAPI documentation by adding callback information
 * to each endpoint's description. It shows which PHP functions/methods handle
 * each API endpoint.
 */
class AddCallbackInfoToDescription {

	private CallbackFinder $callbackFinder;
	private View $view;

	/**
	 * Initialize the filter to enhance endpoint descriptions
	 * 
	 * @param Filters $hooks - The filter system for modifying OpenAPI output
	 * @param View $view - Template renderer for callback information
	 * @param array $routes - WordPress REST API routes
	 */
	public function __construct( Filters $hooks, View $view, array $routes ) {
		// Create a CallbackFinder to locate PHP handlers for each route
		$this->callbackFinder = new CallbackFinder( $routes );

		// Register a filter that will run on all API paths
		$hooks->addPathsFilter(
			function( array $paths ) {
				// Process each path to add callback info
				foreach ( $paths as $path ) {
					$this->addCallbackInfo( $path );
				}

				return $paths;
			}
		);
		$this->view = $view;
	}

	/**
	 * Add callback information to a specific path's description
	 * 
	 * @param Path $path - The OpenAPI path object to enhance
	 * @return Path - The enhanced path object
	 */
	private function addCallbackInfo( Path $path ): Path {
		// Find all callbacks (PHP handlers) for this path
		$callbacks = $this->callbackFinder->find( $path->getOriginalPath() );

		// Process each operation (GET, POST, etc.) in this path
		foreach ( $path->getOperations() as $operation ) {
			$method = $operation->getMethod();
			
			// If we found a callback for this method
			if ( isset( $callbacks[ $method ] ) ) {
				$callback     = $callbacks[ $method ];
				
				// Get existing description and append callback info
				$description = $operation->getDescription();
				$description .= $this->view->render(
					array(
						'callbackType' => $callback->getCallableType(), // 'function' or 'class'
						'callable'     => $callback->getCallable(),         // PHP function/method name
						'filepath'     => htmlentities( $callback->getFilepath() ), // File location
					)
				);
				
				// Update the operation with enhanced description
				$operation->setDescription( $description );
			}
		}
		return $path;
	}
}
