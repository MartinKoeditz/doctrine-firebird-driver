#!/bin/bash
./bin/phpunit -c tests/phpunit.xml --bootstrap ./vendor/autoload.php  tests/ 2>&1
