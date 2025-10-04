<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Domain\ValueObject\IPAddress;
use MaintenancePro\Infrastructure\Cache\CacheInterface;
use MaintenancePro\Infrastructure\Config\ConfigurationManagerInterface;
use MaintenancePro\Infrastructure\Logger\LoggerInterface;

class AccessControlService
{
    private ConfigurationManagerInterface $config;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ConfigurationManagerInterface $config,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Check if IP is whitelisted
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
     * Add IP to whitelist
     */
    public function addToWhitelist(string $ip): void
    {
        $ipObject = new IPAddress($ip);

        $whitelist = $this->config->get('access.whitelist.ips', []);

        if (!in_array($ip, $whitelist, true)) {
            $whitelist[] = $ip;
            $this->config->set('access.whitelist.ips', $whitelist);
            $this->config->save($this->config->get('system.config_path'));

            $this->logger->info("IP added to whitelist: {$ip}");
        }
    }

    /**
     * Remove IP from whitelist
     */
    public function removeFromWhitelist(string $ip): void
    {
        $whitelist = $this->config->get('access.whitelist.ips', []);

        $key = array_search($ip, $whitelist, true);
        if ($key !== false) {
            unset($whitelist[$key]);
            $whitelist = array_values($whitelist);

            $this->config->set('access.whitelist.ips', $whitelist);
            $this->config->save($this->config->get('system.config_path'));

            $this->logger->info("IP removed from whitelist: {$ip}");
        }
    }

    /**
     * Check if access key is valid
     */
    public function isValidAccessKey(string $key): bool
    {
        $validKeys = $this->config->get('access.access_keys', []);
        return in_array($key, $validKeys, true);
    }
}