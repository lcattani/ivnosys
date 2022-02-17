<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class LawsuitsController extends AbstractController
{

    private $plaintiff; 
    private $defendant;


    /**
     * @Route("/lawsuits", name="lawsuits")
     */
    public function index(Request $request): Response
    {

        $this->plaintiff = $request->query->get('plaintiff');
        $this->defendant = $request->query->get('defendant');

        $this->process();

        return $this->render('lawsuits/index.html.twig', [
            'controller_name' => 'LawsuitsController',
            'result' => ['plaintiff' => $this->plaintiff, 'defendant' => $this->defendant],
        ]);


    }

    private function process()
    {

        $this->plaintiff = $this->parseSignatures($this->plaintiff);
        $this->defendant = $this->parseSignatures($this->defendant);

        $this->setWinner($this->plaintiff, $this->defendant);

        $this->plaintiff = $this->analyzeJoker($this->plaintiff, $this->defendant["points"] - $this->plaintiff["points"]);
        $this->defendant = $this->analyzeJoker($this->defendant, $this->plaintiff["points"] - $this->defendant["points"]);

    }

    private function getRols(): array
    {

        $retval = array();

        $retval["K"]["points"] = 5;
        $retval["K"]["name"] = "King";

        $retval["N"]["points"] = 2;
        $retval["N"]["name"] = "Notary";

        $retval["V"]["points"] = 1;
        $retval["V"]["name"] = "Validator";

        $retval["#"]["points"] = 0;
        $retval["#"]["name"] = "Joker";

        return $retval;
    }

    private function parseSignatures(string $signatures): array
    {

        $signatures_array = str_split($signatures);
        $rols = $this->getRols();
        $jokers = 0;

        $king = strpos($signatures, "K") !== false ? true : false;
        $joker = strpos($signatures, "#") !== false ? true : false;

        $result["signatures"]  = $signatures;
        $result["error"]  = 0;
        $result["points"] = 0;
        $result["joker"] = $joker;
        $result["king"] = $king;

        foreach ($signatures_array as $signature) {
            if (!isset($rols[$signature])) {
                $result["error"] = 1;
                $result["points"] = 0;
                break;
            } else {
                if($signature == "#"){
                    $jokers++;
                }                
                else if (!($signature == "V" && $king == TRUE)) {
                    $result["points"] += $rols[$signature]["points"];
                }
            }
        }

        // Solo una Joker es permitido
        if ($jokers > 1) {
            $result["error"] = 1;
            $result["points"] = 0;
        }

        return $result;
    }

    private function setWinner()
    {

        $this->plaintiff['winner'] = $this->plaintiff['points'] - $this->defendant['points'];
        $this->defendant['winner'] = $this->defendant['points'] - $this->plaintiff['points'];
        
    }

    private function analyzeJoker(array $parsedSignatures, int $minimumToWin): array
    {

        // No existe Joker para analizar o ya es el ganador
        if ($parsedSignatures['joker'] == false /*|| $minimumToWin < 0*/) {
            $parsedSignatures['joker_analysis']['found'] = false;
            return $parsedSignatures;
        }

        $to_win = array();

        // Roles ordenados por puntos
        $rols = $this->getRols();
        uasort($rols, function ($a, $b) {
            return $a['points'] - $b['points'];
        });

        // Ya es un ganador, analizo maximo alcanzable
        if ($minimumToWin < 0) {
            $key = array_key_last($rols);
            $to_win = $rols[$key];
        } else { // Analizo firma minima para ganar
            $to_win = array();

            // Al existir un King el Validator no cuenta
            if ($parsedSignatures['king'])
                unset($rols['V']);

            foreach ($rols as $key => $rol) {
                if ($rol['points'] > $minimumToWin) {
                    $to_win = $rol;
                    break;
                }
            }
        }

        // Valor de Joker encontrado
        if (count($to_win) > 0) {
            $to_win['new_signature'] = str_replace("#", $key, $parsedSignatures["signatures"]);
            $to_win['found'] = true;
            $new_signature = $this->parseSignatures($to_win['new_signature']);
            $to_win['new_points'] = $new_signature['points'];

            $parsedSignatures['joker_analysis'] = $to_win;
        } else {
            $parsedSignatures['joker_analysis']['found'] = false;
        }

        return $parsedSignatures;
    }
}
