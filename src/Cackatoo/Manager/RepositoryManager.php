<?php

namespace Cackatoo\Manager;

use JMS\DiExtraBundle\Annotation as DI;

use Cackatoo\Model\Project;
use Cackatoo\Model\Repository;

/**
 * For .deb repository with SSL authentication at this time.
 *
 * @DI\Service("cackatoo.repository_manager")
 *
 * @author Alexy Shockov <alexey@shockov.com>
 */
class RepositoryManager
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @var array
     */
    private $repositories;

    /**
     * @DI\InjectParams({
     *     "repositories" = @DI\Inject("%deb%"),
     * })
     */
    public function __construct($repositories)
    {
        // %deb.repositories% doesn't work. Why?..
        $this->metadata = $repositories['repositories'];
    }

    /**
     * @param string $code
     *
     * @return \Colada\Option
     */
    public function findBy($code)
    {
        if (isset($this->repositories[$code])) {
            return $this->repositories[$code];
        }

        return $this->repositories[$code] = $this->loadBy($code);
    }

    private function loadBy($code)
    {
        if (!isset($this->metadata[$code])) {
            return option(null);
        }

        $repository = $this->metadata[$code];

        $client = new \Buzz\Client\Curl();

        // CURLOPT_SSL_VERIFYHOST, CURLOPT_SSL_VERIFYPEER.
        $client->setVerifyPeer(false);
        $client->setOption(CURLOPT_SSLCERT, $repository['authentication']['ssl']['certificate']);
        $client->setOption(CURLOPT_SSLKEY, $repository['authentication']['ssl']['key']);

        // TODO To container with factory.
        $browser = new \Buzz\Browser($client);

        $response = $browser->get($repository['packages_url']);

        if ($response->isOk()) {
            $packages = $response->getContent();
        } else {
            throw new \RuntimeException('Unable to receive repository metadata.');
        }

        return option(new Repository($packages));
    }
}
