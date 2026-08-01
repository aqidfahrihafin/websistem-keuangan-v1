<?php

return [
    /*
    | Automatic backups are opt-in so a new local installation does not
    | unexpectedly consume disk. Production should enable this only after
    | off-site storage and restore testing have been configured.
    */
    'automatic_backup_enabled' => (bool) env('AUTOMATIC_BACKUP_ENABLED', false),
    'automatic_backup_time' => env('AUTOMATIC_BACKUP_TIME', '02:00'),
    'automatic_backup_cleanup_time' => env('AUTOMATIC_BACKUP_CLEANUP_TIME', '03:00'),
    'backup_warning_after_hours' => (int) env('BACKUP_WARNING_AFTER_HOURS', 26),
    'backup_critical_after_hours' => (int) env('BACKUP_CRITICAL_AFTER_HOURS', 48),
    'backup_offsite_enabled' => (bool) env('BACKUP_OFFSITE_ENABLED', false),
    'backup_offsite_disk' => env('BACKUP_OFFSITE_DISK', 's3'),
    'backup_offsite_prefix' => env('BACKUP_OFFSITE_PREFIX', 'emall-backups'),
];
