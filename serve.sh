#!/bin/sh
cd "$(dirname "$0")" || exit 1
echo "HarusiLink inaendeshwa: http://localhost:8000"
echo "Hakikisha .env ina: APP_URL=http://localhost:8000"
php -S localhost:8000 -t public public/router.php
