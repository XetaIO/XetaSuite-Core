<?php

declare(strict_types=1);

namespace XetaSuite\Enums\Notifications;

/**
 * Enum representing the different types of notifications in the system.
 * Used to categorize notifications and determine their display properties.
 */
enum NotificationType: string
{
    case CleaningAlert = 'cleaning_alert';
    case ItemWarningStock = 'item_warning_stock';

    /**
     * Get the human-readable label for this notification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CleaningAlert => __('notifications.types.cleaning_alert'),
            self::ItemWarningStock => __('notifications.types.item_warning_stock'),
        };
    }

    /**
     * Get the icon identifier for this notification type.
     * Used by the frontend to display appropriate icons.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CleaningAlert => 'broom',
            self::ItemWarningStock => 'cubes',
        };
    }

    /**
     * Get the color scheme for this notification type.
     */
    public function color(): string
    {
        return match ($this) {
            self::CleaningAlert => 'warning',
            self::ItemWarningStock => 'error',
        };
    }
}
