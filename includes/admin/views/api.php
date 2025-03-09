<?php
/**
 * Endpoints page template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Process endpoint testing if requested
$test_result = null;
$test_endpoint = '';
$test_method = 'GET';
$test_params = '';

if (isset($_POST['headlesswp_test_endpoint']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_test_endpoint', 'headlesswp_nonce');

	$test_endpoint = sanitize_text_field($_POST['endpoint']);
	$test_method = sanitize_text_field($_POST['method']);
	$test_params = isset($_POST['params']) ? $_POST['params'] : '';

	// Prepare request args
	$args = array(
		'method' => $test_method,
		'headers' => array(
			'Content-Type' => 'application/json',
		),
	);

	// Add body for POST, PUT, PATCH requests
	if (in_array($test_method, array('POST', 'PUT', 'PATCH')) && !empty($test_params)) {
		$args['body'] = $test_params;
	}

	// Add query parameters for GET requests
	if ($test_method === 'GET' && !empty($test_params)) {
		$params = json_decode($test_params, true);
		if (is_array($params)) {
			$test_endpoint = add_query_arg($params, $test_endpoint);
		}
	}

	// Make the request
	$response = wp_remote_request($test_endpoint, $args);

	if (is_wp_error($response)) {
		$test_result = array(
			'success' => false,
			'message' => $response->get_error_message(),
		);
	} else {
		$test_result = array(
			'success' => true,
			'status' => wp_remote_retrieve_response_code($response),
			'headers' => wp_remote_retrieve_headers($response),
			'body' => wp_remote_retrieve_body($response),
		);
	}
}

// Handle endpoint toggling
if (isset($_POST['headlesswp_toggle_endpoint']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_toggle_endpoint', 'headlesswp_toggle_nonce');

	$namespace = sanitize_text_field($_POST['namespace']);
	$route = sanitize_text_field($_POST['route']);

	$options = get_option('headlesswp_options', array());

	if (!isset($options['disabled_endpoints'])) {
		$options['disabled_endpoints'] = array();
	}

	$endpoint_key = $namespace . '|' . $route;

	if (isset($options['disabled_endpoints'][$endpoint_key])) {
		unset($options['disabled_endpoints'][$endpoint_key]);
	} else {
		$options['disabled_endpoints'][$endpoint_key] = true;
	}

	update_option('headlesswp_options', $options);
}

// Get current options
$options = get_option('headlesswp_options', array());
if (!isset($options['disabled_endpoints'])) {
	$options['disabled_endpoints'] = array();
}

// Group routes by namespace and structure them hierarchically
$namespaces = array();
$endpoint_tree = array();

foreach ($routes as $route => $route_data) {
	// Extract namespace from route
	$parts = explode('/', ltrim($route, '/'));
	$namespace = !empty($parts[0]) ? $parts[0] : 'root';

	if (!isset($namespaces[$namespace])) {
		$namespaces[$namespace] = array();
		$endpoint_tree[$namespace] = array();
	}

	$namespaces[$namespace][$route] = $route_data;

	// Create a tree structure for the sidebar
	$path_parts = array_filter($parts);
	if (empty($path_parts)) {
		$endpoint_tree[$namespace]['root'] = array(
			'route' => $route,
			'data' => $route_data,
			'children' => array()
		);
		continue;
	}

	// Remove namespace from path parts to build tree
	array_shift($path_parts);

	// Build the tree recursively
	$current = &$endpoint_tree[$namespace];
	$current_path = $namespace;

	foreach ($path_parts as $index => $part) {
		$current_path .= '/' . $part;

		if ($index === count($path_parts) - 1) {
			// This is the last part, so it's an endpoint
			$current[$part] = array(
				'route' => $route,
				'data' => $route_data,
				'children' => array()
			);
		} else {
			// This is a directory part
			if (!isset($current[$part])) {
				$current[$part] = array(
					'route' => null,
					'data' => null,
					'children' => array()
				);
			}
			$current = &$current[$part]['children'];
		}
	}
}

// Sort namespaces alphabetically
ksort($namespaces);
ksort($endpoint_tree);

// Get active namespace and endpoint from query parameters
$active_namespace = isset($_GET['namespace']) ? sanitize_text_field($_GET['namespace']) : key($namespaces);
$active_endpoint = isset($_GET['endpoint']) ? sanitize_text_field($_GET['endpoint']) : '';

if (!isset($namespaces[$active_namespace])) {
	$active_namespace = key($namespaces);
}

// Function to render the endpoint tree recursively
function render_endpoint_tree($tree, $namespace, $current_path = '', $depth = 0) {
	$html = '';
	$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);

	// Sort tree keys alphabetically
	ksort($tree);

	foreach ($tree as $key => $node) {
		$path = $current_path ? $current_path . '/' . $key : $key;
		$active_class = '';

		if (isset($_GET['endpoint']) && $_GET['endpoint'] === $node['route']) {
			$active_class = ' headlesswp-tree-active';
		}

		if ($node['route']) {
			// This is an endpoint
			$endpoint_url = admin_url('admin.php?page=headlesswp-api&namespace=' . urlencode($namespace) . '&endpoint=' . urlencode($node['route']));
			$html .= '<div class="headlesswp-tree-item' . $active_class . '">';
			$html .= $indent . '<a href="' . esc_url($endpoint_url) . '" class="headlesswp-tree-link">';

			// Get the HTTP methods for this endpoint
			$methods = array();
			foreach ($node['data'] as $data) {
				if (isset($data['methods'])) {
					$methods = array_merge($methods, array_keys($data['methods']));
				}
			}
			$methods = array_unique($methods);
			sort($methods);

			// Add method badges
			$method_html = '';
			foreach ($methods as $method) {
				$method_html .= '<span class="headlesswp-method-badge method-' . strtolower(esc_attr($method)) . '">' . esc_html($method) . '</span>';
			}

			$html .= '<span class="headlesswp-tree-endpoint">' . esc_html($key) . '</span> ' . $method_html;
			$html .= '</a>';
			$html .= '</div>';
		} else {
			// This is a directory
			$html .= '<div class="headlesswp-tree-directory">';
			$html .= $indent . '<span class="headlesswp-tree-toggle">';
			$html .= '<span class="dashicons dashicons-arrow-right-alt2"></span>';
			$html .= '</span>';
			$html .= '<span class="headlesswp-tree-dir-name">' . esc_html($key) . '</span>';
			$html .= '</div>';
			$html .= '<div class="headlesswp-tree-children">';
			$html .= render_endpoint_tree($node['children'], $namespace, $path, $depth + 1);
			$html .= '</div>';
		}
	}

	return $html;
}
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-api-manager">
        <!-- Sidebar with endpoint tree -->
        <div class="headlesswp-sidebar">
            <div class="headlesswp-namespace-selector">
                <label for="namespace-select"><?php _e('Namespace:', 'headlesswp'); ?></label>
                <select id="namespace-select">
					<?php foreach ($namespaces as $ns => $ns_routes): ?>
                        <option value="<?php echo esc_attr($ns); ?>" <?php selected($active_namespace, $ns); ?>>
							<?php echo esc_html($ns); ?> (<?php echo count($ns_routes); ?>)
                        </option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="headlesswp-search-bar">
                <input type="text" id="endpoint-search" placeholder="<?php _e('Search endpoints...', 'headlesswp'); ?>">
                <button type="button" id="clear-search" class="button-link" style="display: none;">
                    <span class="dashicons dashicons-dismiss"></span>
                </button>
            </div>

            <div class="headlesswp-endpoint-tree">
				<?php
				foreach ($endpoint_tree as $ns => $tree):
					$display = ($ns === $active_namespace) ? 'block' : 'none';
					?>
                    <div class="headlesswp-namespace-tree" id="namespace-tree-<?php echo esc_attr($ns); ?>" style="display: <?php echo $display; ?>">
						<?php echo render_endpoint_tree($tree, $ns); ?>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>

        <!-- Main content area -->
        <div class="headlesswp-main-content">
			<?php if ($active_endpoint && isset($routes[$active_endpoint])):
				// Display the selected endpoint details
				$endpoint_data = $routes[$active_endpoint];
				$methods = array();
				foreach ($endpoint_data as $data) {
					if (isset($data['methods'])) {
						$methods = array_merge($methods, array_keys($data['methods']));
					}
				}
				$methods = array_unique($methods);
				sort($methods);

				$endpoint_key = $active_namespace . '|' . $active_endpoint;
				$is_disabled = isset($options['disabled_endpoints'][$endpoint_key]);
				$status_class = $is_disabled ? 'disabled' : 'enabled';
				$status_text = $is_disabled ? __('Disabled', 'headlesswp') : __('Enabled', 'headlesswp');
				$toggle_text = $is_disabled ? __('Enable', 'headlesswp') : __('Disable', 'headlesswp');

				// Make a proper URL for testing
				$test_url = rest_url(ltrim($active_endpoint, '/'));
				?>
                <div class="headlesswp-card headlesswp-endpoint-details">
                    <div class="headlesswp-endpoint-header">
                        <h2><?php echo esc_html($active_endpoint); ?></h2>
                        <div class="headlesswp-endpoint-status">
                            <span class="headlesswp-status-badge <?php echo $status_class; ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>

                            <form method="post" action="" class="headlesswp-toggle-form">
								<?php wp_nonce_field('headlesswp_toggle_endpoint', 'headlesswp_toggle_nonce'); ?>
                                <input type="hidden" name="namespace" value="<?php echo esc_attr($active_namespace); ?>">
                                <input type="hidden" name="route" value="<?php echo esc_attr($active_endpoint); ?>">
                                <button type="submit" name="headlesswp_toggle_endpoint" class="button">
									<?php echo esc_html($toggle_text); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="headlesswp-endpoint-methods">
                        <h3><?php _e('HTTP Methods', 'headlesswp'); ?></h3>
                        <div class="headlesswp-methods-list">
							<?php foreach ($methods as $method): ?>
                                <span class="headlesswp-method-badge method-<?php echo strtolower(esc_attr($method)); ?>">
                                    <?php echo esc_html($method); ?>
                                </span>
							<?php endforeach; ?>
                        </div>
                    </div>

                    <div class="headlesswp-endpoint-url">
                        <h3><?php _e('Endpoint URL', 'headlesswp'); ?></h3>
                        <div class="headlesswp-copy-container">
                            <code id="endpoint-url" class="headlesswp-copy-text"><?php echo esc_url($test_url); ?></code>
                            <button type="button" class="button headlesswp-copy-button" data-clipboard-target="#endpoint-url">
                                <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'headlesswp'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="headlesswp-endpoint-test">
                        <h3><?php _e('Test Endpoint', 'headlesswp'); ?></h3>
                        <form method="post" action="" class="headlesswp-test-form">
							<?php wp_nonce_field('headlesswp_test_endpoint', 'headlesswp_nonce'); ?>

                            <input type="hidden" name="endpoint" value="<?php echo esc_url($test_url); ?>">

                            <div class="headlesswp-form-row">
                                <label for="method"><?php _e('HTTP Method:', 'headlesswp'); ?></label>
                                <select id="method" name="method">
									<?php foreach ($methods as $method): ?>
                                        <option value="<?php echo esc_attr($method); ?>" <?php selected($test_method, $method); ?>>
											<?php echo esc_html($method); ?>
                                        </option>
									<?php endforeach; ?>
                                </select>
                            </div>

                            <div class="headlesswp-form-row">
                                <label for="params"><?php _e('Parameters (JSON):', 'headlesswp'); ?></label>
                                <textarea id="params" name="params" rows="5" class="large-text code"><?php echo esc_textarea($test_params); ?></textarea>
                                <p class="description"><?php _e('Enter parameters as JSON. For GET requests, these will be added as query parameters. For POST, PUT, and PATCH, they will be sent in the request body.', 'headlesswp'); ?></p>
                            </div>

                            <div class="headlesswp-form-row">
                                <button type="submit" name="headlesswp_test_endpoint" class="button button-primary">
									<?php _e('Send Request', 'headlesswp'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

					<?php if ($test_result): ?>
                        <div class="headlesswp-endpoint-response">
                            <h3><?php _e('Response', 'headlesswp'); ?></h3>
                            <div class="headlesswp-test-result">
								<?php if ($test_result['success']): ?>
                                    <p><strong><?php _e('Status:', 'headlesswp'); ?></strong> <?php echo esc_html($test_result['status']); ?></p>

                                    <h4><?php _e('Headers:', 'headlesswp'); ?></h4>
                                    <pre class="headlesswp-response-headers"><?php
										$headers_array = $test_result['headers']->getAll();
										foreach ($headers_array as $key => $values) {
											foreach ((array) $values as $value) {
												echo esc_html($key) . ': ' . esc_html($value) . "\n";
											}
										}
										?></pre>

                                    <h4><?php _e('Body:', 'headlesswp'); ?></h4>
                                    <div class="headlesswp-response-body">
										<?php
										$json = json_decode($test_result['body']);
										if ($json !== null) {
											echo '<pre>' . esc_html(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
										} else {
											echo '<pre>' . esc_html($test_result['body']) . '</pre>';
										}
										?>
                                    </div>
								<?php else: ?>
                                    <div class="notice notice-error">
                                        <p><?php echo esc_html($test_result['message']); ?></p>
                                    </div>
								<?php endif; ?>
                            </div>
                        </div>
					<?php endif; ?>

                    <div class="headlesswp-endpoint-schema">
                        <h3><?php _e('Endpoint Documentation', 'headlesswp'); ?></h3>
                        <div class="headlesswp-schema-content">
							<?php
							// Get schema info from endpoint data
							$schema_html = '';
							$args_html = '';

							foreach ($endpoint_data as $data) {
								if (!empty($data['schema'])) {
									$schema_html .= '<h4>' . __('Schema:', 'headlesswp') . '</h4>';
									$schema_html .= '<pre>' . esc_html(json_encode($data['schema'], JSON_PRETTY_PRINT)) . '</pre>';
								}

								if (!empty($data['args'])) {
									$args_html .= '<h4>' . __('Arguments:', 'headlesswp') . '</h4>';
									$args_html .= '<table class="widefat striped">';
									$args_html .= '<thead><tr><th>' . __('Name', 'headlesswp') . '</th><th>' . __('Required', 'headlesswp') . '</th><th>' . __('Description', 'headlesswp') . '</th></tr></thead>';
									$args_html .= '<tbody>';

									foreach ($data['args'] as $arg_name => $arg) {
										$required = !empty($arg['required']) ? __('Yes', 'headlesswp') : __('No', 'headlesswp');
										$description = !empty($arg['description']) ? $arg['description'] : '';

										$args_html .= '<tr>';
										$args_html .= '<td><code>' . esc_html($arg_name) . '</code></td>';
										$args_html .= '<td>' . esc_html($required) . '</td>';
										$args_html .= '<td>' . esc_html($description) . '</td>';
										$args_html .= '</tr>';
									}

									$args_html .= '</tbody></table>';
								}
							}

							if ($schema_html) {
								echo $schema_html;
							}

							if ($args_html) {
								echo $args_html;
							}

							if (!$schema_html && !$args_html) {
								echo '<p>' . __('No documentation available for this endpoint.', 'headlesswp') . '</p>';
							}
							?>
                        </div>
                    </div>

                    <div class="headlesswp-endpoint-code-examples">
                        <h3><?php _e('Code Examples', 'headlesswp'); ?></h3>

                        <h4><?php _e('JavaScript Fetch:', 'headlesswp'); ?></h4>
                        <pre class="headlesswp-code-example" id="js-example">fetch('<?php echo esc_url($test_url); ?>', {
  method: '<?php echo esc_html($methods[0] ?? 'GET'); ?>',
<?php if (in_array('POST', $methods) || in_array('PUT', $methods) || in_array('PATCH', $methods)): ?>
    headers: {
    'Content-Type': 'application/json'
    },
    body: JSON.stringify({
    // Your request data here
    })
<?php endif; ?>
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</pre>
                        <button type="button" class="button headlesswp-copy-button" data-clipboard-target="#js-example">
                            <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'headlesswp'); ?>
                        </button>

                        <h4><?php _e('cURL:', 'headlesswp'); ?></h4>
                        <pre class="headlesswp-code-example" id="curl-example">curl -X <?php echo esc_html($methods[0] ?? 'GET'); ?> \
<?php if (in_array('POST', $methods) || in_array('PUT', $methods) || in_array('PATCH', $methods)): ?>
    -H "Content-Type: application/json" \
    -d '{"key": "value"}' \
<?php endif; ?>
							<?php echo esc_url($test_url); ?></pre>
                        <button type="button" class="button headlesswp-copy-button" data-clipboard-target="#curl-example">
                            <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'headlesswp'); ?>
                        </button>
                    </div>
                </div>
			<?php else: ?>
                <!-- Dashboard view when no endpoint is selected -->
                <div class="headlesswp-card">
                    <h2><?php _e('API Base URL', 'headlesswp'); ?></h2>
                    <p><?php _e('Use this as your base URL for API requests:', 'headlesswp'); ?></p>
                    <div class="headlesswp-copy-container">
                        <code id="base-url" class="headlesswp-copy-text"><?php echo esc_url(rest_url()); ?></code>
                        <button type="button" class="button headlesswp-copy-button" data-clipboard-target="#base-url">
                            <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'headlesswp'); ?>
                        </button>
                    </div>
                    <p class="description"><?php _e('Select an endpoint from the sidebar to view details and test it.', 'headlesswp'); ?></p>
                </div>

                <div class="headlesswp-card">
                    <h2><?php _e('API Manager Overview', 'headlesswp'); ?></h2>
                    <p><?php _e('Welcome to the HeadlessWP API Manager. This tool allows you to:', 'headlesswp'); ?></p>
                    <ul class="headlesswp-features-list">
                        <li><?php _e('Browse all available REST API endpoints organized by namespace', 'headlesswp'); ?></li>
                        <li><?php _e('Test endpoints with different HTTP methods and parameters', 'headlesswp'); ?></li>
                        <li><?php _e('View endpoint documentation, including schemas and arguments', 'headlesswp'); ?></li>
                        <li><?php _e('Enable or disable specific endpoints', 'headlesswp'); ?></li>
                        <li><?php _e('Get code examples for interacting with the API', 'headlesswp'); ?></li>
                    </ul>
                    <p><?php _e('To get started, select an endpoint from the sidebar on the left.', 'headlesswp'); ?></p>
                </div>
			<?php endif; ?>
        </div>
    </div>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Initialize clipboard.js
        if (typeof ClipboardJS !== 'undefined') {
            new ClipboardJS('.headlesswp-copy-button');

            // Show success message
            $('.headlesswp-copy-button').on('click', function() {
                var $this = $(this);
                var originalText = $this.html();

                $this.html('<span class="dashicons dashicons-yes"></span> Copied!');

                setTimeout(function() {
                    $this.html(originalText);
                }, 2000);
            });
        }

        // Namespace selector change
        $('#namespace-select').on('change', function() {
            var namespace = $(this).val();

            // Hide all namespace trees
            $('.headlesswp-namespace-tree').hide();

            // Show the selected namespace tree
            $('#namespace-tree-' + namespace).show();

            // Update the URL without reloading
            var url = new URL(window.location.href);
            url.searchParams.set('namespace', namespace);
            url.searchParams.delete('endpoint');
            window.history.pushState({}, '', url);
        });

        // Toggle tree directories
        $('.headlesswp-endpoint-tree').on('click', '.headlesswp-tree-toggle', function() {
            var $directory = $(this).closest('.headlesswp-tree-directory');
            var $children = $directory.next('.headlesswp-tree-children');
            var $icon = $(this).find('.dashicons');

            if ($children.is(':visible')) {
                $children.slideUp(200);
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
            } else {
                $children.slideDown(200);
                $icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
            }
        });

        // Search functionality
        $('#endpoint-search').on('input', function() {
            var searchText = $(this).val().toLowerCase();

            if (searchText) {
                $('#clear-search').show();
                $('.headlesswp-tree-item').each(function() {
                    var endpointText = $(this).text().toLowerCase();
                    if (endpointText.indexOf(searchText) > -1) {
                        $(this).show();
                        // Show parent directories
                        $(this).parents('.headlesswp-tree-children').show();
                        $(this).parents('.headlesswp-tree-children').prev('.headlesswp-tree-directory').find('.dashicons')
                            .removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    } else {
                        $(this).hide();
                    }
                });

                // Hide directories that don't have visible children
                $('.headlesswp-tree-directory').each(function() {
                    var $dir = $(this);
                    var $children = $dir.next('.headlesswp-tree-children');
                    var hasVisibleEndpoints = $children.find('.headlesswp-tree-item:visible').length > 0;

                    if (!hasVisibleEndpoints) {
                        $dir.hide();
                    } else {
                        $dir.show();
                    }
                });
            } else {
                $('#clear-search').hide();
                $('.headlesswp-tree-item, .headlesswp-tree-directory').show();
                $('.headlesswp-tree-children').hide();
                $('.headlesswp-tree-toggle .dashicons')
                    .removeClass('dashicons-arrow-down-alt2')
                    .addClass('dashicons-arrow-right-alt2');
            }
        });

        // Clear search button
        $('#clear-search').on('click', function() {
            $('#endpoint-search').val('').trigger('input');
        });

        // Expand all directories on page load for the active endpoint
        $('.headlesswp-tree-active').parents('.headlesswp-tree-children').show()
            .prev('.headlesswp-tree-directory').find('.dashicons')
            .removeClass('dashicons-arrow-right-alt2')
            .addClass('dashicons-arrow-down-alt2');
    });
</script>

<style>
    /* API Manager layout */
    .headlesswp-api-manager {
        display: flex;
        margin-top: 20px;
        background-color: #f0f0f1;
        border: 1px solid #c3c4c7;
        border-radius: 3px;
        overflow: hidden;
    }

    /* Sidebar styles */
    .headlesswp-sidebar {
        width: 280px;
        background-color: #f0f0f1;
        border-right: 1px solid #c3c4c7;
        overflow-y: auto;
        max-height: calc(100vh - 200px);
    }

    /* Main content area */
    .headlesswp-main-content {
        flex: 1;
        padding: 20px;
        background-color: #fff;
        overflow-y: auto;
        max-height: calc(100vh - 200px);
    }

    /* Namespace selector */
    .headlesswp-namespace-selector {
        padding: 15px;
        background-color: #fff;
        border-bottom: 1px solid #c3c4c7;
    }

    .headlesswp-namespace-selector label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .headlesswp-namespace-selector select {
        width: 100%;
    }

    /* Search bar */
    .headlesswp-search-bar {
        padding: 10px 15px;
        background-color: #fff;
        border-bottom: 1px solid #c3c4c7;
        position: relative;
    }

    .headlesswp-search-bar input {
        width: 100%;
        padding: 5px 30px 5px 8px;
    }

    .headlesswp-search-bar #clear-search {
        position: absolute;
        right: 20px;
        top: 15px;
        cursor: pointer;
        color: #999;
    }

    .headlesswp-search-bar #clear-search:hover {
        color: #d63638;
    }

    /* Endpoint tree */
    .headlesswp-endpoint-tree {
        padding: 10px 0;
    }

    .headlesswp-tree-item {
        padding: 5px 15px;
        margin: 0;
        cursor: pointer;
    }

    .headlesswp-tree-item:hover {
        background-color: #e9e9e9;
    }

    .headlesswp-tree-active {
        background-color: #e0e0e0;
        border-left: 3px solid #2271b1;
    }

    .headlesswp-tree-link {
        display: block;
        text-decoration: none;
        color: #2c3338;
    }

    .headlesswp-tree-endpoint {
        display: inline-block;
        margin-right: 5px;
        word-break: break-word;
    }

    .headlesswp-tree-directory {
        padding: 5px 15px;
        margin: 0;
        cursor: pointer;
        font-weight: 600;
    }

    .headlesswp-tree-directory:hover {
        background-color: #e9e9e9;
    }

    .headlesswp-tree-toggle {
        display: inline-block;
        width: 20px;
    }

    .headlesswp-tree-dir-name {
        display: inline-block;
    }

    .headlesswp-tree-children {
        display: none;
    }

    /* Method badges */
    .headlesswp-method-badge {
        display: inline-block;
        padding: 2px 6px;
        margin-right: 3px;
        font-size: 10px;
        font-weight: bold;
        border-radius: 3px;
        color: #fff;
    }

    .headlesswp-method-badge.method-get {
        background-color: #61affe;
    }

    .headlesswp-method-badge.method-post {
        background-color: #49cc90;
    }

    .headlesswp-method-badge.method-put {
        background-color: #fca130;
    }

    .headlesswp-method-badge.method-delete {
        background-color: #f93e3e;
    }

    .headlesswp-method-badge.method-patch {
        background-color: #50e3c2;
    }

    .headlesswp-method-badge.method-options,
    .headlesswp-method-badge.method-head {
        background-color: #9012fe;
    }

    /* Status badges */
    .headlesswp-status-badge {
        display: inline-block;
        padding: 4px 8px;
        margin-right: 10px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }

    .headlesswp-status-badge.enabled {
        background-color: #dff0d8;
        color: #3c763d;
        border: 1px solid #d6e9c6;
    }

    .headlesswp-status-badge.disabled {
        background-color: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }

    /* Endpoint details styling */
    .headlesswp-endpoint-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .headlesswp-endpoint-header h2 {
        margin: 0;
        word-break: break-word;
    }

    .headlesswp-endpoint-status {
        display: flex;
        align-items: center;
    }

    .headlesswp-toggle-form {
        display: inline-block;
    }

    /* Copy container */
    .headlesswp-copy-container {
        display: flex;
        align-items: center;
        margin: 10px 0;
    }

    .headlesswp-copy-text {
        display: block;
        flex: 1;
        padding: 10px;
        background: #f6f7f7;
        border-radius: 3px;
        overflow-x: auto;
        margin-right: 10px;
    }

    /* Test form */
    .headlesswp-form-row {
        margin-bottom: 15px;
    }

    .headlesswp-form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    /* Response styling */
    .headlesswp-test-result {
        background-color: #f6f7f7;
        padding: 15px;
        border-radius: 3px;
        border: 1px solid #ddd;
    }

    .headlesswp-response-headers,
    .headlesswp-response-body pre {
        background-color: #fff;
        padding: 10px;
        border-radius: 3px;
        border: 1px solid #ddd;
        overflow-x: auto;
        margin: 0;
    }

    .headlesswp-response-body pre {
        max-height: 400px;
        overflow-y: auto;
    }

    /* Code examples */
    .headlesswp-code-example {
        background-color: #2c3338;
        color: #fff;
        padding: 15px;
        border-radius: 3px;
        margin: 10px 0;
        overflow-x: auto;
    }

    /* Features list */
    .headlesswp-features-list {
        background-color: #f6f7f7;
        padding: 15px 15px 15px 35px;
        border-radius: 3px;
        border-left: 4px solid #2271b1;
    }

    .headlesswp-features-list li {
        margin-bottom: 8px;
    }

    /* Loading indicator */
    .headlesswp-loading {
        text-align: center;
        padding: 20px;
    }

    .headlesswp-loading .spinner {
        float: none;
        margin: 5px auto;
    }
</style>