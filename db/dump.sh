#!/bin/bash

mysqldump rate_stat --no-data > /var/www/html/db/db.sql
crontab -l > /var/www/html/db/crontab