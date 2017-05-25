#!/bin/bash
while :; do sleep 30; flock -n /tmp/agent-update-statistics.lock -c "php /var/www/html/yii agent/update-statistics" & done
