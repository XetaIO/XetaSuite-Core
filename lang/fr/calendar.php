<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Calendar Translations (French)
    |--------------------------------------------------------------------------
    */
    'title' => 'Calendrier',
    'today' => 'Aujourd\'hui',
    'todayEvents' => 'Événements du jour',
    'noEventsToday' => 'Aucun événement prévu aujourd\'hui',

    /*
    |--------------------------------------------------------------------------
    | Event Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'title' => 'Catégories d\'événements',
        'singular' => 'Catégorie',
        'plural' => 'Catégories',
        'name' => 'Nom',
        'color' => 'Couleur',
        'description' => 'Description',
        'isDefault' => 'Catégorie par défaut',
        'eventsCount' => 'Événements',
        'create' => 'Créer une catégorie',
        'edit' => 'Modifier la catégorie',
        'delete' => 'Supprimer la catégorie',
        'deleteConfirm' => 'Êtes-vous sûr de vouloir supprimer cette catégorie ? Les événements liés à cette catégorie resteront, mais sans catégorie.',
        'created' => 'Catégorie créée avec succès',
        'updated' => 'Catégorie mise à jour avec succès',
        'deleted' => 'Catégorie supprimée avec succès',
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar Events
    |--------------------------------------------------------------------------
    */
    'events' => [
        'title' => 'Événements',
        'singular' => 'Événement',
        'plural' => 'Événements',
        'eventTitle' => 'Titre',
        'description' => 'Description',
        'category' => 'Catégorie',
        'color' => 'Couleur',
        'startAt' => 'Date de début',
        'endAt' => 'Date de fin',
        'allDay' => 'Journée entière',
        'createdBy' => 'Créé par',
        'create' => 'Créer un événement',
        'edit' => 'Modifier l\'événement',
        'delete' => 'Supprimer l\'événement',
        'deleteConfirm' => 'Êtes-vous sûr de vouloir supprimer cet événement ?',
        'created' => 'Événement créé avec succès',
        'updated' => 'Événement mis à jour avec succès',
        'deleted' => 'Événement supprimé avec succès',
        'dateUpdated' => 'Dates de l\'événement mises à jour avec succès',
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'showMaintenances' => 'Afficher les maintenances',
        'showIncidents' => 'Afficher les incidents',
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar Views
    |--------------------------------------------------------------------------
    */
    'views' => [
        'month' => 'Mois',
        'week' => 'Semaine',
        'day' => 'Jour',
        'list' => 'Liste',
    ],
];
