# TSSE-Upload-Script
This is a php script that will scan directory for files every x amount of seconds(set by user), create torrent file, upload it to a TSSE site, move diretory to complete directory, and move .torrent file to a watch directory.  This script will take the information from the .nfo file and add it to the description.  This script will only upload to one category.  This script doesn't work if captcha is on.

## Requirements
* mktorrent
* cksfv(optional)
* curl
* php

## Setup
1.  Install mktorrent
2.  Install cksfv(this is optional)
3.  Edit config.php and set directories, site nick, site password, announce url, category, mktorrent patch and cksfv(if using), and scan interval(how often you want the script to scan for new files).
4.  Edit autoup.php and set your site url on lines 313, 317, 342, 346, and 374.

##  How to use
After setup run autoup.php file.  You might want to run in screen if your going to let this run 24/7.
