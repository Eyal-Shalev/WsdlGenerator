<?php

/**
 * @file
 * Contains \Drupal\wsdl2phpgenerator_command\Command\GenerateWsdlCommand.
 */

namespace Drupal\wsdl_generator\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;

use Symfony\Component\Filesystem\Filesystem;
use Wsdl2PhpGenerator\Config as WsdlConfig;
use Wsdl2PhpGenerator\Generator as WsdlGenerator;

/**
 * Class GenerateWsdlCommand.
 *
 * @package Drupal\wsdl2phpgenerator_command
 */
class GenerateWsdlCommand extends Command {
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:wsdl')
      ->setDescription($this->trans('command.generate.wsdl.description'))
      ->addArgument('endpoint', InputArgument::REQUIRED, $this->trans('command.generate.wsdl.arguments.endpoint'))
      ->addArgument('directory', InputArgument::OPTIONAL, $this->trans('command.generate.wsdl.arguments.directory'), './wsdl')
      ->addOption('namespace', 's', InputOption::VALUE_OPTIONAL, $this->trans('command.generate.wsdl.options.namespace'), 'wsdl')
      ->addOption('remove-existing', 'f', InputOption::VALUE_NONE, $this->trans('command.generate.wsdl.options.remove_existing'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $endpoint = $input->getArgument('endpoint');
    $directory = $input->getArgument('directory');

    $generator_options = [
      'inputFile' => $endpoint,
      'outputDir' => $directory
    ];

    if ($input->hasOption('namespace')) {
      $generator_options['namespaceName'] = $input->getOption('namespace');
    }

    $tmp_directory = $directory . '_tmp';
    $fs = new Filesystem();
    if ($remove_existing = $input->hasOption('remove-existing')) {
      $counter = 1;
      while ($fs->exists($tmp_directory)) {
        $tmp_directory .= '_' . $counter++;
      }
      $output->writeln("Moving the existing directory to the temp directory ({$directory} -> {$tmp_directory}).");
      $fs->rename($directory, $tmp_directory);
    }

    if ($endpoint) {
      try {
        $output->writeln("Building WSDL to the directory: {$directory}");
        $generator = new WsdlGenerator();
        $generator->generate(new WsdlConfig($generator_options));
        $output->writeln('WSDL has been built successfully');
        if ($remove_existing) {
          $output->writeln("Removing the temp directory: {$tmp_directory}");
          $fs->remove($tmp_directory);
        }
      }
      catch (\Exception $e) {
        $output->writeln($output->getFormatter()->format('The WSDL was not built due to an error'));
        $output->writeln($output->getFormatter()->format("Error Code: {$e->getCode()}"));
        $output->writeln($output->getFormatter()->format("Error Message: {$e->getMessage()}"));
        if ($remove_existing) {
          $output->writeln("Moving the temp directory back to the existing location ({$tmp_directory} -> {$directory})");
          $fs->rename($tmp_directory, $directory);
        }
      }
    }
  }

}
