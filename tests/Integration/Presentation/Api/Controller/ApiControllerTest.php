<?php
declare(strict_types=1);

namespace Tests\Integration\Presentation\Api\Controller;

use Tests\Integration\ApiTestCase;

require_once __DIR__ . '/bootstrap.php';

class ApiControllerTest extends ApiTestCase
{
    public function testGetMaintenanceStatus()
    {
        $response = $this->get('/api/maintenance/status');
        $this->assertArrayHasKey('is_active', $response);
        $this->assertIsBool($response['is_active']);
    }

    public function testEnableAndDisableMaintenance()
    {
        // Enable
        $response = $this->post('/api/maintenance/enable', ['reason' => 'test', 'duration' => 60]);
        $this->assertEquals(['message' => 'Maintenance mode enabled.'], $response);

        $status = $this->get('/api/maintenance/status');
        $this->assertTrue($status['is_active']);
        $this->assertEquals('test', $status['reason']);

        // Disable
        $response = $this->post('/api/maintenance/disable');
        $this->assertEquals(['message' => 'Maintenance mode disabled.'], $response);

        $status = $this->get('/api/maintenance/status');
        $this->assertFalse($status['is_active']);
    }

    public function testAddAndRemoveIpFromWhitelist()
    {
        $ip = '123.123.123.123';

        // Add
        $response = $this->post('/api/whitelist/add', ['ip' => $ip]);
        $this->assertEquals(['message' => "IP {$ip} added to whitelist."], $response);

        // Remove
        $response = $this->post('/api/whitelist/remove', ['ip' => $ip]);
        $this->assertEquals(['message' => "IP {$ip} removed from whitelist."], $response);
    }

    public function testWhitelistIpValidation()
    {
        $response = $this->post('/api/whitelist/add', ['ip' => 'invalid-ip']);
        $this->assertEquals(['error' => 'Invalid or missing IP address.'], $response);
    }

    public function testGetHealthCheck()
    {
        $response = $this->get('/api/health/check');
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('checks', $response);
        $this->assertEquals('healthy', $response['status']);
    }

    public function testGetCircuitBreakers()
    {
        $response = $this->get('/api/circuit-breakers');
        $this->assertIsArray($response);
    }

    public function testResetCircuitBreaker()
    {
        $service = 'test-service';
        $response = $this->post("/api/circuit-breakers/{$service}/reset");
        $this->assertEquals(['message' => "Circuit breaker for '{$service}' has been reset."], $response);
    }

    public function testGetMetrics()
    {
        $response = $this->get('/api/metrics?range=1h');
        $this->assertIsArray($response);
    }

    public function testGetMetricsReport()
    {
        $response = $this->get('/api/metrics/report');
        $this->assertIsArray($response);
    }

    public function testNotFoundRoute()
    {
        $response = $this->get('/api/non-existent-route');
        $this->assertEquals(['error' => 'Not Found'], $response);
    }

    public function testIpWhitelistBypass()
    {
        // 1. Enable maintenance mode
        $this->post('/api/maintenance/enable', ['reason' => 'whitelist test']);

        // 2. Add an IP to the whitelist
        $whitelistedIp = '192.168.1.100';
        $this->post('/api/whitelist/add', ['ip' => $whitelistedIp]);

        // 3. Verify a non-whitelisted IP is blocked
        $blockedIp = '10.0.0.5';
        $responseBlocked = $this->getPublic('/', $blockedIp);
        $this->assertStringContainsString('Site Under Maintenance', $responseBlocked);

        // 4. Verify the whitelisted IP bypasses maintenance
        $responseBypassed = $this->getPublic('/', $whitelistedIp);
        $this->assertEquals('Application is running.', $responseBypassed);
    }
}