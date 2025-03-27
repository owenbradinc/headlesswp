<?php

namespace HeadlessWP\OpenAPI;

use HeadlessWP\OpenAPI\Spec\Info;
use HeadlessWP\OpenAPI\Spec\Operation;
use HeadlessWP\OpenAPI\Spec\Path;
use HeadlessWP\OpenAPI\Spec\Tag;

/**
 * Provides WordPress filter hooks for modifying OpenAPI documentation.
 * Uses singleton pattern to ensure consistent filter management.
 */
class Filters {
	const PREFIX = 'headlesswp-';

	private static ?Filters $instance = null;

	public static function getInstance(): Filters {
		if ( static::$instance === null ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Filter for modifying HTTP operations (GET, POST, etc.) for endpoints
	 * Use this to add/modify authentication, parameters, or responses
	 * 
	 * Example:
	 * add_filter('headlesswp-filter-operations', function($operations) {
	 *     foreach ($operations as $op) {
	 *         $op->addSecurity('jwt', ['write']);
	 *     }
	 *     return $operations;
	 * });
	 */
	public function addOperationsFilter( $callback, int $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-operations', $callback, $priority, 2 );
	}

	/**
	 * @param Operation[] $operations
	 * @param array $args
	 * @return Operation[]
	 */
	public function applyOperationsFilters( array $operations, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-operations', $operations, $args );
	}

	/**
	 * Filter for modifying API paths/endpoints
	 * Use this to modify endpoint URLs or add new endpoints
	 * 
	 * Example:
	 * add_filter('headlesswp-filter-paths', function($paths) {
	 *     // Add or modify endpoint paths
	 *     return $paths;
	 * });
	 */
	public function addPathsFilter( $callback, $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-paths', $callback, $priority, 2 );
	}

	/**
	 * @param Path[] $paths
	 * @param array  $args
	 * @return Path[]
	 */
	public function applyPathsFilters( array $paths, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-paths', $paths, $args );
	}

	/**
	 * Filter for modifying API servers
	 * Use this to add staging, development, or production servers
	 * 
	 * Example:
	 * add_filter('headlesswp-filter-servers', function($servers) {
	 *     $servers[] = new Server('https://staging-api.example.com');
	 *     return $servers;
	 * });
	 */
	public function addServersFilter( $callback, int $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-servers', $callback, $priority, 2 );
	}

	/**
	 * @param array $servers
	 * @param array $args
	 * @return array
	 */
	public function applyServersFilters( array $servers, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-servers', $servers, $args );
	}

	/**
	 * Filter for modifying API information
	 * Use this to customize title, description, terms of service, etc.
	 * 
	 * Example:
	 * add_filter('headlesswp-filter-info', function($info) {
	 *     $info->setTermsOfService('https://example.com/terms');
	 *     return $info;
	 * });
	 */
	public function addInfoFilter( $callback, int $priority = 10 ) {
		add_filter( self::PREFIX . 'filter-info', $callback, $priority, 2 );
	}

	/**
	 * @param Info  $info
	 * @param array $args
	 * @return Info
	 */
	public function applyInfoFilters( Info $info, array $args = array() ): Info {
		return apply_filters( self::PREFIX . 'filter-info', $info, $args );
	}

	/**
	 * Filter for modifying reusable components/schemas
	 * Use this to add or modify shared data structures
	 * 
	 * Example:
	 * add_filter('headlesswp-filter-components', function($components) {
	 *     $components['schemas']['CustomType'] = [
	 *         'type' => 'object',
	 *         'properties' => [...]
	 *     ];
	 *     return $components;
	 * });
	 */
	public function addComponentsFilter( $callback, int $priority = 10 ) {
		return add_filter( self::PREFIX . 'filter-components', $callback, $priority, 2 );
	}

	/**
	 * @param array $components
	 * @param array $args
	 * @return array
	 */
	public function applyComponentsFilters( array $components, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-components', $components, $args );
	}

	/**
	 * Filter for modifying API tags
	 * Use this to group endpoints or add descriptions to existing groups
	 * 
	 * Example:
	 * add_filter('headlesswp-filter-tags', function($tags) {
	 *     $tags[] = new Tag('custom-posts', 'Custom Post Type Endpoints');
	 *     return $tags;
	 * });
	 */
	public function addTagsFilter( $callback, int $priority = 10 ) {
		return add_filter( self::PREFIX . 'filter-tags', $callback, $priority, 2 );
	}

	/**
	 * @param Tag[] $tags
	 * @param array $args
	 * @return Tag[]
	 */
	public function applyTagsFilters( array $tags, array $args = array() ): array {
		return apply_filters( self::PREFIX . 'filter-tags', $tags, $args );
	}
}
