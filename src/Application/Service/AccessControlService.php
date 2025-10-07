<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Application\LoggerInterface;
use MaintenancePro\Domain\Contracts\CacheInterface;
use MaintenancePro\Domain\Contracts\ConfigurationInterface;
use MaintenancePro\Domain\ValueObjects\IPAddress;

/**
 * Manages access control rules, such as IP whitelisting and access keys.
 */
class AccessControlService
{
    private ConfigurationInterface $config;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    /**
     * AccessControlService constructor.
     *
     * @param ConfigurationInterface $config The application configuration.
     * @param CacheInterface         $cache  The cache for storing access control checks.
     * @param LoggerInterface        $logger The logger for recording access control events.
     */
    public function __construct(
        ConfigurationInterface $config,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Checks if a given IP address is whitelisted.
     *
     * The result is cached to improve performance for subsequent checks.
     *
     * @param IPAddress $ip The IP address to check.
     * @return bool True if the IP is whitelisted, false otherwise.
     */
    public function isIPWhitelisted(IPAddress $ip): bool
    {
        $cacheKey = 'whitelist_check_' . md5($ip->toString());

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $whitelist = $this->config->get('access.whitelist.ips', []);

        foreach ($whitelist as $allowedIP) {
            if ($ip->inRange($allowedIP)) {
                $this->cache->set($cacheKey, true, 300);
                return true;
            }
        }

        $this->cache->set($cacheKey, false, 300);
        return false;
    }

    /**
     * Adds an IP address or CIDR range to the whitelist.
     *
     * @param string $ip The IP address or CIDR range to add.
     */
    public function addToWhitelist(string $ip): void
    {
        $ipObject = new IPAddress($ip);

        $whitelist = $this->config->get('access.whitelist.ips', []);

        if (!in_array($ip, $whitelist, true)) {
            $whitelist[] = $ip;
            $this->config->set('access.whitelist.ips', $whitelist);
            $this->config->save();

            $cacheKey = 'whitelist_check_' . md5($ip);
            $this->cache->delete($cacheKey);

            $this->logger->info("IP added to whitelist: {$ip}");
        }
    }

    /**
     * Removes an IP address or CIDR range from the whitelist.
     *
     * @param string $ip The IP address or CIDR range to remove.
     */
    public function removeFromWhitelist(string $ip): void
    {
        $whitelist = $this->config->get('access.whitelist.ips', []);

        $key = array_search($ip, $whitelist, true);
        if ($key !== false) {
            unset($whitelist[$key]);
            $whitelist = array_values($whitelist);

            $this->config->set('access.whitelist.ips', $whitelist);
            $this->config->save();

            $cacheKey = 'whitelist_check_' . md5($ip);
            $this->cache->delete($cacheKey);

            $this->logger->info("IP removed from whitelist: {$ip}");
        }
    }

    /**
     * Validates an access key against the configured list of valid keys.
     *
     * @param string $key The access key to validate.
     * @return bool True if the key is valid, false otherwise.
     */
    public function isValidAccessKey(string $key): bool
    {
        $validKeys = $this->config->get('access.access_keys', []);
        return in_array($key, $validKeys, true);
    }
}