<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();
$phpCsFixerBin = __DIR__ . '/../vendor/bin/php-cs-fixer';
$phpCsFixerConf = __DIR__ . '/../.php_cs';
$currentBranch = trim(exec('git rev-parse --abbrev-ref HEAD'));

class WarningException extends Exception
{
}
class FailureException extends Exception
{
}

try {
    // do not allow committing directly to master branch
    if ($currentBranch === 'master') {
        throw new WarningException("It is not allowed to commit directly into $currentBranch branch!");
    }

    // php-cs-fixer might not be available (eg, we're on an old branch or something)
    if (!file_exists($phpCsFixerBin)) {
        throw new FailureException('PHP-CS-Fixer is not installed!');
    }

    // run php-cs-fixer and ensure it finishes successfully
    passthru(sprintf(
        '%s fix --verbose --config %s --allow-risky yes',
        escapeshellarg(realpath($phpCsFixerBin)),
        escapeshellarg(realpath($phpCsFixerConf))
    ), $result);
    if ($result) {
        throw new FailureException('PHP-CS-Fixer faulted for some reason. Please fix the error and try committing again.');
    }

    // do we have unstaged changes? if yes, tell the user that something probably changed and needs to be staged
    passthru('git diff --quiet', $result);
    if ($result) {
        throw new WarningException('PHP-CS-Fixer fixed some files. Please review changes, add them in git and commit again.');
    }
} catch (\Exception $ex) {
    if ($ex instanceof WarningException) {
        $output->writeln(sprintf('<fg=yellow>%s</>', OutputFormatter::escape($ex->getMessage())));
    } elseif ($ex instanceof FailureException) {
        $output->writeln(sprintf('<fg=red>%s</>', OutputFormatter::escape($ex->getMessage())));
    } else {
        $output->writeln(sprintf('<fg=white;bg=red>%s</>', OutputFormatter::escape((string) $ex)));
    }
    $output->writeln('To skip this check and commit anyway, run again with <info>--no-verify</info> option.');

    die(1); // <- this will actually prevent the commit from taking place
}
