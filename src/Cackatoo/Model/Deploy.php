<?php

namespace Cackatoo\Model;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\Mapping as ORM;

use Clock\DateTime;

/**
 * @ORM\Entity
 *
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Deploy
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $project;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $endedAt;

    /**
     * User name.
     *
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var array
     */
    private $error;

    /**
     * @ORM\OneToOne(targetEntity="\Cackatoo\Model\Deploy")
     *
     * @var \Cackatoo\Model\Deploy;
     */
    private $previous;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $version;

    /**
     * @param \Cackatoo\Model\Project                             $project
     * @param string                                              $version
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     */
    function __construct(Project $project, $version, UserInterface $user)
    {
        $this->project = $project->getCode();
        $this->version = $version;
        $this->user    = $user->getUsername();

        $this->startedAt = new \DateTime();
    }

    /**
     * @return \Colada\Option
     */
    public function getEndedAt()
    {
        $option = option(null);
        if ($this->endedAt) {
            $option = option(new DateTime($this->endedAt));
        }

        return $option;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->getEndedAt()->orElse(new DateTime())->diff($this->startedAt)->toSeconds();
    }

    /**
     * @return null|string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getProjectCode()
    {
        return $this->project;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return new DateTime($this->startedAt);
    }

    public function getUserName()
    {
        return $this->user;
    }

    /**
     * @internal
     *
     * @param \DateTime $endedAt
     */
    public function setEndedAt(\DateTime $endedAt)
    {
        $this->endedAt = $endedAt;
    }

    /**
     * @internal
     *
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return !is_null($this->error);
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return is_null($this->error);
    }

    /**
     * Previous deploy.
     *
     * @return \Colada\Option
     */
    public function getPrevious()
    {
        return option($this->previous);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
