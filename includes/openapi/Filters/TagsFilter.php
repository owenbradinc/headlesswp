<?php

namespace HeadlessWP\OpenAPI\Filters;

use HeadlessWP\OpenAPI\Spec\Tag;

class TagsFilter {
    public static function register(): void {
        add_filter('headlesswp-filter-tags', [self::class, 'addTags']);
    }

    public static function addTags(array $tags): array {
        // Get all routes from WordPress
        $server = rest_get_server();
        $routes = $server->get_routes();
        
        // Track unique groups to avoid duplicates
        $groups = [];
        
        foreach ($routes as $route => $args) {
            // Parse the route to get the group name
            $parts = explode('/', trim($route, '/'));
            if (count($parts) >= 3) {
                $group = $parts[2]; // Get the resource name (posts, users, etc.)
                
                // Only add each group once
                if (!isset($groups[$group])) {
                    $groups[$group] = true;
                    $tags[] = new Tag(
                        $group,
                        ucfirst($group) . ' API Endpoints'
                    );
                }
            }
        }
        
        return $tags;
    }
} 