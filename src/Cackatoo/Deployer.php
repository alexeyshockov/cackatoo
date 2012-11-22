<?php

namespace Cackatoo;

use JMS\DiExtraBundle\Annotation as DI;

use Cackatoo\Model\Deploy;
use Cackatoo\Model\Project;
use Cackatoo\Manager\ProjectManager;

use Cackatoo\Exception\DeployException;

/**
 * For Puppet deploy at this time.
 *
 * @DI\Service("cackatoo.deployer")
 *
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Deployer
{
    /**
     * @var \Cackatoo\Manager\ProjectManager
     */
    private $projectManager;

    /**
     * @var string
     */
    private $versionFile;

    /**
     * @var string
     */
    private $syncCommand;

    /**
     * @DI\InjectParams({
     *     "projectManager" = @DI\Inject("cackatoo.project_manager"),
     *     "versionFile"    = @DI\Inject("%version_file%"),
     *     "syncCommand"    = @DI\Inject("%sync_command%"),
     * })
     */
    public function __construct(ProjectManager $projectManager, $versionFile, $syncCommand)
    {
        $this->projectManager = $projectManager;
        $this->versionFile    = $versionFile;
        $this->syncCommand    = $syncCommand;
    }

    /**
     * @throws \Cackatoo\Exception\DeployException
     *
     * @param \Cackatoo\Model\Deploy $deploy
     */
    public function process(Deploy $deploy)
    {
        $project = $this->projectManager->findBy($deploy->getProjectCode())
            ->orThrow(new \RuntimeException('Unknown project.'));

        $this->updateVersionFor($project, $deploy->getVersion());

        $this->syncNodesFor($project);
    }

    /**
     * @throws \Cackatoo\Exception\DeployException
     */
    public function syncAllNodes()
    {
        // TODO Refactor. Get all nodes and process them at once.
        $this->projectManager->findAll()->eachBy([$this, 'syncNodesFor']);
    }

    /**
     * @throws \Cackatoo\Exception\DeployException
     *
     * @param \Cackatoo\Model\Project $project
     */
    public function syncNodesFor(Project $project)
    {
        $nodes = $project->getNodes();

        ob_start();

        passthru($this->syncCommand.' '.implode(' ', $nodes), $status);

        $output = ob_get_clean();

        if ($status) {
            // TODO More information.
            throw new DeployException('Node update failed.');
        }
    }

    /**
     * @throws Exception\DeployException
     *
     * @param Model\Project $project
     * @param string        $version
     */
    private function updateVersionFor(Project $project, $version)
    {
        if (!is_scalar($version)) {
            throw new \InvalidArgumentException('Version must be a string.');
        }

        $rows = [];
        if (file_exists($this->versionFile)) {
            $rows = array_map('str_getcsv', file($this->versionFile));
        }

        // TODO Replace with Colada.
        $found = false;
        foreach ($rows as $index => $row) {
            if ($project->getCode().'_version' == $row[0]) {
                $rows[$index] = [
                    $row[0],
                    $version,
                ];

                $found = true;

                break;
            }
        }

        // New entry.
        if (!$found) {
            $rows[] = [$project->getCode().'_version', $version];
        }

        $file = @fopen($this->versionFile, 'w');

        if (false === $file) {
            throw new DeployException('Unable to write version (file: '.$this->versionFile.').');
        }

        foreach ($rows as $row) {
            $result = @fputcsv($file, $row);

            if (false === $result) {
                throw new DeployException('Unable to write version (file: '.$this->versionFile.').');
            }
        }
    }
}
