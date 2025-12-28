<?php

return [
    'name' => 'Name',
    'permissions' => 'Permissions',
    'users_count' => 'Users',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',
    'created' => 'Role created successfully.',
    'updated' => 'Role updated successfully.',
    'deleted' => 'Role deleted successfully.',
    'cannot_delete_role_with_users' => 'Cannot delete this role because it is assigned to :count user(s).',
    'validation' => [
        'name_required' => 'The role name is required.',
        'name_unique' => 'This role name already exists.',
        'permissions_array' => 'Permissions must be an array.',
        'permission_exists' => 'One or more permissions do not exist.',
    ],
];
