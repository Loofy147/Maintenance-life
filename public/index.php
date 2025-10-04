<?php
declare(strict_types=1);

// public/index.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use MaintenancePro\Application\Kernel;

try {
    $app = new Kernel(dirname(__DIR__));
    $app->run();
} catch (\Exception $e) {
    // A fallback for critical bootstrap errors
    http_response_code(500);
    echo "A critical error occurred. Please try again later.";
    error_log("Fatal bootstrap error: " . $e->getMessage());
    exit(1);
}