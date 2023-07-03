#!/bin/bash

NETFOLDER=/var/www/SF5/giza-baliabideak

sudo -u informatika -s `php $NETFOLDER/bin/console app:expired &>> $NETFOLDER/var/log/expired.log`
