<?php

namespace Cackatoo\Manager;

use Cackatoo\Model\Project;
use Cackatoo\Model\Deploy;

use JMS\DiExtraBundle\Annotation as DI;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;

/**
 * @DI\Service("cackatoo.project_manager")
 *
 * @author Alexy Shockov <alexey@shockov.com>
 */
class ProjectManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $deployRepository;

    /**
     * @var array
     */
    private $projects;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    private $securityContext;

    /**
     * @var \Cackatoo\Manager\RepositoryManager
     */
    private $repositoryManager;

    /**
     * @DI\InjectParams({
     *     "projects"          = @DI\Inject("%projects%"),
     *     "entityManager"     = @DI\Inject("doctrine.orm.entity_manager"),
     *     "securityContext"   = @DI\Inject("security.context"),
     *     "repositoryManager" = @DI\Inject("cackatoo.repository_manager"),
     * })
     */
    public function __construct(
        $projects,
        EntityManager $entityManager,
        SecurityContext $securityContext,
        RepositoryManager $repositoryManager
    )
    {
        $this->projects        = $projects;
        $this->entityManager   = $entityManager;
        $this->securityContext = $securityContext;

        $this->repositoryManager = $repositoryManager;

        $this->deployRepository = $this->entityManager->getRepository('Cackatoo\\Model\\Deploy');
    }

    /**
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    private function getCurrentUser()
    {
        if (is_object($this->securityContext->getToken())) {
            return $this->securityContext->getToken()->getUser();
        } else {
            throw new \RuntimeException('User not found.');
        }
    }

    /**
     * @param string $code
     *
     * @return \Colada\Option
     */
    public function findBy($code)
    {
        if (!isset($this->projects[$code])) {
            return option(null);
        }

        $repository = $this->repositoryManager->findBy($this->projects[$code]['deb']['repository'])->orThrow(
            new \LogicException('Unknown repository.')
        );

        $latestPackage = $repository
            ->acceptBy(x()->getName()->isEqualTo($this->projects[$code]['deb']['package']))
            ->head();

        return option(new Project(
            $code,
            $this->projects[$code],
            $this->getTimelineFor($code),
            $latestPackage
        ));
    }

    /**
     * @return \Colada\Collection
     */
    public function findAll()
    {
        $builder = new \Colada\CollectionBuilder();

        foreach (array_keys($this->projects) as $code) {
            $builder->add($this->findBy($code)->get());
        }

        return $builder->build();
    }

    /**
     * @todo By default, timeline should include only successful deploys.
     *
     * @param string $project
     *
     * @return \Colada\Collection
     */
    private function getTimelineFor($project)
    {
        return to_collection($this->deployRepository->findBy(
            ['project' => $project],
            ['id' => 'DESC']
        ));
    }

    /**
     * @param \Cackatoo\Model\Project $project
     * @param string                  $version
     *
     * @return \Cackatoo\Model\Deploy
     */
    public function startDeployFor(Project $project, $version)
    {
        $deploy = new Deploy($project, $version, $this->getCurrentUser());

        $this->entityManager->persist($deploy);
        $this->entityManager->flush();

        return $deploy;
    }

    public function endDeploy(Deploy $deploy)
    {
        $deploy->setEndedAt(new \DateTime());

        $this->entityManager->persist($deploy);
        $this->entityManager->flush();
    }
}
