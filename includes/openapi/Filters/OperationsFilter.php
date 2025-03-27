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

            // Custom descriptions with Markdown for the posts endpoint
            foreach ($path->getOperations() as $operation) {
                // Example: Customize description based on path and method
                if (strpos($originalPath, '/wp/v2/posts') !== false) {
                    if ($operation->getMethod() === 'get') {
                        $operation->setDescription(<<<MARKDOWN
                            ## Posts Endpoint

                            Returns a collection of posts from your WordPress site.

                            ### Features
                            - Supports pagination
                            - Filter by category, tag, or author
                            - Order by date, title, or other fields

                            ### Example Usage
                            ```javascript
                            fetch('/wp-json/wp/v2/posts?per_page=5&orderby=date')
                            .then(response => response.json())
                            .then(posts => console.log(posts));
                            ```

                            *Note: Authentication may be required for certain post types.*
                            MARKDOWN
                        );
                    }
                }
            }
        }
        
        return $paths;
    }
} 