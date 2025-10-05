<?php
/**
 * MaintenancePro - Cache Performance Benchmark
 *
 * This script measures the performance of the AdaptiveCache implementation
 * under different scenarios to validate its efficiency.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MaintenancePro\Application\Kernel;
use MaintenancePro\Domain\Contracts\CacheInterface;

echo str_repeat("=", 80) . "\n";
echo " ADAPTIVE CACHE PERFORMANCE BENCHMARK\n";
echo str_repeat("=", 80) . "\n\n";

// --- Setup ---
$tempDir = sys_get_temp_dir() . '/mp_benchmark_' . uniqid();
mkdir($tempDir, 0755, true);

$kernel = new Kernel($tempDir);
/** @var CacheInterface $cache */
$cache = $kernel->getContainer()->get(CacheInterface::class);

$iterations = 10000;
echo "Running {$iterations} iterations for each test...\n\n";

// --- 1. Cache Write Performance (Warm-up) ---
$start_time = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->set("key_{$i}", "value_{$i}");
}
$end_time = microtime(true);
$write_time = ($end_time - $start_time) * 1000;
printf("1. Cache Write (Warm-up):\n");
printf("   - Total time: %.2f ms\n", $write_time);
printf("   - Avg time per set: %.4f ms\n\n", $write_time / $iterations);


// --- 2. In-Memory Cache Hit Performance ---
$start_time = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->get("key_{$i}");
}
$end_time = microtime(true);
$memory_hit_time = ($end_time - $start_time) * 1000;
printf("2. In-Memory Cache Hit:\n");
printf("   - Total time: %.2f ms\n", $memory_hit_time);
printf("   - Avg time per get: %.4f ms\n\n", $memory_hit_time / $iterations);

// --- 3. Persistent Cache Hit Performance (Cache Warming) ---
// Re-instantiate the kernel and cache to simulate a new request,
// clearing the in-memory layer but keeping the persistent layer.
$kernel = new Kernel($tempDir);
/** @var CacheInterface $cache */
$cache = $kernel->getContainer()->get(CacheInterface::class);

$start_time = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->get("key_{$i}");
}
$end_time = microtime(true);
$persistent_hit_time = ($end_time - $start_time) * 1000;
printf("3. Persistent Cache Hit (Warming In-Memory):\n");
printf("   - Total time: %.2f ms\n", $persistent_hit_time);
printf("   - Avg time per get: %.4f ms\n\n", $persistent_hit_time / $iterations);


// --- 4. Cache Miss Performance ---
$start_time = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->get("non_existent_key_{$i}");
}
$end_time = microtime(true);
$miss_time = ($end_time - $start_time) * 1000;
printf("4. Cache Miss:\n");
printf("   - Total time: %.2f ms\n", $miss_time);
printf("   - Avg time per get: %.4f ms\n\n", $miss_time / $iterations);


echo str_repeat("=", 80) . "\n";
echo " BENCHMARK COMPLETE\n";
echo str_repeat("=", 80) . "\n";

// --- Cleanup ---
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($iterator as $file) {
    $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
}
rmdir($tempDir);