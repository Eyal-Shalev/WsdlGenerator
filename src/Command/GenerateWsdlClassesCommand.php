<?php

/**
 * @file
 * Contains \EyalShalev\WsdlGenerator\Command\GenerateWsdlClassesCommand.
 */

namespace EyalShalev\WsdlGenerator\Command;

# Symfony
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
# wsdl2phpgenerator
use Wsdl2PhpGenerator\Config as WsdlConfig;
use Wsdl2PhpGenerator\Generator as WsdlGenerator;

/**
 * Class GenerateWsdlCommand.
 *
 * @package EyalShalev\WsdlGenerator\Command
 */
class GenerateWsdlClassesCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {

    $this
      ->setName('generate')
      ->setDescription('Generate WSDL classes')
      ->addArgument('endpoint', InputArgument::REQUIRED, 'Where is the WSDL endpoint?')
      ->addArgument('directory', InputArgument::OPTIONAL, 'In what directory do you want to place the WSDL files?', './wsdl')
      ->addOption('namespace', 's', InputOption::VALUE_OPTIONAL, 'What namespace do you want the WSDL classes to use?', 'wsdl')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do you want to remove existing files?');
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
      $output->write('Moving the existing directory to the temp directory');
      $output->write(" ({$directory} -> {$tmp_directory})", OutputInterface::VERBOSITY_VERBOSE);
      $fs->rename($directory, $tmp_directory);
    }

    if ($endpoint) {
      try {
        $output->write('Building WSDL to the directory');
        $output->write(": {$directory}", OutputInterface::VERBOSITY_VERBOSE);
        $generator = new WsdlGenerator();
        $generator->generate(new WsdlConfig($generator_options));
        $output->writeln('WSDL has been built successfully');
        if ($remove_existing) {
          $output->write('Removing the temp directory');
          $output->write(": {$tmp_directory}", OutputInterface::VERBOSITY_VERBOSE);
          $fs->remove($tmp_directory);
        }
      }
      catch (\Exception $e) {
        $output->writeln($output->getFormatter()->format('The WSDL was not built due to an error'));
        $output->writeln($output->getFormatter()->format("Error Code: {$e->getCode()}"));
        $output->writeln($output->getFormatter()->format("Error Message: {$e->getMessage()}"));
        if ($remove_existing) {
          $output->write('Moving the temp directory back to the existing location');
          $output->write(" ({$tmp_directory} -> {$directory})", OutputInterface::VERBOSITY_VERBOSE);
          $fs->rename($tmp_directory, $directory);
        }
      }
    }
  }

}
