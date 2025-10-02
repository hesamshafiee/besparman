#!/bin/sh
set -e

php artisan queue:work --verbose --tries=3 --timeout=90
