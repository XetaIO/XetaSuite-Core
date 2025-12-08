<?php

return [
    // Attributes
    'material' => 'Material',
    'maintenance' => 'Maintenance',
    'description' => 'Description',
    'severity' => 'Severity',
    'status' => 'Status',
    'started_at' => 'Started at',
    'resolved_at' => 'Resolved at',
    'reported_by' => 'Reported by',

    // Severity labels
    'severity_low' => 'Low',
    'severity_medium' => 'Medium',
    'severity_high' => 'High',
    'severity_critical' => 'Critical',

    // Status labels
    'status_open' => 'Open',
    'status_in_progress' => 'In Progress',
    'status_resolved' => 'Resolved',
    'status_closed' => 'Closed',

    // Validation
    'validation' => [
        'material_not_on_site' => 'The selected material does not belong to your current site.',
        'maintenance_not_on_site' => 'The selected maintenance does not belong to your current site.',
        'maintenance_not_for_material' => 'The selected maintenance is not linked to the chosen material.',
    ],

    // Messages
    'created' => 'Incident created successfully.',
    'updated' => 'Incident updated successfully.',
    'deleted' => 'Incident deleted successfully.',
];
