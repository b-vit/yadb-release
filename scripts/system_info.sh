#!/bin/bash

CPU_=`echo $[100-$(vmstat 1 2|tail -1|awk '{print $15}')]`
FREE_DATA=`free -m | grep Mem`
UPTIME_=`awk '{print $1}' /proc/uptime`
TEMP_=`cat /sys/class/thermal/thermal_zone1/temp`
CURRENT_HDD=`df -h /dev/mmcblk0p1 | sed -n 2p | awk '{print $3}'`
TOTAL_HDD=`df -h /dev/mmcblk0p1 | sed -n 2p | awk '{print $2}'`
CURRENT=`echo $FREE_DATA | cut -f3 -d' '`
TOTAL=`echo $FREE_DATA | cut -f2 -d' '`

JSON_FMT='{"cpu":"%s","temp":"%s","current_memory":"%s","total_memory":"%s","uptime":"%s","current_hdd":"%s","total_hdd":"%s"}\n'
rm -f -- ../logs/system_info.json
printf "$JSON_FMT" "$CPU_" "$TEMP_" "$CURRENT" "$TOTAL" "$UPTIME_" "$CURRENT_HDD" "$TOTAL_HDD" >> ../logs/system_info.json