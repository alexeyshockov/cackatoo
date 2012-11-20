<?php

namespace Cackatoo\CackatooBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;

use Cackatoo\Model\Deploy;

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
     * @return \Cackatoo\Deployer
     */
    private function notifyAbout(Deploy $deploy)
    {
        // TODO Notify New Relic.
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
    // TODO Тут бы нужен REST, чтобы дёргать из Жени. Чтобы выкладка была реально из одного места. Аутентификацию можно
    // сделать по SSL-сертификатам, чтобы не заморачиваться с OAuth.
    public function deployAction(Request $request, $project)
    {
        $pm = $this->getProjectManager();

        $project = $pm->findBy($project)->orThrow($this->createNotFoundException());

        $templateParameters = ['project' => $project];

        if ($request->isMethod('POST')) {
            // TODO Refactor to form...
            $version = $request->get('deploy')['version'];

            // TODO Validate version (by .deb repository lookup).

            $deploy = $pm->startDeployFor($project, $version);

            $this->getDeployer()->process($deploy);

            $pm->endDeploy($deploy);

            $this->notifyAbout($deploy);

            $templateParameters['deploy'] = $deploy;
        }

        return $templateParameters;
    }
}
