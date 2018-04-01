<?php

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
