<?php
// src/BasketballBundle/Controller/ResultsControler.php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ResultsController extends Controller
{

    public function resultsAction()
    {
        $dataLoader = $this->container->get('app.data_loader');
        $results = $dataLoader->loadMatches();

        return $this->render('match-results.html.twig',
                array(
                'results' => $results,
                'standings' => $dataLoader->getStandings()
        ));
    }
}