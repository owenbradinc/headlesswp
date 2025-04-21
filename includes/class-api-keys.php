<?php
/**
 * API Keys Database Handler
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

class HeadlessWP_API_Keys {
	/**
	 * The table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Initialize the class
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'headlesswp_api_keys';
	}

	/**
	 * Create the API keys table
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			name varchar(255) NOT NULL,
			description text,
			api_key varchar(64) NOT NULL,
			permissions varchar(255) NOT NULL DEFAULT 'read',
			origins text,
			last_used datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY api_key (api_key)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Add a new API key
	 *
	 * @param string $name Key name
	 * @param string $description Key description
	 * @param string $permissions Key permissions
	 * @param array $origins Allowed origins
	 * @return array|WP_Error The new key data or WP_Error on failure
	 */
	public function add_key($name, $description = '', $permissions = 'read', $origins = []) {
		global $wpdb;

		if (empty($name)) {
			return new WP_Error('invalid_key_name', __('API key name cannot be empty.', 'headlesswp'));
		}

		// Generate a unique key
		$api_key = 'hwp_' . wp_generate_password(32, false, false);
		$api_key_hashed = wp_hash_password($api_key);

		$data = [
			'user_id' => get_current_user_id(),
			'name' => sanitize_text_field($name),
			'description' => sanitize_text_field($description),
			'api_key' => $api_key_hashed,
			'permissions' => sanitize_text_field($permissions),
			'origins' => maybe_serialize($origins),
			'created_at' => current_time('mysql')
		];

		$result = $wpdb->insert($this->table_name, $data);

		if (!$result) {
			return new WP_Error('db_error', __('Failed to save API key to database.', 'headlesswp'));
		}

		// Return the key data with the plain key for display
		$data['id'] = $wpdb->insert_id;
		$data['api_key'] = $api_key;
		$data['origins'] = $origins;

		return $data;
	}

	/**
	 * Get all API keys
	 *
	 * @return array
	 */
	public function get_keys() {
		global $wpdb;
		$keys = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC", ARRAY_A);

		foreach ($keys as &$key) {
			$key['origins'] = maybe_unserialize($key['origins']);
		}

		return $keys;
	}

	/**
	 * Get a single API key by ID
	 *
	 * @param int $id Key ID
	 * @return array|null
	 */
	public function get_key($id) {
		global $wpdb;
		$key = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		), ARRAY_A);

		if ($key) {
			$key['origins'] = maybe_unserialize($key['origins']);
		}

		return $key;
	}

	/**
	 * Verify an API key
	 *
	 * @param string $api_key The API key to verify
	 * @return array|WP_Error The key data or WP_Error if invalid
	 */
	public function verify_key($api_key) {
		global $wpdb;
		$keys = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);

		foreach ($keys as $key) {
			if (wp_check_password($api_key, $key['api_key'])) {
				$key['origins'] = maybe_unserialize($key['origins']);
				return $key;
			}
		}

		return new WP_Error('invalid_key', __('Invalid API key.', 'headlesswp'));
	}

	/**
	 * Update the last used timestamp for a key
	 *
	 * @param int $id Key ID
	 * @return bool
	 */
	public function update_last_used($id) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name,
			['last_used' => current_time('mysql')],
			['id' => $id]
		);
	}

	/**
	 * Revoke an API key
	 *
	 * @param int $id Key ID
	 * @return bool|WP_Error
	 */
	public function revoke_key($id) {
		global $wpdb;
		$result = $wpdb->delete($this->table_name, ['id' => $id]);

		if ($result === false) {
			return new WP_Error('db_error', __('Failed to revoke API key.', 'headlesswp'));
		}

		return true;
	}
} 