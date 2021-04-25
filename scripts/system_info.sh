#!/bin/bash

CPU_=`top -b -n1 | grep "Cpu(s)" | awk '{print $2 + $4}'`
FREE_DATA=`free -m | grep Mem`
UPTIME_=`awk '{print $1}' /proc/uptime`
TEMP_='cat /sys/class/thermal/thermal_zone1/temp'
CPU_TEMP=$((TEMP_/1000))
CURRENT_HDD=`df -h /dev/sda1 | sed -n 2p | awk '{print $3}'`
TOTAL_HDD=`df -h /dev/sda1 | sed -n 2p | awk '{print $2}'`
CURRENT=`echo $FREE_DATA | cut -f3 -d' '`
TOTAL=`echo $FREE_DATA | cut -f2 -d' '`

JSON_FMT='{"cpu":"%s","temp":"%s","current_memory":"%s","total_memory":"%s","uptime":"%s","current_hdd":"%s","total_hdd":"%s"}\n'
rm system_info.json
printf "$JSON_FMT" "$CPU_" "$CPU_TEMP" "$CURRENT" "$TOTAL" "$UPTIME_" "$CURRENT_HDD" "$TOTAL_HDD" >> system_info.json