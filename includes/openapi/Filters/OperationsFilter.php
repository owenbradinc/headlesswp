<?php

namespace HeadlessWP\OpenAPI\Filters;

use HeadlessWP\OpenAPI\Spec\Path;

class OperationsFilter {
    public static function register(): void {
        add_filter('headlesswp-filter-paths', [self::class, 'groupPaths']);
    }

    public static function groupPaths(array $paths): array {
        foreach ($paths as $path) {
            $originalPath = $path->getOriginalPath();
            
            // Parse the path to get the group
            $parts = explode('/', trim($originalPath, '/'));
            if (count($parts) >= 3) {
                $group = $parts[2]; // Get the resource name (posts, users, etc.)
                
                // Add the group tag to all operations in this path
                foreach ($path->getOperations() as $operation) {
                    $operation->addTag($group);
                }
            }
        }
        
        return $paths;
    }
} 