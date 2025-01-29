<?php


namespace App; 
Use Illuminate\Support\Str;

class Helpers
{


    public static function generateRandomPassword(){
        $length= 10;

        $password= Str::random($length);

        // Ajoute au moins 10 chiffres au mot de passe 
        $digits = Str::random(10);
        $password .= preg_replace("/[^0-9]/", "", $digits);

        // Ajoute une majuscule au mot de passe
        $password .= Str::random(1, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

        // Ajoute un caractère spécial au mot de passe
        $specialCharacters = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password .= Str::random(1, $specialCharacters);

        // Mélange les caractères pour plus de sécurité
        $password = str_shuffle($password);
        return $password;

    }
}