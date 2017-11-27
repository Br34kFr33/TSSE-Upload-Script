<?php

/**************************************************/
/***** PUT A \ BEFORE ANY ' IN SETTING VALUES *****/
/**************************************************/

/* Semi-obvious definitions:
 * Job: Refers to the processing done on data. Creating torrent, sfv checking, uploading, etc...
 * Scan: Looking at directory for new data.
 */

// All Directories must have ending slashes
// Use relative or absolute, no tilde. ie use /home/bob instead of just ~
define ('WATCH_DIR', '/home/todo/'); // Directory to watch for new data
define ('ERROR_DIR', '/home/error/'); // Directory to move data that errors during processing (ex: sfv failure).
define ('COMPLETE_DIR', '/home/complete/'); // Directory to move data to upon completed post processing.
define ('JOB_DIR', '/home/job_logs/'); // Directory for logging jobs outputs. Can be useful, but set to '' for no logs.
define ('LOG_FILE', '/home/main.log'); // Bot log file, writes things like "starting on XYZ... blah blah". Can be useful when daemonized. Set '' for none.
define ('TMP_DIR', '/home/temp/'); // Temporary file location. Should be fine with /tmp/, though a local user directory is better.
define ('OUTPUT_DIR', '/home/watch/'); // Location of .torrent files from the site after upload

define ('SITE_NICK', 'USERNAME-HERE'); // autouploader's site nick
define ('SITE_PASS', 'PASSWORD-HERE'); // login pass
define ('ANNOUNCE_URL', 'TORRENT-ANNOUNCE-AND-PASSKEY-HERE'); // Tracker announce URL

define ('CATEGORY', 42); // Category to upload to (31 = appz)

define ('MKTORRENT', '/usr/local/bin/mktorrent'); // Path to mktorrent ('/usr/bin/mktorrent')
define ('CKSFV', '/'); // Path to cksfv. Blank to skip sfv checking.(/usr/bin/cksfv)

define ('SCAN_INTERVAL', 5); // Seconds between the last scan's processing being completed and the next scan starting. (You Change this to what ever number you want)

define ('LOG_STAMP_FORMAT', 'H:i:s'); // Do not remove/make empty, though feel free to change using formats from http://php.net/manual/en/function.date.php
?>
