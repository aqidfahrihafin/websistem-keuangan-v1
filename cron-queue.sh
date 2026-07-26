#!/bin/bash
cd /home/u689704230/domains/emal-annuqayah.storytelling.my.id/public_html/emal-annuqayah
/usr/bin/php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
