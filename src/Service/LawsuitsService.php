<?php

namespace App\Service;

class LawsuitsService
{
    private $rols;

    public function __construct()
    {
        $this->rols = array();

        $this->rols["K"]["points"] = 5;
        $this->rols["K"]["name"] = "King";

        $this->rols["N"]["points"] = 2;
        $this->rols["N"]["name"] = "Notary";

        $this->rols["V"]["points"] = 1;
        $this->rols["V"]["name"] = "Validator";

        $this->rols["#"]["points"] = 0;
        $this->rols["#"]["name"] = "Joker";
    }

    public function getAll($orderBy = null): array
    {

        if ($orderBy == 'points') {
            uasort($this->rols, function ($a, $b) {
                return $a['points'] - $b['points'];
            });
        }

        return $this->rols;
    }
}
