<?php
 
require_once('config.php');
error_reporting(E_ALL);
ini_set("log_errors", true);
ini_set("error_log", LOG_FILE);
 
print "Running bot startup checks...\r\n";
 
 // Checks whether required things are sets
 setting_checker ('WATCH_DIR', "Invalid watch dir setting!", array ('is_dir', 'die'));
 setting_checker ('ERROR_DIR', "Invalid error dir setting!", array ('is_dir', 'die'));
 setting_checker ('COMPLETE_DIR', "Invalid complete dir setting!", array ('is_dir', 'die'));
 setting_checker ('TMP_DIR', "Invalid temp dir setting!", array ('is_dir', 'die'));
 setting_checker ('MKTORRENT', "Invalid mktorrent setting!", array ('is_file', 'die'));
 setting_checker ('SITE_NICK', "Invalid site nick setting!", array ('die'));
 setting_checker ('SITE_PASS', "Invalid site pass setting!", array ('die'));
 setting_checker ('ANNOUNCE_URL', "Invalid announce url setting!", array ('die'));
 setting_checker ('OUTPUT_DIR', "Invalid output dir setting!", array ('is_dir', 'die'));
 setting_checker ('SCAN_INTERVAL', "Invalid scan interval setting!", array ('die'));
 
 if (setting_checker (JOB_DIR, '', array ('is_dir'))) {
     print "Using optional job dir (".JOB_DIR.")\r\n";
     $record_jobs = true;
 } else {
     print "Not using optional job dir (Unset or invalid)\r\n";
     $record_jobs = false;
 }
 if (setting_checker (LOG_FILE)) {
     print "Using optional log file (".LOG_FILE.")\r\n";
     $record_main = true;
 } else {
     print "Not using optional log file (Unset or invalid)\r\n";
     $record_main = false;
 }
 if (setting_checker (CKSFV, '', array ('is_file'))) {
     print "Using optional cksfv (".CKSFV.")\r\n";
     $run_sfv = true;
 } else {
     print "Not using optional cksfv (Unset or invalid)\r\n";
     $run_sfv = false;
 }
// Checks complete.
 
print "Bot started!\r\n\r\n";
 
while (true) {
    $queue_list = list_dir(WATCH_DIR);
    if ($queue_list === false) {
        print "Error accessing watch directory\r\n";
        if ($record_main)
            write_log(LOG_FILE, "Error accessing watch directory");
    } else {
        if (empty($queue_list)) {
            if ($record_main)
                write_log(LOG_FILE, "No jobs in this scan");
        } else {
            foreach ($queue_list as $job) {
                $job_log = JOB_DIR . $job;
                if (substr($job_log, -1) == '/')
                    $job_log = substr($job_log, 0, -1);
               
                print "Processing $job...\r\n";
                if ($record_main)
                    write_log(LOG_FILE, "Processing $job...");
                if ($record_jobs)
                    write_log($job_log, "Started processing");
               
                $location = WATCH_DIR . $job;
               
                // Directory
                if (substr($job, -1) == '/') {
                    $name = substr($job, 0, -1);
                   
                    // Remove .txt files
                    $files = find_extension($location, '.txt');
                    foreach ($files as $file)
                        unlink($location . $file);
                   
                    $file_list = list_dir(WATCH_DIR . $job);
                   
                    $nfo = "There was no NFO supplied with this release";
                    foreach ($file_list as $file) {
                        if (substr(strtolower($file), -4) == '.nfo' && is_file(WATCH_DIR . $job . $file)) {
                            $nfo     = file_get_contents(WATCH_DIR . $job . $file); // NFO found, get it
                            $match   = array(
                                "/[^a-zA-Z0-9-+.,&=??????:;*'\"???\/\@\[\]\(\)\s]/",
                                "/((\x0D\x0A\s*){3,}|(\x0A\s*){3,}|(\x0D\s*){3,})/",
                                "/\x0D\x0A|\x0A|\x0D/"
                            );
                            $replace = array(
                                "",
                                "\n\n",
                                "\n"
                            );
                            $nfo     = preg_replace($match, $replace, trim($nfo));
                           
                            print "NFO found\r\n";
                            if ($record_main)
                                write_log(LOG_FILE, "NFO found ($file)");
                            if ($record_jobs)
                                write_log($job_log, "NFO found ($file)");
                            break;
                        }
                    }
                   
                    if ($run_sfv) {
                        if ($record_jobs)
                            write_log($job_log, "SFV enabled");
                        $sfv_passed = null;
                        foreach ($file_list as $file) {
                            if (substr(strtolower($file), -4) == '.sfv' && is_file(WATCH_DIR . $job . $file)) {
                                print "Running SFV checker ($file)\r\n";
                                if ($record_main)
                                    write_log(LOG_FILE, "Running SFV checker ($file)");
                                if ($record_jobs)
                                    write_log($job_log, "Running SFV checker ($file)");
                                $sfv_output = shell_exec(CKSFV . ' -g ' . escapeshellarg(WATCH_DIR . $job . $file) . ' 2>&1');
                                if ($record_jobs)
                                    write_log($job_log, trim($sfv_output));
                               
                                $out = explode("\n", trim($sfv_output));
                                if (stripos(end($out), "everything ok") > -1) {
                                    print "SFV passed\r\n";
                                    if ($record_main)
                                        write_log(LOG_FILE, "SFV passed");
                                    $sfv_passed = true;
                                } else {
                                    $sfv_passed = false;
                                }
                                break;
                            }
                        }
                       
                        if ($sfv_passed === false) {
                            shell_exec("mv " . escapeshellarg(WATCH_DIR . $job) . " " . escapeshellarg(ERROR_DIR . $job));
                            print "SFV failed\r\n";
                            if ($record_main)
                                write_log(LOG_FILE, "SFV failed");
                            continue;
                        }
                    }
                   
                    // Completed folder-specific stuff (nfo, sfv)
                   
                    // File
                } else {
                    $nfo = "NFO Unavailable";
                    print "No NFO (single-file job)\r\n";
                    if ($record_main)
                        write_log(LOG_FILE, "No NFO (single-file job)");
                    if ($record_jobs)
                        write_log($job_log, "No NFO (single-file job)");
                   
                    print "No SFV checking (single-file job)\r\n";
                    if ($record_main)
                        write_log(LOG_FILE, "No SFV checking (single-file job)");
                    if ($record_jobs)
                        write_log($job_log, "No SFV checking (single-file job)");
                   
                    // Get the torrent name (file name minus the extension)
                    $name = $job;
                    $name = explode('.', $name);
                    array_pop($name);
                }
               
                print "Creating torrent...\r\n";
                $temp_torrent = TMP_DIR . $name . ".torrent";
                $torrent_out  = shell_exec(MKTORRENT . " -a " . escapeshellarg(ANNOUNCE_URL) . " -l " . 20 . " -o " . escapeshellarg($temp_torrent) . " -p " . escapeshellarg(WATCH_DIR . $job) . ' 2>&1');
                if ($record_jobs)
                    write_log($job_log, trim($torrent_out));
                echo $torrent_out;
                if (substr(trim($torrent_out), -5) != "done.") {
                    unlink($temp_torrent);
                    shell_exec("mv " . escapeshellarg(WATCH_DIR . $job) . " " . escapeshellarg(ERROR_DIR));
                    print "Torrent creation failed\r\n";
                    if ($record_main)
                        write_log(LOG_FILE, "Torrent creation failed");
                   
                    continue;
                }
               
                print "Torrent creation complete\r\n";
                if ($record_main)
                    write_log(LOG_FILE, "Torrent creation complete");
               
                print "Uploading\r\n";
                if ($record_main)
                    write_log(LOG_FILE, "Uploading torrent");
                if ($record_jobs)
                    write_log($job_log, "Uploading torrent");
               
                $login = log_in();
                if ($record_jobs)
                    write_log($job_log, trim($login));
               
                sleep(5);
               
                $upload = upload($temp_torrent, $name, $nfo);
                if ($record_jobs)
                    write_log($job_log, trim($upload));
               
                shell_exec("mv " . escapeshellarg(WATCH_DIR . $job) . " " . escapeshellarg(COMPLETE_DIR));
                shell_exec("mv " . escapeshellarg($temp_torrent) . " " . escapeshellarg(OUTPUT_DIR . $name . '.torrent'));
               
                print "Completed $job\r\n";
                if ($record_main)
                    write_log(LOG_FILE, "Completed $job");
                if ($record_jobs)
                    write_log($job_log, "Completed $job");
            }
        }
    }
   
    sleep(SCAN_INTERVAL);
}
 
 
function setting_checker($setting, $message = "", $options = array()) {
    // Basic settings, all should be met. If they are, proceed to options
    if (!defined($setting)) {
        if (!empty($message))
            print $message . "\r\n";
        if (in_array('die', $options))
            die();
        return false;
    }
    $constant = constant($setting);
   
    if (in_array('is_dir', $options) && !is_dir($constant)) {
        if (!empty($message))
            print $message . "\r\n";
        if (in_array('die', $options))
            die();
        return false;
    }
   
    if (in_array('is_file', $options) && !is_file($constant)) {
        if (!empty($message))
            print $message . "\r\n";
        if (in_array('die', $options))
            die();
        return false;
    }
   
    return true;
}
 
function find_extension($dir, $extension, $recursive = true) {
    if (is_dir($dir) && substr($dir, -1) != '/')
        $dir .= '/';
   
    $matches = array();
    $files   = list_dir($dir);
    if (!$files) {
        return array();
    } else {
        foreach ($files as $file) {
            if (is_dir($dir . $file) && $recursive) {
                $get = find_extension($dir . $file, $extension);
                foreach ($get as $id => $item)
                    $matches[] = $file . $item;
            } elseif (substr($file, -1 * strlen($extension)) == $extension) {
                $matches[] = $file;
            }
        }
    }
    return $matches;
}
 
function list_dir($directory) {
    if (substr($directory, -1) != '/')
        $directory .= '/';
    if (is_dir($directory)) {
        $listing = array();
        if ($dir_handle = opendir($directory)) {
            while (($item = readdir($dir_handle)) !== false) {
                if ($item != '..' && $item != '.') {
                    if (is_dir($directory . $item))
                        $item .= '/';
                    $listing[] = $item;
                }
            }
            closedir($dir_handle);
            return $listing;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
 
function write_log($file, $message) {
    $handler = fopen($file, "a");
    if ($handler === FALSE) {
        print "Error opening log file $file \r\n";
        return;
    }
   
    fwrite($handler, date(LOG_STAMP_FORMAT) . " - $message\r\n");
   
    fclose($handler);
}
 
function log_in() {
    $hash = md5(SITE_NICK . SITE_PASS);
   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array(
        'Expect: '
    ));
    curl_setopt($ch, CURLOPT_URL, 'http://YOUR-SITE-NAME-HERE/takelogin.php');
    curl_setopt($ch, CURLOPT_COOKIEJAR, TMP_DIR . 'cookies' . $hash . '.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, TMP_DIR . 'cookies' . $hash . '.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, 'http://YOUR-SITE-NAME-HERE/login.php?');
    curl_setopt($ch, CURLOPT_POST, 1);
    $post = array(
        "username" => SITE_NICK,
        "password" => SITE_PASS
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
   
    return $response;
}
 
function upload($torrent_file, $name, $nfo) {
    $hash = md5(SITE_NICK . SITE_PASS);
   
    $imdb = "";
    if (preg_match('/http:\/\/www.imdb.com\/title\/tt[\d]+\//', $nfo, $matches)) {
        $imdb = $matches[0];
    }
   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array(
        'Expect: '
    ));
    curl_setopt($ch, CURLOPT_URL, 'http://YOUR-SITE-NAME-HERE/upload.php');
    curl_setopt($ch, CURLOPT_COOKIEJAR, TMP_DIR . 'cookies' . $hash . '.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, TMP_DIR . 'cookies' . $hash . '.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, 'http://YOUR-SITE-NAME-HERE/upload.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    $post = array(
        "torrentfile" => "@$torrent_file",
        "MAX_FILE_SIZE" => "1500000",
        "message" => $nfo,
        "subject" => $name,
        "category" => CATEGORY,
        "t_link" => $imdb
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $response = curl_exec($ch);
    curl_close($ch);
   
    return $response;
}
 
function get_page($page) {
    $hash = md5(SITE_NICK . SITE_PASS);
   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array(
        'Expect: '
    ));
    curl_setopt($ch, CURLOPT_URL, $page);
    curl_setopt($ch, CURLOPT_COOKIEJAR, TMP_DIR . 'cookies' . $hash . '.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, TMP_DIR . 'cookies' . $hash . '.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, 'http://YOUR-SITE-NAME-HERE/');
    $response = curl_exec($ch);
    curl_close($ch);
   
    return $response;
}
?>
