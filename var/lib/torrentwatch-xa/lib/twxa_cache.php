<?php

// cache and history functions

function add_history($ti) {
    global $config_values;
    if (file_exists($config_values['Settings']['History'])) {
        $history = unserialize(file_get_contents($config_values['Settings']['History']));
    }
    $history[] = array('Title' => $ti, 'Date' => date("Y.m.d H:i"));
    file_put_contents($config_values['Settings']['History'], serialize($history));
}

function setupCache() {
    global $config_values;
    if (isset($config_values['Settings']['Cache Dir'])) {
        $cacheDir = $config_values['Settings']['Cache Dir'];
        twxaDebug("Enabling cache in: $cacheDir\n", 2);
        if(file_exists($cacheDir)) {
            if(is_dir($cacheDir)) {
                if(is_writeable($cacheDir)) {
                    // cache is already set up
                } else {
                    twxaDebug("Cache Dir not writeable: $cacheDir\n", -1);
                }
            } else {
                twxaDebug("Cache Dir not a directory: $cacheDir\n", -1);
            }
        } else {
            twxaDebug("Cache Dir does not exist, creating: $cacheDir\n", 2);
            mkdir($cacheDir, 0775, true);
        }
    }
}

function add_cache($ti) {
    global $config_values;
    if (isset($config_values['Settings']['Cache Dir'])) {
        $cache_file = $config_values['Settings']['Cache Dir'] . '/dl_' . sanitizeFilename($ti);
        touch($cache_file);
        return($cache_file);
    }
}

function clear_cache_by_feed_type($file) {
    global $config_values;
    $fileglob = $config_values['Settings']['Cache Dir'] . '/' . $file;
    twxaDebug("Clearing $fileglob\n", 2);
    foreach (glob($fileglob) as $fn) {
        twxaDebug("Removing $fn\n", 2);
        unlink($fn);
    }
}

function clear_cache_by_cache_type() {
    if (isset($_GET['type'])) {
        switch ($_GET['type']) {
            case 'feeds':
                clear_cache_by_feed_type("rsscache_*");
                clear_cache_by_feed_type("atomcache_*");
                break;
            case 'torrents':
                clear_cache_by_feed_type("dl_*");
                break;
            case 'all':
                clear_cache_by_feed_type("dl_*");
                clear_cache_by_feed_type("rsscache_*");
                clear_cache_by_feed_type("atomcache_*");
        }
    }
}

function get_torHash($cache_file) {
    $handle = fopen($cache_file, "r");
    if ($handle) {
        if (filesize($cache_file)) {
            $torHash = fread($handle, filesize($cache_file));
            return $torHash;
        } else {
            twxaDebug("No torrent hash in cache file: $cache_file\n", 0);
        }
    } else {
        twxaDebug("Unable to open cache file: $cache_file\n", 0);
    }
}

function check_cache_for_torHash($torHash) {
    global $config_values;
    $handle = opendir($config_values['Settings']['Cache Dir']);
    if ($handle !== false) {
        while (false !== ($file = readdir($handle))) {
            // loop through each cache file in the Cache Directory and check its torHash
            if (substr($file, 0, 3) === "dl_") {
                $tmpTorHash = get_torHash($config_values['Settings']['Cache Dir'] . "/" . $file);
                if ($torHash === $tmpTorHash) {
                    return $file;
                }
            }
        }
    } else {
        twxaDebug("Unable to open Cache Directory: " . $config_values['Settings']['Cache Dir'] . "\n", -1);
    }
    return "";
}

function check_cache_episode($ti) {
    // attempts to find previous downloads that have the same parsed title but different episode numbering styles
    global $config_values;
    $guess = detectMatch($ti);
    if ($guess['favTitle'] === "") {
        twxaDebug("Unable to guess a favoriteTitle for $ti\n", 0);
        return true; // do download
    }
    $handle = opendir($config_values['Settings']['Cache Dir']);
    if ($handle !== false) {
        while (false !== ($file = readdir($handle))) {
            // loop through each cache file in the Cache Directory
            if (substr($file, 0, 3) !== "dl_") {
                continue;
            }
            // check for a match by parsed title
            if (preg_replace('/[. ]/', '_', substr($file, 3, strlen($guess['favTitle']))) !== preg_replace('/[. ]/', '_', $guess['favTitle'])) {
                continue;
            }
            // if match by title, check for a match by episode
            //TODO does Ignore Batches need to be implemented here?
            $cacheguess = detectMatch(substr($file, 3)); // ignores first 3 characters, 'dl_'
            if ($cacheguess['numberSequence'] > 0 && $guess['numberSequence'] === $cacheguess['numberSequence']) {
                if ($guess['seasBatEnd'] === $cacheguess['seasBatEnd']) {
                    // end is in same season, compare episodes only
                    if ($guess['episBatEnd'] === "") {
                        // full season, compare
                        if ($cacheguess['episBatEnd'] !== "" && is_numeric($cacheguess['episBatEnd'])) {
                            return true; // title is a full season and is likely newer than the last episode in cache, do download
                        }
                        else {
                            twxaDebug("Equiv. in cache: ignoring: $ti (" . $guess['seasBatEnd'] . "x" . $guess['episBatEnd'] . ")\n", 2);
                            return false; // both are full seasons
                        }
                    } else if ($guess['episBatEnd'] === $cacheguess['episBatEnd']) {
                        if ($guess['itemVersion'] > $cacheguess['itemVersion']) {
                            if ($config_values['Settings']['Download Versions']) {
                                return true; // difference in item version, do download
                            } else {
                                twxaDebug("Older version in cache: ignoring newer: $ti (" . $guess['episode'] . "v" . $guess['itemVersion'] . ")\n", 2);
                                return false; // title is found in cache, version is newer, Download Versions is off, so don't download
                            }
                        } else {
                            twxaDebug("Equiv. in cache: ignoring: $ti (" . $guess['episode'] . "v" . $guess['itemVersion'] . ")\n", 2);
                            return false; // title and same version is found in cache, don't download
                        }
                    } else if ($guess['episBatEnd'] >= $cacheguess['episBatStart'] && $guess['episBatEnd'] < $cacheguess['episBatEnd']) {
                        twxaDebug("Ignoring: $ti (Cur:Cache " . $cacheguess['seasBatEnd'] . "x" . $cacheguess['episBatStart']
                                . "<=" . $guess['seasBatEnd'] . "x" . $guess['episBatEnd'] .
                                "<" . $cacheguess['seasBatEnd'] . "x" . $cacheguess['episBatEnd'] . ")\n", 2);
                        return false; // end episode is within the episode batch found in cache, don't download
                    } else {
                        // end episode appears to be newer than the last episode found in cache OR
                        // older than the earliest episode found in cache, do download
                        return true;
                    }
                } else if ($guess['seasBatEnd'] >= $cacheguess['seasBatStart'] && $guess['seasBatEnd'] < $cacheguess['seasBatEnd']) {
                    twxaDebug("Ignoring: $ti (Cur:Cache " . $cacheguess['seasBatEnd'] . "x" . $cacheguess['episBatStart']
                            . "<=" . $guess['seasBatEnd'] . "x" . $guess['episBatEnd'] .
                            "<" . $cacheguess['seasBatEnd'] . "x" . $cacheguess['episBatEnd'] . ")\n", 2);
                    return false; // end season appears to overlap with season range in cache, but is too old to compare episodes; don't download
                } else {
                    // end season appears to be entirely older than the earliest season found in cache OR
                    // entirely newer than the last season found in cache, do download
                    return true;
                }
            }
        }
    } else {
        twxaDebug("Unable to open Cache Directory: " . $config_values['Settings']['Cache Dir'] . "\n", -1);
    }
    return true; // do download
}

function check_cache($ti) {
    global $config_values;
    if (isset($config_values['Settings']['Cache Dir'])) {
        $cache_file = $config_values['Settings']['Cache Dir'] . '/dl_' . sanitizeFilename($ti);
        if (!file_exists($cache_file)) {
            return check_cache_episode($ti);
        } else {
            return false;
        }
    } else {
        return true; // cache is disabled, always download
    }
}
