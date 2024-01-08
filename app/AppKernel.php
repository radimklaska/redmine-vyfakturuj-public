<?php

# app/AppKernel.php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AppKernel extends Kernel
{
  /**
   * In more complex app, add bundles here
   */
  public function registerBundles(): array
  {
    return [];
  }

  /**
   * Load all services
   */
  public function registerContainerConfiguration(LoaderInterface $loader): void
  {
    $loader->load(__DIR__ . '/../config/services.yml');
  }
}