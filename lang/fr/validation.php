<?php

declare(strict_types=1);

return [
    'recaptcha' => [
        'required' => 'La vérification reCAPTCHA est requise.',
        'failed' => 'La vérification reCAPTCHA a échoué. Veuillez réessayer.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Validation Rules
    |--------------------------------------------------------------------------
    */
    'password' => [
        'letters' => 'Le :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le :attribute doit contenir au moins une lettre majuscule et une lettre minuscule.',
        'numbers' => 'Le :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le :attribute doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Le :attribute fourni a été compromis dans une fuite de données. Veuillez choisir un autre :attribute.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Validation Rules
    |--------------------------------------------------------------------------
    */
    'confirmed' => 'La confirmation du :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe actuel est incorrect.',
    'min' => [
        'string' => 'Le :attribute doit contenir au moins :min caractères.',
    ],
    'required' => 'Le champ :attribute est obligatoire.',
];
