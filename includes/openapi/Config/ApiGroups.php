<?php

namespace HeadlessWP\OpenAPI\Config;

class ApiGroups {
    /**
     * Get groups from a route path
     * Example: /wp/v2/posts -> posts
     *         /wp/v2/users -> users
     */
    public static function getGroupFromPath(string $path): string {
        // Remove version prefix (e.g., /wp/v2/)
        $parts = explode('/', trim($path, '/'));
        
        // Skip namespace and version (e.g., 'wp' and 'v2')
        if (count($parts) >= 3) {
            return $parts[2]; // Return the resource name (posts, users, etc.)
        }
        
        return 'other';
    }

    /**
     * Get a friendly name for a group
     */
    public static function getGroupDescription(string $group): string {
        return ucfirst($group) . ' API Endpoints';
    }
} 