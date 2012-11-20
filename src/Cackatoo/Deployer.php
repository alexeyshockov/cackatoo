<?php

namespace Cackatoo;

use JMS\DiExtraBundle\Annotation as DI;

use Cackatoo\Model\Deploy;
use Cackatoo\Model\Project;
use Cackatoo\Manager\ProjectManager;

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
     * @DI\InjectParams({
     *     "projectManager" = @DI\Inject("cackatoo.project_manager"),
     *     "versionFile"    = @DI\Inject("%version_file%"),
     * })
     */
    public function __construct(ProjectManager $projectManager, $versionFile)
    {
        $this->projectManager = $projectManager;
        $this->versionFile    = $versionFile;
    }

    public function process(Deploy $deploy)
    {
        $project = $this->projectManager->findBy($deploy->getProjectCode())
            ->orThrow(new \RuntimeException('Unknown project.'));

        $this->updateVersionFor($project, $deploy->getVersion());

        list($status, $output) = $this->kickNodesFor($project);

        if (!$status) {
            throw new \RuntimeException('Deploy error.');
        }
    }

    /**
     * @param \Cackatoo\Model\Project $project
     *
     * @return array Suitable for list($status, $output).
     */
    private function kickNodesFor(Project $project)
    {
        $nodes = $project->getNodes();

        $output = system('/usr/bin/puppet kick --foreground '.implode(' ', $nodes), $status);

        return [$status, $output];
    }

    // TODO Handle errors.
    private function updateVersionFor(Project $project, $version)
    {
        $versions = [];
        if (file_exists($this->versionFile)) {
            $versions = str_getcsv(file_get_contents($this->versionFile));
        }

        $versions[$project->getCode().'_version'] = $version;

        $file = @fopen($this->versionFile, 'w');

        if (false === $file) {
            throw new \RuntimeException('Unable to write version.');
        }

        $result = @fputcsv($file, $versions);

        if (false === $result) {
            throw new \RuntimeException('Unable to write version.');
        }
    }
}
