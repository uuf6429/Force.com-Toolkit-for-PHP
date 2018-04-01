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

namespace SForce\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SForce\SoapGen;

class Plugin implements PluginInterface
{
    const WSDL_SOURCE = 'sforce-wsdl-source';
    const WSDL_CLASSPATH = 'sforce-wsdl-classpath';
    
    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = $composer->getPackage()->getExtra();

        $logger = $io instanceof LoggerInterface ? $io : new NullLogger();

        $wsdlSources = null;
        if (isset($extra[self::WSDL_SOURCE])) {
            $logger->debug('Retrieving WSDL sources');
            if (is_array($extra[self::WSDL_SOURCE])) {
                $wsdlSources = $extra[self::WSDL_SOURCE];
            } elseif (is_callable($extra[self::WSDL_SOURCE])) {
                $wsdlSources = call_user_func($extra[self::WSDL_SOURCE]);
            } else {
                $logger->error('Unexpected WSDL source in composer.json');
            }
        }

        if (isset($extra[self::WSDL_CLASSPATH])) {
            $wsdlClassPath = rtrim($extra[self::WSDL_CLASSPATH], '\\/');
        } else {
            $wsdlClassPath = rtrim($composer->getConfig()->get('vendor-dir'), '\\/') . '/../src/SForce/Wsdl';
        }

        $gen = new SoapGen($wsdlSources, $wsdlClassPath, $logger);
        $gen->generate(); // TODO handle case where user wants to force generation

        $autoloadFile = $wsdlClassPath . '/autoload.php';

        if (!file_exists($autoloadFile)) {
            throw new \RuntimeException("WSDL autoloader does not exist in $autoloadFile");
        }

        $autoload = $composer->getPackage()->getAutoload();
        $autoload['files'][] = $autoloadFile;
        $composer->getPackage()->setAutoload($autoload);
    }
}
