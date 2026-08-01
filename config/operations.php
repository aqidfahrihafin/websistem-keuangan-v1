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
];
