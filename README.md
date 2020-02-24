# reserve_fly

Plane reservation for flight club including a mobile interface, pilote/plane logbook, ... Written in PHP and using MySQL (MariaDB) back-end.

The interface language is in French.

The authentication part is currently only supporting Joomla.

## dbi.php

You first need to localize `dbi-dist.php` with your own parameters then rename it as `dbi.php`.

## create_tables.sql

This is the SQL script to create all tables. Table names should be reflected in `dbi.php` as $table_person being 'rapcs_person' for example.

Of course, rename the database from `spaaviation` to your own database name and rename all tables to suite your club.