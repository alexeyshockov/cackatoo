<?php

namespace Cackatoo\CackatooBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class DashboardController extends Controller
{
    /**
     * @Route("/", name="dashboard_main")
     * @Template
     */
    public function mainAction()
    {
        return [
            'links' => $this->container->getParameter('links'),
        ];
    }
}
