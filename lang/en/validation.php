<?php

declare(strict_types=1);

return [
    'recaptcha' => [
        'required' => 'The reCAPTCHA verification is required.',
        'failed' => 'The reCAPTCHA verification failed. Please try again.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Validation Rules
    |--------------------------------------------------------------------------
    */
    'password' => [
        'letters' => 'The :attribute must contain at least one letter.',
        'mixed' => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute must contain at least one number.',
        'symbols' => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Validation Rules
    |--------------------------------------------------------------------------
    */
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => 'The current password is incorrect.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'required' => 'The :attribute field is required.',
];
