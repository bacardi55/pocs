POCS

Simple backend for JOCS (javascript comment for static website).

written in php with the framework silex


#To install
git clone

Set up the database configuration in resources/config/prod.php

Load the schema:
php console doctrine:database:create # Only if you didn't create the db yourself
php console doctrine:schema:load

then go to /install
