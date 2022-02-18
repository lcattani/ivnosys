<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\RolsService;

class LawsuitsController extends AbstractController
{

    private array $plaintiff;
    private array $defendant;
    private RolsService $rolsService;

    public function __construct(RolsService $lawsuitsService)
    {

        $this->rolsService = $lawsuitsService;

    }

    /**
     * @Route("/lawsuits", name="lawsuits")
     */
    public function index(Request $request): Response
    {

        $plaintiff = $request->query->get('plaintiff');
        $defendant = $request->query->get('defendant');

        $this->process($plaintiff, $defendant);

        return $this->render('lawsuits/index.html.twig', [
            'controller_name' => 'LawsuitsController',
            'result' => ['plaintiff' => $this->plaintiff, 'defendant' => $this->defendant],
        ]);
    }

    public function getPlainResult(string $plaintiff, string $defendant): string
    {

        $this->process($plaintiff, $defendant);

        // Renderizo en una variable
        $loader = new \Twig\Loader\FilesystemLoader('templates/');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load('lawsuits/index.html.twig');

        $result =  $template->render([
            'controller_name' => 'LawsuitsController',
            'result' => ['plaintiff' => $this->plaintiff, 'defendant' => $this->defendant],
        ]);

        // Obtengo los tagas que me interesan
        preg_match_all("/<b class='title'>(.*?)<\/b>/s", $result, $match_title);
        $match_title = $this->removeNonAlphanumeric($match_title[1]);

        preg_match_all("/<li class='result'>(.*?)<\/li>/s", $result, $match_result);
        $match_result = $this->removeNonAlphanumeric($match_result[1]);

        preg_match_all("/<li class='joker'>(.*?)<\/li>/s", $result, $match_joker);
        $match_joker = $this->removeNonAlphanumeric($match_joker[1]);

        // Formo respuesta en modo texto
        $result = "";
        $result .= "{$match_title[0]}\n";
        $result .= "  - {$match_result[0]}\n";
        $result .= "  - {$match_result[1]}\n\n";
        if (isset($match_joker[0]) || isset($match_joker[1])) {
            $result .= "{$match_title[1]}\n";
            if (isset($match_joker[0])) {
                $result .= "  - {$match_joker[0]}\n";
            } 
            if (isset($match_joker[1])) {
                $result .= "  - {$match_joker[1]}";
            }
        }

        return $result;
    }

    private function removeNonAlphanumeric(array $data): array
    {

        return $data;
        foreach ($data as &$value) {
            $value = preg_replace("/[^A-Za-z0-9# ]/", '', $value);
        }

        return $data;
        
    }

    private function process(string $plaintiff, string $defendant)
    {

        $this->plaintiff = $this->parseSignatures($plaintiff);
        $this->defendant = $this->parseSignatures($defendant);

        $this->setWinner($this->plaintiff, $this->defendant);

        $this->plaintiff = $this->analyzeJoker($this->plaintiff, $this->defendant["points"] - $this->plaintiff["points"]);
        $this->defendant = $this->analyzeJoker($this->defendant, $this->plaintiff["points"] - $this->defendant["points"]);
    }

    private function parseSignatures(string $signatures): array
    {

        $signatures_array = str_split($signatures);
        $rols = $this->rolsService->getAll();
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
                if ($signature == "#") {
                    $jokers++;
                } else if (!($signature == "V" && $king == TRUE)) {
                    $result["points"] += $rols[$signature]["points"];
                }
            }
        }

        // Solo un Joker es permitido
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
        $rols = $this->rolsService->getAll('points');

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
