# Kiosk mode

This directory contains all the configuration files to set a Ubuntu PC in kiosk mode with automated startup.

## Packages

Chromium browser and X should be installed.

## /home/user

The home directory of a plain user (no need to be root) starting Chromium.

## Dynamic DNS

Easier for remote maintenance if dynamic DNS is enabled, e.g. via:
/usr/bin/wget -6 --user=xxx --password=pxxx -O /tmp/dyn.out https://www.xxxx.org/dyn.php >> /tmp/dyn.log 2>&1

## Autologin the user

Modify /etc/gdm3/custom.conf to have

```
[daemon]
# Uncomment the line below to force the login screen to use Xorg
WaylandEnable=false

# Enabling timed login
#  TimedLoginEnable = true
#  TimedLogin = user1
#  TimedLoginDelay = 10

AutomaticLoginEnable=True
AutomaticLogin=user
```
