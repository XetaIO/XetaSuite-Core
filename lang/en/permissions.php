<?php

return [
    'name' => 'Name',
    'roles' => 'Roles',
    'roles_count' => 'Roles',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',
    'messages' => [
        'created' => 'Permission created successfully.',
        'updated' => 'Permission updated successfully.',
        'deleted' => 'Permission ":name" deleted successfully.',
    ],
    'errors' => [
        'hasRoles' => 'Cannot delete this permission because it is assigned to one or more roles.',
    ],
    'validation' => [
        'name_required' => 'The permission name is required.',
        'name_unique' => 'This permission name already exists.',
    ],
];
