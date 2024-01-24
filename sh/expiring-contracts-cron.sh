#!/bin/bash

NETFOLDER=/var/www/SF6/giza-baliabideak

sudo -u informatika -s `php $NETFOLDER/bin/console app:about-to-expire 15 &>> $NETFOLDER/var/log/about-to-expire.log`
