#!/bin/bash
PGREP="/usr/bin/pgrep"
RESTARTM="systemctl restart mariadb"
MYSQLD="mysqld"
$PGREP ${MYSQLD}
if [ $? -ne 0 ]; then
$RESTARTM
fi