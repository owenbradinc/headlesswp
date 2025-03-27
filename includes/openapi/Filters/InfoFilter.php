<?php

namespace HeadlessWP\OpenAPI\Filters;

use HeadlessWP\OpenAPI\Spec\License;
use HeadlessWP\OpenAPI\Spec\Info;

class InfoFilter {
    public static function register(): void {
        add_filter('headlesswp-filter-info', [self::class, 'customizeInfo']);
    }

    public static function customizeInfo(Info $info): Info {
        // License info
        $license = new License(
            'MIT',
            'MIT',
            'https://opensource.org/licenses/MIT'
        );
        
        $info->setLicense($license);
        $info->setTermsOfService('http://headlesswp.local/terms');
        
        return $info;
    }
} 