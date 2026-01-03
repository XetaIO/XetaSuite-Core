<?php

return [
    'name' => 'Nom',
    'permissions' => 'Permissions',
    'users_count' => 'Utilisateurs',
    'created_at' => 'Créé le',
    'updated_at' => 'Modifié le',
    'created' => 'Rôle créé avec succès.',
    'updated' => 'Rôle mis à jour avec succès.',
    'deleted' => 'Rôle supprimé avec succès.',
    'cannot_delete_role_with_users' => 'Impossible de supprimer ce rôle car il est assigné à :count utilisateur(s).',
    'validation' => [
        'name_required' => 'Le nom du rôle est requis.',
        'name_unique' => 'Ce nom de rôle existe déjà.',
        'permissions_array' => 'Les permissions doivent être un tableau.',
        'permission_exists' => 'Une ou plusieurs permissions n\'existent pas.',
    ],
];
