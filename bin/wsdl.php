#!/usr/bin/env php
<?php
// application.php

require __DIR__.'../../../autoload.php';

use EyalShalev\WsdlGenerator\Command\GenerateWsdlClassesCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new GenerateWsdlClassesCommand());
$application->run();