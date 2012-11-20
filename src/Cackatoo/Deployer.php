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
    private $puppetCommand;

    /**
     * @DI\InjectParams({
     *     "projectManager" = @DI\Inject("cackatoo.project_manager"),
     *     "versionFile"    = @DI\Inject("%version_file%"),
     *     "puppetCommand"  = @DI\Inject("%puppet_command%"),
     * })
     */
    public function __construct(ProjectManager $projectManager, $versionFile, $puppetCommand)
    {
        $this->projectManager = $projectManager;
        $this->versionFile    = $versionFile;
        $this->puppetCommand  = $puppetCommand;
    }

    /**
     * @throws \Cackatoo\DeployException
     *
     * @param \Cackatoo\Model\Deploy $deploy
     */
    public function process(Deploy $deploy)
    {
        $project = $this->projectManager->findBy($deploy->getProjectCode())
            ->orThrow(new \RuntimeException('Unknown project.'));

        $this->updateVersionFor($project, $deploy->getVersion());

        $this->kickNodesFor($project);
    }

    private function kickNodesFor(Project $project)
    {
        $nodes = $project->getNodes();

        ob_start();

        passthru($this->puppetCommand.' kick --foreground '.implode(' ', $nodes), $status);

        $output = ob_get_clean();

        if ($status) {
            // TODO More information.
            throw new DeployException('Host update (puppet kick) failed.');
        }
    }

    private function updateVersionFor(Project $project, $version)
    {
        $versions = [];
        if (file_exists($this->versionFile)) {
            $versions = str_getcsv(file_get_contents($this->versionFile));
        }

        $versions[$project->getCode().'_version'] = $version;

        $file = @fopen($this->versionFile, 'w');

        if (false === $file) {
            throw new DeployException('Unable to write version (file: '.$this->versionFile.').');
        }

        $result = @fputcsv($file, $versions);

        if (false === $result) {
            throw new DeployException('Unable to write version (file: '.$this->versionFile.').');
        }
    }
}
