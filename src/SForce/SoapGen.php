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

namespace SForce;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wsdl2PhpGenerator\Config;
use Wsdl2PhpGenerator\Generator;

class SoapGen
{
    const DEFAULT_WSDL_SOURCES = [
        __DIR__ . '/Wsdl/metadata.wsdl.xml',
        __DIR__ . '/Wsdl/partner.wsdl.xml',
        __DIR__ . '/Wsdl/enterprise.wsdl.xml',
    ];

    const DEFAULT_WSDL_CLASSPATH = __DIR__ . '/Wsdl';

    /**
     * @var string[]
     */
    private $wsdlSources;

    /**
     * @var string
     */
    private $wsdlClassPath;

    /**
     * @var string
     */
    private $cacheMarkerFile;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $namespace = 'SForce\\Wsdl';

    /**
     * @param null|string[] $wsdlSources
     * @param null|string $wsdlClassPath
     * @param null|LoggerInterface $logger
     */
    public function __construct($wsdlSources = null, $wsdlClassPath = null, $logger = null)
    {
        $this->wsdlSources = $wsdlSources ?: static::DEFAULT_WSDL_SOURCES;
        $this->wsdlClassPath = rtrim($wsdlClassPath ?: static::DEFAULT_WSDL_CLASSPATH, '\\/') . DIRECTORY_SEPARATOR;
        $this->logger = $logger ?: new NullLogger();

        sort($this->wsdlSources); // avoids invalidating cache by mistake
        $this->cacheMarkerFile = $this->wsdlClassPath . 'cache-marker.php';
    }

    /**
     * @param bool $ignoreCache
     */
    public function generate($ignoreCache = false)
    {
        $cacheMarker = $this->generateCacheMarker();

        if (!$ignoreCache && $cacheMarker === $this->getCurrentCacheMarker()) {
            $this->logger->debug('Skipped generating WSDL classes');

            return;
        }

        $this->logger->debug('Starting WSDL generator');
        $generator = new Generator();

        foreach ($this->wsdlSources as $wsdlSource) {
            $this->logger->info('Generating WSDL classes for ' . basename($wsdlSource));
            $generator->generate(
                new Config([
                    'inputFile' => $wsdlSource,
                    'outputDir' => $this->wsdlClassPath,
                    'namespaceName' => $this->namespace,
                ])
            );
        }
        $this->updateCacheMarker($cacheMarker);

        $this->logger->debug('WSDL class generation complete');
    }

    /**
     * @return string
     */
    private function generateCacheMarker()
    {
        return implode('|', array_map('md5_file', $this->wsdlSources));
    }

    /**
     * @return string
     */
    private function getCurrentCacheMarker()
    {
        if (!file_exists($this->cacheMarkerFile)) {
            return '';
        }

        return (string)(@include $this->cacheMarkerFile);
    }

    /**
     * @param string $cacheMarker
     */
    private function updateCacheMarker($cacheMarker)
    {
        file_put_contents($this->cacheMarkerFile, '<?php return ' . var_export($cacheMarker, true) . ';');
    }
}
