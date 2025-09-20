<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestingEnvironmentTest extends TestCase
{
    /**
     * Test that PHPUnit is configured correctly.
     */
    public function test_phpunit_is_configured_correctly(): void
    {
        // Test that we can run basic assertions
        $this->assertTrue(true);
        $this->assertFalse(false);
        $this->assertEquals(1, 1);
        $this->assertNotEquals(1, 2);
    }

    /**
     * Test that PHP version meets requirements.
     */
    public function test_php_version_meets_requirements(): void
    {
        $phpVersion = PHP_VERSION;

        // Laravel 11 requires PHP 8.2+
        $this->assertGreaterThanOrEqual('8.2.0', $phpVersion,
            "PHP version {$phpVersion} does not meet minimum requirement of 8.2.0");
    }

    /**
     * Test that required PHP extensions are loaded.
     */
    public function test_required_php_extensions_are_loaded(): void
    {
        $requiredExtensions = [
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
            'ctype',
            'fileinfo',
        ];

        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(extension_loaded($extension),
                "Required PHP extension '{$extension}' is not loaded");
        }
    }

    /**
     * Test that memory limit is sufficient for testing.
     */
    public function test_memory_limit_is_sufficient(): void
    {
        $memoryLimit = ini_get('memory_limit');

        // Convert memory limit to bytes for comparison
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);

        // Require at least 128MB for testing
        $minimumMemory = 128 * 1024 * 1024; // 128MB in bytes

        $this->assertGreaterThanOrEqual($minimumMemory, $memoryLimitBytes,
            "Memory limit {$memoryLimit} is insufficient for testing (minimum 128M required)");
    }

    /**
     * Test that execution time limit is reasonable for CI.
     */
    public function test_execution_time_limit_is_reasonable(): void
    {
        $maxExecutionTime = ini_get('max_execution_time');

        // In CI, we want either unlimited (0) or at least 300 seconds
        $this->assertTrue($maxExecutionTime == 0 || $maxExecutionTime >= 300,
            "Max execution time {$maxExecutionTime} may be too low for CI testing");
    }

    /**
     * Convert memory limit string to bytes.
     */
    private function convertToBytes(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX; // Unlimited
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }
}
