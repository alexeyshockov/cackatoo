<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    const VERSION = 0;

    public function __construct($environment, $debug = null)
    {
        if (is_null($debug)) {
            $debug = ('prod' != $environment);
        }

        parent::__construct($environment, $debug);

        $this->name = 'cackatoo';
    }

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),

            new Symfony\Bundle\MonologBundle\MonologBundle(),

            new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),

            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),

            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),

            new Cackatoo\CackatooBundle\CackatooBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            array(
                'app.version' => self::VERSION
            )
        );
    }

    public function init()
    {
        parent::init();

        // Useful for working with cache, logs and other generated files.
        umask(0002);

        // Default time zone for our application - UTC.
        date_default_timezone_set('UTC');

        setlocale(LC_ALL, 'ru_RU.utf8');
    }

    public function getLogDir()
    {
        return $this->rootDir.'/logs';
    }

    public function getCacheDir()
    {
        return $this->rootDir.'/cache/'.$this->getEnvironment();
    }
}
