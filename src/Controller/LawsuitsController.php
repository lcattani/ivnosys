<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class LawsuitsController extends AbstractController
{
    /**
     * @Route("/lawsuits", name="lawsuits")
     */
    public function index(Request $request): Response
    {
        $plaintiff = $request->query->get('plaintiff');
        $defendant = $request->query->get('defendant');

        $plaintiff = $this->parseSignatures($plaintiff);
        $defendant = $this->parseSignatures($defendant);

        $winner = $plaintiff["points"] > $defendant["points"] ? "plaintiff" : "defendant";
        return $this->render('lawsuits/index.html.twig', [
            'controller_name' => 'LawsuitsController',
            'result' => ['plaintiff' => $plaintiff, 'defendant' => $defendant, "winner" => $winner],
        ]);
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
        $result["signatures"]  = $signatures;
        $result["error"]  = 0;
        $result["points"] = 0;

        $king = strpos($signatures, "K");
        $joker = strpos($signatures, "#");

        foreach ($signatures_array as $signature) {
            if (!isset($rols[$signature])) {
                $result["error"] = 1;
                break;
            } else {
                if (!($signature == "V" && $king !== FALSE)) {
                    $result["points"] += $rols[$signature]["points"];
                }
            }
        }

        return $result;
    }
}
