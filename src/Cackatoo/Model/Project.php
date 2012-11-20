<?php

namespace Cackatoo\Model;

use Colada\Collection;
use Colada\Option;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Project
{
    /**
     * @var string
     */
    private $code;

    private $parameters;

    /**
     * @var \Colada\Collection
     */
    private $timeline;

    /**
     * @var \Colada\Option
     */
    private $latestPackage;

    /**
     * @internal
     */
    public function __construct($code, $parameters, Collection $timeline, Option $latestPackage)
    {
        $this->code       = $code;
        $this->parameters = $parameters;

        $this->timeline = $timeline;

        $this->latestPackage = $latestPackage;
    }

    /**
     * @param string $parameter
     *
     * @return \Colada\Option
     */
    public function getParameter($parameter)
    {
        if (!isset($this->parameters[$parameter])) {
            return option(null);
        }

        return option($this->parameters[$parameter]);
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        return $this->parameters['nodes'];
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return null|string
     */
    public function getCurrentVersion()
    {
        return $this->getSuccessfulDeploys()->head()->mapBy(x()->getVersion())->orNull();
    }

    /**
     * @return null|string
     */
    public function getLatestVersion()
    {
        return $this->latestPackage->mapBy(x()->getVersion())->orNull();
    }

    public function isOutdated()
    {
        return version_compare(
            $this->getCurrentVersion(),
            $this->getLatestVersion(),
            '<'
        );
    }

    /**
     * @return \Colada\Collection
     */
    public function getTimeline()
    {
        return $this->timeline;
    }

    /**
     * @return \Colada\Collection
     */
    public function getSuccessfulDeploys()
    {
        return $this->timeline->rejectBy(x()->isFailed());
    }

    /**
     * @param mixed $project
     *
     * @return bool
     */
    public function isEqualTo($project)
    {
        if (is_object($project) && ($project instanceof static)) {
            return $project->getCode() == $this->getCode();
        }

        return false;
    }
}
