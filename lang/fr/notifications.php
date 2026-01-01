<?php

declare(strict_types=1);

return [
    'registered' => [
        'greeting' => 'Bienvenue sur :app, :name !',
        'line1' => 'Votre compte vient d\'être créé sur :app.',
        'line2' => 'Avant de pouvoir vous connecter, vous devez créer un mot de passe pour votre compte.',
        'action' => 'Créer mon mot de passe',
        'warning' => 'Remarque : Ne partagez jamais votre mot de passe avec qui que ce soit. L\'équipe informatique n\'a pas besoin de votre mot de passe pour interagir avec votre compte si nécessaire.',
        'subject' => 'Bienvenue sur :app, :name !',
    ],

    // Types de notifications
    'types' => [
        'cleaning_alert' => 'Alerte de nettoyage',
        'item_warning_stock' => 'Stock faible',
    ],

    // Notification d'alerte de nettoyage
    'cleaning_alert' => [
        'title' => 'Nettoyage requis',
        'message' => 'Le matériel ":material" nécessite un nettoyage. Prochain nettoyage prévu le :next_cleaning.',
    ],

    // Notification de stock faible
    'item_warning_stock' => [
        'title' => 'Alerte stock faible',
        'message' => 'L\'article ":item" a un stock faible (:current_stock unités). Le seuil minimum est de :minimum unités.',
    ],

    // Réponses API
    'not_found' => 'Notification introuvable.',
    'marked_as_read' => 'Notification marquée comme lue.',
    'all_marked_as_read' => 'Toutes les notifications ont été marquées comme lues.',
    'deleted' => 'Notification supprimée.',
    'all_deleted' => 'Toutes les notifications ont été supprimées.',
];
