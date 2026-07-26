#!/bin/bash
cd /home/u689704230/domains/emal-annuqayah.storytelling.my.id/public_html/emal-annuqayah
/usr/bin/php artisan schedule:run >> /dev/null 2>&1
