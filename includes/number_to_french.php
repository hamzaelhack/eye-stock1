<?php
function numberToFrench($number) {
    $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
    $tens = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
    $teens = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    
    if ($number < 0 || !is_numeric($number)) {
        return false;
    }
    
    // Séparer la partie entière et décimale
    $parts = explode('.', number_format($number, 2, '.', ''));
    $integer = intval($parts[0]);
    $decimal = intval($parts[1]);
    
    // Convertir la partie entière
    if ($integer == 0) {
        $string = 'zéro';
    } else {
        $string = '';
        
        // Milliards
        if ($integer >= 1000000000) {
            $billions = floor($integer / 1000000000);
            $string .= ($billions > 1 ? numberToFrench($billions) . ' ' : '') . 'milliard' . ($billions > 1 ? 's' : '') . ' ';
            $integer = $integer % 1000000000;
        }
        
        // Millions
        if ($integer >= 1000000) {
            $millions = floor($integer / 1000000);
            $string .= ($millions > 1 ? numberToFrench($millions) . ' ' : '') . 'million' . ($millions > 1 ? 's' : '') . ' ';
            $integer = $integer % 1000000;
        }
        
        // Milliers
        if ($integer >= 1000) {
            $thousands = floor($integer / 1000);
            if ($thousands == 1) {
                $string .= 'mille ';
            } else {
                $string .= numberToFrench($thousands) . ' mille ';
            }
            $integer = $integer % 1000;
        }
        
        // Centaines
        if ($integer >= 100) {
            $hundreds = floor($integer / 100);
            if ($hundreds == 1) {
                $string .= 'cent ';
            } else {
                $string .= $units[$hundreds] . ' cent' . ($hundreds > 1 && $integer % 100 == 0 ? 's' : '') . ' ';
            }
            $integer = $integer % 100;
        }
        
        // Dizaines et unités
        if ($integer >= 10) {
            if ($integer < 20) {
                $string .= $teens[$integer - 10];
            } else {
                $ten = floor($integer / 10);
                $unit = $integer % 10;
                if ($ten == 7 || $ten == 9) {
                    $string .= $tens[$ten-1] . '-' . $teens[$unit];
                } else {
                    $string .= $tens[$ten];
                    if ($unit > 0) {
                        $string .= '-' . $units[$unit];
                    }
                }
            }
        } elseif ($integer > 0) {
            $string .= $units[$integer];
        }
    }
    
    // Ajouter les centimes
    if ($decimal > 0) {
        $string .= ' virgule ';
        if ($decimal < 20) {
            $string .= $teens[$decimal];
        } else {
            $ten = floor($decimal / 10);
            $unit = $decimal % 10;
            $string .= $tens[$ten];
            if ($unit > 0) {
                $string .= '-' . $units[$unit];
            }
        }
    }
    
    return trim($string);
}
?>
