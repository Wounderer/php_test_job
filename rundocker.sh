#!/bin/bash
redis-server  --daemonize yes
php /app/yii queue/listen
