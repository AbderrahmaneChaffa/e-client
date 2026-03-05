<?php

namespace App\Helpers;

use NumberToWords\NumberToWords;

class NumberHelper
{
    public static function enLettres($montant)
    {
        $numberToWords = new NumberToWords();
        $transformer = $numberToWords->getNumberTransformer('fr');

        $entier = floor($montant);
        $decimales = round(($montant - $entier) * 100);

        $resultat = ucfirst($transformer->toWords($entier)) . ' Dinars';

        if ($decimales > 0) {
            $resultat .= ' et ' . $transformer->toWords($decimales) . ' Centimes';
        }

        return $resultat;
    }
}
