<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * Manage access to homepage.
 */
class DefaultController extends AbstractController
{
    /**
     * Show homepage.
     *
     * @return Response
     *
     * @Route("/", name="homepage", methods={"GET"})
     */
    public function indexAction(): Response
    {
        return $this->render('default/index.html.twig');
    }
}
