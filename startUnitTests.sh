#!/bin/bash
#php8.1 ./bin/phpunit -c tests/phpunit.xml --bootstrap ./vendor/autoload.php  tests/ 2>&1
php8.1 ./bin/phpunit -c tests/phpunit.xml --bootstrap ./tests/phpunit.bootstrap.php  tests/ 2>&1
