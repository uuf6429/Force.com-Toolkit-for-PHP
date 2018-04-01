<?php

/*
 * Copyright (c) 2007, salesforce.com, inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided
 * that the following conditions are met:
 *
 *    Redistributions of source code must retain the above copyright notice, this list of conditions and the
 *    following disclaimer.
 *
 *    Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *    the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *    Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
 *    promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

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

    // ensure wsdl classes are generated
    passthru('php ' . escapeshellarg(__DIR__ . '/generate-wsdl-classes.php'));

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
