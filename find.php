#!/usr/bin/env php
<?php

use DrupalIssueFinder\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


require_once __DIR__ . '/vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$loader = new YamlFileLoader($containerBuilder, new FileLocator());
$loader->load(__DIR__ . '/config/services.yml');

$containerBuilder->compile();

/** @var Application $application */
$application = $containerBuilder->get(Application::class);
//$application = new Application('Drupal Issue Finder', '0.1.0');
//print_r($containerBuilder->getServiceIds());
foreach ($containerBuilder->getServiceIds() as $serviceId) {
  $service = $containerBuilder->get($serviceId);
  if ($service !== null && is_a($service, Command::class)) {
    $application->add($service);
  }
}
$application->setDefaultCommand('find:find');
$application->run();


