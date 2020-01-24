<?php
declare(strict_types=1);

/**
 * Plugin
 *
 * @copyright Copyright © 2020 Arnaud Amant. All rights reserved.
 * @author Arnaud Amant <contact@arnaudamant.fr>
 */

namespace Aamant\ComposerEolCleaner;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;
    /**
     * @var IOInterface $io
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => ['execute', 100],
            PackageEvents::POST_PACKAGE_UPDATE => ['execute', 100],
        ];
    }

    public function execute(\Composer\Installer\PackageEvent $event)
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $extra = $this->composer->getPackage()->getExtra();
        $packages = $extra['convert-eol'] ?? (array) $this->composer->getConfig()->get('convert-eol');

        $fileSystem = new Filesystem(new ProcessExecutor($this->io));

        foreach ($packages as $packageName => $paths) {
            if ($this->getPackageFromOperation($event->getOperation()) != $packageName) {
                continue;
            }
            foreach ($paths as $path) {
                $filename = $vendorDir . '/' . $packageName . '/' .$path;

                if (! file_exists($filename)) {
                    return;
                }

                $this->io->write(sprintf('  - Convert EOL to %s', $filename));
                $fileSystem->copy($filename, $filename . '.origin');

                $content = $this->normalize(file_get_contents($filename));
                file_put_contents($filename, $content);
            }
        }
    }

    private function normalize($content)
    {
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        // Don't allow out-of-control blank lines
        $content = preg_replace("/\n{2,}/", "\n" . "\n", $content);
        return $content;
    }

    /**
     * @param OperationInterface $operation
     * @return string
     * @throws \Exception
     */
    protected function getPackageFromOperation(OperationInterface $operation)
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        }
        elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        }
        else {
            throw new \Exception('Unknown operation: ' . get_class($operation));
        }

        return $package->getName();
    }
}