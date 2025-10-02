#!/bin/sh

QUEUE_COUNT=${QUEUE_GROUP_COUNT:-1}

for i in $(seq 1 $QUEUE_COUNT)
do
  echo "Starting worker for group_charge_$i"
  php artisan queue:work --queue=group_$i --sleep=2 --tries=1 --max-time=3600 --timeout=60 &
done

wait
