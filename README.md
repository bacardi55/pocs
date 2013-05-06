POCS

Simple backend for JOCS (javascript comment for static website).

written in php with the micro framework silex

# Install

    bash
    git clone
    # Get composer, then install the dependencies.
    php composer.phar install

    # Set up the database configuration in resources/config/prod.php

    # Load the schema:
    php console doctrine:database:create # Only if you didn't create the db yourself
    php console doctrine:schema:load

    # Dump assetics:
    php console assetic:dump


then go to /install to create the first admin.


# Features:

## What the app does:

- Create/remove frontends (and their api key)
- View frontends details (urls and comments)
- Remove comments


## What the app doesn't do (yet or never):

- Create / remove user.
- Nesting comment
- User Permissions by frontend.
- pretty url.
…
