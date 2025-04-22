<?php
/**
 * GraphQL functionality for HeadlessWP
 *
 * @package HeadlessWP
 */

namespace HeadlessWP;

/**
 * Class GraphQL
 */
class GraphQL {

	/**
	 * Initialize the GraphQL functionality
	 */
	public function init() {
		// Register custom GraphQL types and fields
		add_action('graphql_register_types', array($this, 'register_graphql_types'));
		
		// Add custom GraphQL queries
		add_action('graphql_register_types', array($this, 'register_graphql_queries'));
		
		// Add custom GraphQL mutations
		add_action('graphql_register_types', array($this, 'register_graphql_mutations'));
	}

	/**
	 * Register custom GraphQL types
	 */
	public function register_graphql_types() {
		// Example: Register a custom type
		register_graphql_object_type('HeadlessWPSettings', [
			'description' => __('HeadlessWP plugin settings', 'headlesswp'),
			'fields' => [
				'disableThemes' => [
					'type' => 'Boolean',
					'description' => __('Whether themes are disabled', 'headlesswp'),
				],
				'disableFrontend' => [
					'type' => 'Boolean',
					'description' => __('Whether frontend is disabled', 'headlesswp'),
				],
			],
		]);
	}

	/**
	 * Register custom GraphQL queries
	 */
	public function register_graphql_queries() {
		// Example: Add a query to get plugin settings
		register_graphql_field('RootQuery', 'headlessWPSettings', [
			'type' => 'HeadlessWPSettings',
			'description' => __('Get HeadlessWP plugin settings', 'headlesswp'),
			'resolve' => function() {
				$options = get_option('headlesswp_options');
				return [
					'disableThemes' => $options['disable_themes'] ?? false,
					'disableFrontend' => $options['disable_frontend'] ?? false,
				];
			},
		]);
	}

	/**
	 * Register custom GraphQL mutations
	 */
	public function register_graphql_mutations() {
		// Example: Add a mutation to update plugin settings
		register_graphql_mutation('updateHeadlessWPSettings', [
			'inputFields' => [
				'disableThemes' => [
					'type' => 'Boolean',
					'description' => __('Whether to disable themes', 'headlesswp'),
				],
				'disableFrontend' => [
					'type' => 'Boolean',
					'description' => __('Whether to disable frontend', 'headlesswp'),
				],
			],
			'outputFields' => [
				'success' => [
					'type' => 'Boolean',
					'description' => __('Whether the settings were updated successfully', 'headlesswp'),
				],
			],
			'mutateAndGetPayload' => function($input) {
				$options = get_option('headlesswp_options');
				$options['disable_themes'] = $input['disableThemes'] ?? $options['disable_themes'];
				$options['disable_frontend'] = $input['disableFrontend'] ?? $options['disable_frontend'];
				
				$success = update_option('headlesswp_options', $options);
				
				return [
					'success' => $success,
				];
			},
		]);
	}
} 