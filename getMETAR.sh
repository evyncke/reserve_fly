#!/bin/sh
# Wrapper to run the METAR fetcher from cron.
# Set METAR_USERNAME and METAR_PASSWORD in the environment before running.
# Example cron line:
# METAR_USERNAME=ebspcrew METAR_PASSWORD=EBSPcrew124640 /var/www/resa/getMETAR.sh >> /var/log/getMETAR.log 2>&1

set -e
#cd /var/www/resa

export METAR_USERNAME="${METAR_USERNAME:-ebspcrew}"
export METAR_PASSWORD="${METAR_PASSWORD:-EBSPcrew124640}"

#/usr/bin/env python3 /var/www/resa/getMETAR.py --output-dir /var/www/nav
/usr/bin/env python3 getMETAR.py --output-dir /tmp
