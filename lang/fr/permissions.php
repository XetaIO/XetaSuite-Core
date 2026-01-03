<?php

return [
    'name' => 'Nom',
    'roles' => 'Rôles',
    'roles_count' => 'Rôles',
    'created_at' => 'Créé le',
    'updated_at' => 'Modifié le',
    'messages' => [
        'created' => 'Permission créée avec succès.',
        'updated' => 'Permission mise à jour avec succès.',
        'deleted' => 'Permission ":name" supprimée avec succès.',
    ],
    'errors' => [
        'hasRoles' => 'Impossible de supprimer cette permission car elle est assignée à un ou plusieurs rôles.',
    ],
    'validation' => [
        'name_required' => 'Le nom de la permission est requis.',
        'name_unique' => 'Ce nom de permission existe déjà.',
    ],
];
