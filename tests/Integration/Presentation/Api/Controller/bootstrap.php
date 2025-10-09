<?php
declare(strict_types=1);

namespace MaintenancePro\Presentation\Api\Controller;

/**
 * This function overrides the global file_get_contents() for the ApiController namespace.
 * It allows us to mock `php://input` for testing POST requests.
 */
function file_get_contents(string $filename, ...$args)
{
    if ($filename === 'php://input' && isset($GLOBALS['__PHPUNIT_OVERRIDE_PHP_INPUT_STREAM'])) {
        return stream_get_contents($GLOBALS['__PHPUNIT_OVERRIDE_PHP_INPUT_STREAM']);
    }
    // Fallback to the global function for all other cases.
    return \file_get_contents($filename, ...$args);
}