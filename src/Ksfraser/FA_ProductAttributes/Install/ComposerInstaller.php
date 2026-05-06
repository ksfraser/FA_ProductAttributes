<?php

namespace Ksfraser\FA_ProductAttributes\Install;

/**
 * Single Responsibility: Checks and runs Composer installation for the module's composer-lib directory.
 */
class ComposerInstaller
{
    /** @var string Absolute path to the module root */
    private $modulePath;

    /** @var string Path to the composer.json inside composer-lib/ */
    private $composerJsonPath;

    /** @var string Path to the vendor/ directory inside composer-lib/ */
    private $vendorPath;

    public function __construct(string $modulePath)
    {
        $this->modulePath       = $modulePath;
        $this->composerJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'composer-lib' . DIRECTORY_SEPARATOR . 'composer.json';
        $this->vendorPath       = $modulePath . DIRECTORY_SEPARATOR . 'composer-lib' . DIRECTORY_SEPARATOR . 'vendor';
    }

    /**
     * Return the current installation status.
     *
     * @return array{
     *   module_path: string,
     *   composer_json_exists: bool,
     *   vendor_installed: bool,
     *   composer_available: bool,
     *   composer_json_path: string,
     *   vendor_path: string
     * }
     */
    public function getStatus(): array
    {
        return [
            'module_path'          => $this->modulePath,
            'composer_json_exists' => file_exists($this->composerJsonPath),
            'vendor_installed'     => is_dir($this->vendorPath),
            'composer_available'   => $this->isComposerAvailable(),
            'composer_json_path'   => $this->composerJsonPath,
            'vendor_path'          => $this->vendorPath,
        ];
    }

    /**
     * Run "composer install" inside the composer-lib directory.
     *
     * @return array{success: bool, message: string, output: string}
     */
    public function install(): array
    {
        if (!file_exists($this->composerJsonPath)) {
            return [
                'success' => false,
                'message' => 'composer.json not found at ' . $this->composerJsonPath,
                'output'  => '',
            ];
        }

        if (!$this->isComposerAvailable()) {
            return [
                'success' => false,
                'message' => 'Composer executable not found. Please install Composer and ensure it is on the PATH.',
                'output'  => '',
            ];
        }

        $workDir = dirname($this->composerJsonPath);
        $command = 'composer install --no-interaction --no-ansi 2>&1';
        $output  = '';
        $returnCode = 0;

        $prevDir = getcwd();
        chdir($workDir);
        exec($command, $outputLines, $returnCode);
        chdir($prevDir);

        $output = implode(PHP_EOL, $outputLines);

        if ($returnCode === 0) {
            return [
                'success' => true,
                'message' => 'Composer install completed successfully.',
                'output'  => $output,
            ];
        }

        return [
            'success' => false,
            'message' => 'Composer install failed with exit code ' . $returnCode . '.',
            'output'  => $output,
        ];
    }

    /**
     * Detect whether the "composer" executable is available on the system PATH.
     */
    public function isComposerAvailable(): bool
    {
        $command    = (PHP_OS_FAMILY === 'Windows') ? 'where composer 2>NUL' : 'which composer 2>/dev/null';
        $output     = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        return $returnCode === 0;
    }
}
