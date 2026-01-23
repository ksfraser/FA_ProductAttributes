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

    /** @var string */
    private $faHooksPath;

    /** @var string */
    private $faHooksComposerJsonPath;

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

        // FA Hooks submodule paths
        $this->faHooksPath = $this->modulePath . DIRECTORY_SEPARATOR . 'fa-hooks';
        $this->faHooksComposerJsonPath = $this->faHooksPath . DIRECTORY_SEPARATOR . 'composer.json';
    }

    /**
     * Install composer dependencies for both main module and fa-hooks
     *
     * @return array ['success' => bool, 'message' => string, 'output' => string]
     */
    public function install(): array
    {
        $results = [];

        // Install main module dependencies
        $mainResult = $this->installMainModule();
        $results[] = $mainResult;

        // Install fa-hooks dependencies
        $hooksResult = $this->installFaHooks();
        $results[] = $hooksResult;

        // Determine overall success
        $allSuccessful = array_reduce($results, function($carry, $result) {
            return $carry && $result['success'];
        }, true);

        $messages = array_column($results, 'message');
        $outputs = array_filter(array_column($results, 'output'));

        return [
            'success' => $allSuccessful,
            'message' => implode('; ', $messages),
            'output' => implode("\n", $outputs)
        ];
    }

    /**
     * Install main module composer dependencies
     *
     * @return array ['success' => bool, 'message' => string, 'output' => string]
     */
    private function installMainModule(): array
    {
        try {
            // Check if composer.json exists
            if (!file_exists($this->composerJsonPath)) {
                return [
                    'success' => false,
                    'message' => 'Main module composer.json not found at: ' . $this->composerJsonPath,
                    'output' => ''
                ];
            }

            // Check if vendor directory already exists and is populated
            if ($this->isVendorInstalled()) {
                return [
                    'success' => true,
                    'message' => 'Main module dependencies already installed',
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
                    'message' => 'Main module dependencies installed successfully',
                    'output' => $result['output']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to install main module dependencies: ' . $result['error'],
                    'output' => $result['output']
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception during main module installation: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }

    /**
     * Install fa-hooks composer dependencies
     *
     * @return array ['success' => bool, 'message' => string, 'output' => string]
     */
    private function installFaHooks(): array
    {
        try {
            // Check if fa-hooks directory exists (submodule initialized)
            if (!is_dir($this->faHooksPath)) {
                return [
                    'success' => false,
                    'message' => 'FA Hooks submodule not initialized. Run: git submodule update --init --recursive',
                    'output' => ''
                ];
            }

            // Check if composer.json exists
            if (!file_exists($this->faHooksComposerJsonPath)) {
                return [
                    'success' => false,
                    'message' => 'FA Hooks composer.json not found at: ' . $this->faHooksComposerJsonPath,
                    'output' => ''
                ];
            }

            // Check if fa-hooks vendor directory already exists and is populated
            if ($this->isFaHooksVendorInstalled()) {
                return [
                    'success' => true,
                    'message' => 'FA Hooks dependencies already installed',
                    'output' => 'FA Hooks vendor directory exists and contains packages'
                ];
            }

            // Run composer install for fa-hooks
            $result = $this->runFaHooksComposerInstall();

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'FA Hooks dependencies installed successfully',
                    'output' => $result['output']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to install FA Hooks dependencies: ' . $result['error'],
                    'output' => $result['output']
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception during FA Hooks installation: ' . $e->getMessage(),
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
     * Check if fa-hooks vendor directory is properly installed
     *
     * @return bool
     */
    private function isFaHooksVendorInstalled(): bool
    {
        $faHooksVendorPath = $this->faHooksPath . DIRECTORY_SEPARATOR . 'vendor';

        if (!is_dir($faHooksVendorPath)) {
            return false;
        }

        // Check for key files that indicate a successful composer install
        $keyFiles = [
            'autoload.php',
            'composer'
        ];

        foreach ($keyFiles as $file) {
            if (!file_exists($faHooksVendorPath . DIRECTORY_SEPARATOR . $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run composer install command for fa-hooks
     *
     * @return array ['success' => bool, 'output' => string, 'error' => string]
     */
    private function runFaHooksComposerInstall(): array
    {
        $output = [];
        $error = '';
        $returnCode = 0;

        // Change to fa-hooks directory and run install
        $command = 'cd ' . escapeshellarg($this->faHooksPath) . ' && composer install --no-dev --optimize-autoloader 2>&1';

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
                'error' => 'FA Hooks composer install failed with exit code: ' . $returnCode
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
            'vendor_path' => $this->vendorPath,
            'fa_hooks_path' => $this->faHooksPath,
            'fa_hooks_composer_json_exists' => file_exists($this->faHooksComposerJsonPath),
            'fa_hooks_vendor_installed' => $this->isFaHooksVendorInstalled()
        ];
    }
}