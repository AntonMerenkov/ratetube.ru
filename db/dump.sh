#!/bin/bash

mysqldump rate_stat --no-data > /var/www/html/db/db.sql
crontab -l > /var/www/html/db/crontab
cp /etc/sphinx/sphinx.conf /var/www/html/db/sphinx.conf