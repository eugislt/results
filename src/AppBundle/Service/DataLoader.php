<?php
// src/AppBundle/Service/DataLoader.php

namespace AppBundle\Service;

use AppBundle\Entity\Team;

class DataLoader
{

    public function __construct($resultsUrl, $teamUrl)
    {
        $this->resultsUrl = $resultsUrl;
        $this->teamUrl = $teamUrl;

        $this->results = [];
        $this->teams = [];
    }
    /**
     * Store results data
     * @var array
     */
    private $results;

    /**
     * Store teams data
     * @var array
     */
    private $teams;

    /**
     * Results service url
     *
     * @var string
     */
    private $resultsUrl;

    /**
     * Team service url
     *
     * @var string
     */
    private $teamUrl;

    /**
     * Load team data from webservice by Id
     *
     * @param int $id Team id
     * @return Team
     */
    public function loadTeam($id)
    {

        if (!empty($this->teams[$id])) {
            return $this->teams[$id];
        }

        $fileContent = false;
        $tryLimit = 3;
        $i = 0;

        //Server is unstable so try few times to get data.
        while (!$fileContent) {
            $fileContent = @file_get_contents($this->teamUrl."?id=$id");

            if (++$i > $tryLimit) {
                return false;
            }

            if (!$fileContent) {
                continue;
            }

            $teamData = json_decode($fileContent);

            $team = new Team($teamData->id, $teamData->name);

            //Store team data
            $this->teams[$id] = $team;

            return $team;
        }
    }

    /**
     * Load match results from webservice
     * @return array
     */
    public function loadMatches()
    {

        if (!empty($this->results)) {
            return $this->results;
        }

        $fileContent = @file_get_contents($this->resultsUrl);

        if (!$fileContent) {
            return [];
        }

        $matchData = json_decode($fileContent);

        $result = [];
        foreach ($matchData as $row) {

            $teamA = $this->loadTeam($row->teamAId);
            $teamB = $this->loadTeam($row->teamBId);

            $row->teamAName = $teamA ? $teamA->getName() : 'Error';
            $row->teamBName = $teamB ? $teamB->getName() : 'Error';

            if (isset($row->scoreA) && isset($row->scoreB)) {
                $row->score = $row->scoreA.':'.$row->scoreB;
            } else {
                $row->score = 'No score';
            }

            $result[] = $row;
        }

        //Store results
        $this->results = $result;

        return $result;
    }

    /**
     * Convert results array to team standings list
     * @param array $results
     * @return array
     */
    public function getStandings($results = false)
    {

        if ($results === false) {
            $results = $this->loadMatches();
        }

        $standings = [];
        foreach ($results as $row) {

            if (!isset($row->scoreA) || !isset($row->scoreB)) {
                continue;
            }

            if ($row->scoreA > $row->scoreB) {
                $standings[$row->teamAId] = isset($standings[$row->teamAId]) ? ++$standings[$row->teamAId]
                        : 1;
                $standings[$row->teamBId] = isset($standings[$row->teamBId]) ? $standings[$row->teamBId]
                        : 0;
            } else if ($row->scoreA < $row->scoreB) {
                $standings[$row->teamBId] = isset($standings[$row->teamBId]) ? ++$standings[$row->teamBId]
                        : 1;
                $standings[$row->teamAId] = isset($standings[$row->teamAId]) ? $standings[$row->teamAId]
                        : 0;
            }
        }

        arsort($standings);

        return array_map(function($wins, $id) {
            $team = $this->loadTeam($id);
            return [
                'name' => $team->getName(),
                'wins' => $wins
            ];
        }, $standings, array_keys($standings));
    }
}