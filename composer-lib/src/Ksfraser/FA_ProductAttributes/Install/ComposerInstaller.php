<?php

namespace Ksfraser\FA_ProductAttributes\Install;

use Exception;

/**
 * Composer Installer for FA Product Attributes Module
 *
 * Handles automatic installation of PHP dependencies during module installation.
 * This ensures all required libraries are available when the module is activated.
 */
class ComposerInstaller
{
    /** @var string */
    private $modulePath;

    /** @var string */
    private $composerJsonPath;

    /** @var string */
    private $vendorPath;

    /**
     * Constructor
     *
     * @param string $modulePath Absolute path to the module directory
     */
    public function __construct(string $modulePath)
    {
        $this->modulePath = rtrim($modulePath, DIRECTORY_SEPARATOR);
        $this->composerJsonPath = $this->modulePath . DIRECTORY_SEPARATOR . 'composer-lib' . DIRECTORY_SEPARATOR . 'composer.json';
        $this->vendorPath = $this->modulePath . DIRECTORY_SEPARATOR . 'composer-lib' . DIRECTORY_SEPARATOR . 'vendor';
    }

    /**
     * Install composer dependencies
     *
     * @return array ['success' => bool, 'message' => string, 'output' => string]
     */
    public function install(): array
    {
        try {
            // Check if composer.json exists
            if (!file_exists($this->composerJsonPath)) {
                return [
                    'success' => false,
                    'message' => 'composer.json not found at: ' . $this->composerJsonPath,
                    'output' => ''
                ];
            }

            // Check if vendor directory already exists and is populated
            if ($this->isVendorInstalled()) {
                return [
                    'success' => true,
                    'message' => 'Composer dependencies already installed',
                    'output' => 'Vendor directory exists and contains packages'
                ];
            }

            // Check if composer is available
            if (!$this->isComposerAvailable()) {
                return [
                    'success' => false,
                    'message' => 'Composer is not available. Please install Composer globally or ensure it\'s in your PATH.',
                    'output' => ''
                ];
            }

            // Run composer install
            $result = $this->runComposerInstall();

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Composer dependencies installed successfully',
                    'output' => $result['output']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to install composer dependencies: ' . $result['error'],
                    'output' => $result['output']
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception during composer installation: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }

    /**
     * Check if vendor directory is properly installed
     *
     * @return bool
     */
    private function isVendorInstalled(): bool
    {
        if (!is_dir($this->vendorPath)) {
            return false;
        }

        // Check for some key files that indicate a successful composer install
        $keyFiles = [
            'autoload.php',
            'composer',
            'phpunit'
        ];

        foreach ($keyFiles as $file) {
            if (!file_exists($this->vendorPath . DIRECTORY_SEPARATOR . $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if composer command is available
     *
     * @return bool
     */
    private function isComposerAvailable(): bool
    {
        $output = [];
        $returnCode = 0;

        // Try different composer commands
        $commands = [
            'composer --version',
            'composer.phar --version',
            'php composer.phar --version'
        ];

        foreach ($commands as $command) {
            exec($command . ' 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run composer install command
     *
     * @return array ['success' => bool, 'output' => string, 'error' => string]
     */
    private function runComposerInstall(): array
    {
        $composerDir = dirname($this->composerJsonPath);
        $output = [];
        $error = '';
        $returnCode = 0;

        // Change to composer directory and run install
        $command = 'cd ' . escapeshellarg($composerDir) . ' && composer install --no-dev --optimize-autoloader 2>&1';

        exec($command, $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode === 0) {
            return [
                'success' => true,
                'output' => $outputStr,
                'error' => ''
            ];
        } else {
            return [
                'success' => false,
                'output' => $outputStr,
                'error' => 'Composer install failed with exit code: ' . $returnCode
            ];
        }
    }

    /**
     * Get installation status information
     *
     * @return array
     */
    public function getStatus(): array
    {
        return [
            'module_path' => $this->modulePath,
            'composer_json_exists' => file_exists($this->composerJsonPath),
            'vendor_installed' => $this->isVendorInstalled(),
            'composer_available' => $this->isComposerAvailable(),
            'composer_json_path' => $this->composerJsonPath,
            'vendor_path' => $this->vendorPath
        ];
    }
}