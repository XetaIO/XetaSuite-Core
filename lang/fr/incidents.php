<?php

return [
    // Attributes
    'material' => 'Matériel',
    'maintenance' => 'Maintenance',
    'description' => 'Description',
    'severity' => 'Sévérité',
    'status' => 'Statut',
    'started_at' => 'Débuté le',
    'resolved_at' => 'Résolu le',
    'reported_by' => 'Signalé par',

    // Severity labels
    'severity_low' => 'Faible',
    'severity_medium' => 'Moyen',
    'severity_high' => 'Élevé',
    'severity_critical' => 'Critique',

    // Status labels
    'status_open' => 'Ouvert',
    'status_in_progress' => 'En cours',
    'status_resolved' => 'Résolu',
    'status_closed' => 'Fermé',

    // Validation
    'validation' => [
        'material_not_on_site' => 'Le matériel sélectionné n\'appartient pas à votre site actuel.',
        'maintenance_not_on_site' => 'La maintenance sélectionnée n\'appartient pas à votre site actuel.',
        'maintenance_not_for_material' => 'La maintenance sélectionnée n\'est pas liée au matériel choisi.',
    ],

    // Messages
    'created' => 'Incident créé avec succès.',
    'updated' => 'Incident mis à jour avec succès.',
    'deleted' => 'Incident supprimé avec succès.',
];
