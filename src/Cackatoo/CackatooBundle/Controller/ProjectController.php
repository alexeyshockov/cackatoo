<?php

namespace Cackatoo\CackatooBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;

use Cackatoo\Model\Deploy;

use Cackatoo\Exception\DeployException;

/**
 * @Route("/projects")
 *
 * @author Alexey Shockov <alexey@shockov.com>
 *
 * @todo ParamConverter.
 */
class ProjectController extends Controller
{
    /**
     * @return \Cackatoo\Manager\ProjectManager
     */
    private function getProjectManager()
    {
        return $this->get('cackatoo.project_manager');
    }

    /**
     * @return \Cackatoo\Deployer
     */
    private function getDeployer()
    {
        return $this->get('cackatoo.deployer');
    }

    /**
     * @Route("/", name="project_list")
     * @Template
     *
     * @return array
     */
    public function listAction()
    {
        $projects = $this->getProjectManager()->findAll();

        return [
            'projects' => $projects,
        ];
    }

    /**
     * @Route("/{project}/timelime", name="project_timeline")
     * @Template
     *
     * @return array
     */
    public function timelineAction($project)
    {
        $project = $this->getProjectManager()->findBy($project)->orThrow($this->createNotFoundException());

        return [
            'project'  => $project,
        ];
    }

    /**
     * Выкладываем.
     *
     * @Route("/{project}/deploy", name="project_deploy")
     * @Template
     *
     * @return array
     */
    public function deployAction(Request $request, $project)
    {
        $projectManager = $this->getProjectManager();

        $project = $projectManager->findBy($project)->orThrow($this->createNotFoundException());

        $templateParameters = [
            'project' => $project,
            'errors'  => [],
            'version' => $project->getLatestVersion()
        ];

        if ($request->isMethod('POST')) {
            // TODO Refactor to form...
            $version = $request->get('deploy')['version'];

            $templateParameters['version'] = $version;

            // TODO Validate version (by .deb repository lookup).

            // TODO Don't do anything, if version are the same as current.

            $deploy = $projectManager->startDeployFor($project, $version);

            try {
                $this->getDeployer()->process($deploy);
            } catch (DeployException $exception) {
                $deploy->setError($exception->getMessage());

                $templateParameters['errors'][] = $exception->getMessage();
            }

            $projectManager->endDeploy($deploy);

            if ($deploy->isSuccessful()) {
                // https://github.com/symfony/symfony/blob/master/UPGRADE-2.1.md#session
                $request->getSession()->getFlashBag()->add('messages', 'Successfully deployed.');

                return $this->redirect(
                    $request->headers->get(
                        'Referer',
                        $this->generateUrl('project_deploy', ['project' => $project->getCode()])
                    )
                );
            }

            $templateParameters['deploy'] = $deploy;
        }

        return $templateParameters;
    }
}
