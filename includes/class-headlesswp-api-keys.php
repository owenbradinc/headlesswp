<?php

class HeadlessWP_API_Keys {
	public function add_api_key($name, $description, $permissions, $origins) {
		global $wpdb;

		// Generate a random API key
		$api_key = wp_generate_password(32, false);
		error_log('HeadlessWP: Generated API key: ' . $api_key);

		// Hash the API key for storage
		$hashed_key = wp_hash_password($api_key);
		error_log('HeadlessWP: Hashed API key: ' . $hashed_key);

		// Insert the API key into the database
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'name' => $name,
				'description' => $description,
				'api_key' => $hashed_key,
				'permissions' => $permissions,
				'origins' => maybe_serialize($origins),
				'created_at' => current_time('mysql'),
				'last_used' => null
			),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
		);

		if ($result === false) {
			error_log('HeadlessWP: Failed to insert API key into database');
			return new WP_Error('db_error', __('Failed to add API key to database.', 'headlesswp'));
		}

		// Return the unhashed key for one-time display
		$return_data = array(
			'id' => $wpdb->insert_id,
			'api_key' => $api_key
		);
		error_log('HeadlessWP: Returning from add_api_key: ' . print_r($return_data, true));
		return $return_data;
	}
} 