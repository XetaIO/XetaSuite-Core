<?php

declare(strict_types=1);

return [
    'registered' => [
        'greeting' => 'Welcome to :app, :name!',
        'line1' => 'Your account has just been created on :app.',
        'line2' => 'Before you can log in, you must create a password for your account.',
        'action' => 'Create my password',
        'warning' => 'Note: Never share your password with anyone. The IT team does not need your password to interact with your account if they need to.',
        'subject' => 'Welcome to :app, :name!',
    ],

    // Notification types
    'types' => [
        'cleaning_alert' => 'Cleaning Alert',
        'item_warning_stock' => 'Low Stock Warning',
    ],

    // Cleaning alert notification
    'cleaning_alert' => [
        'title' => 'Cleaning Required',
        'message' => 'Material ":material" needs cleaning. Next cleaning scheduled for :next_cleaning.',
    ],

    // Item warning stock notification
    'item_warning_stock' => [
        'title' => 'Low Stock Alert',
        'message' => 'Item ":item" has low stock (:current_stock units). Minimum threshold is :minimum units.',
    ],

    // API responses
    'not_found' => 'Notification not found.',
    'marked_as_read' => 'Notification marked as read.',
    'all_marked_as_read' => 'All notifications marked as read.',
    'deleted' => 'Notification deleted.',
    'all_deleted' => 'All notifications deleted.',
];
