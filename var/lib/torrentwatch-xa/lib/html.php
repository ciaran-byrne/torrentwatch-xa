<?php

// Return a formatted html link that will call javascript in a normal browser

function setup_rss_list_html() {
    global $html_out;
    $html_out = "<div id='torrentlist_container'>\n";
}

function show_transmission_div() {
    global $html_out;
    $html_out .= '<div id="transmission_data" class="transmission">';
    $html_out .= '<ul id="transmission_list" class="torrentlist">';

    function finish_rss_list_html() {
        global $html_out;
        $html_out .= "</div>\n";
    }

}

function show_torrent_html($item, $feed, $feedName, $alt, $torHash, $matched, $id) {
    global $html_out, $test_run, $config_values;
    $guess = detectMatch($item['title']);
    if ($config_values['Settings']['Episodes Only'] == 1 && ($guess['episode'] == 'noShow' || !$guess))
        return;

    if (!$config_values['Settings']['Disable Hide List']) {
        if (isset($config_values['Hidden'][strtolower(trim(strtr($guess['title'], array(":" => "", "," => "", "'" => "", "." => " ", "_" => " "))))]))
            return;
    }

    if (($matched == "cachehit" || $matched == "downloaded" || $matched == "match") && $config_values['Settings']['Client'] != 'folder') {
        $matched = $torInfo['dlStatus'] = 'to_check'; //TODO this $torInfo overrides the torInfo function with the status codes in tools.php
        $torInfo['stats'] = 'Waiting for client data...';
        $torInfo['clientId'] = $torHash;
    }
    // add word-breaking flags after each period
    $ti = preg_replace('/\./', '.&shy;', $item['title']);
    // Copy feed cookies to item
    $ulink = get_torrent_link($item);
    if (($pos = strpos($feed, ':COOKIE:')) !== False) {
        $ulink .= substr($feed, $pos);
    }

    //TODO remove these after fixing $torInfo and torInfo() collision
    if (!isset($torInfo['dlStatus'])) {
        $torInfo['dlStatus'] = '';
    }
    if (!isset($torInfo['stats'])) {
        $torInfo['stats'] = '';
    }
    if (!isset($torInfo['status'])) {
        $torInfo['status'] = '';
    }
    if (!isset($torInfo['clientId'])) {
        $torInfo['clientId'] = '';
    }
    //TODO end remove^

    ob_start();
    require('templates/feed_item.tpl');
    $html_out .= ob_get_contents();
    ob_end_clean();
}

// The opening of the div which contains all the feeditems(one div per feed)
function show_feed_html($idx) {
    global $html_out, $config_values;
    if ($config_values['Settings']['Combine Feeds'] == 1) {
        $html_out .= '<div class="header combined">Combined Feeds</div>';
    }
    $html_out .= "<div class='feed' id='feed_$idx'>";
    if ($config_values['Settings']['Combine Feeds'] == 0) {
        $html_out .= "<div class=\"header\">\n";
        $html_out .= "<table width=\"100%\" cellspacing=\"0\"><tr><td class='hide_feed'>\n";
        $html_out .= "<span class=\"hide_feed_left\">\n";
        $html_out .= "<a href=\"#\" title=\"Hide this feed\" onclick=\"$.toggleFeed(" . $idx . ", 0)\">\n";
        $html_out .= "<img height='14' src=\"images/blank.gif\"></a></span></td>\n";
        if (!$config_values['Feeds'][$idx]['Name']) {
            $ti = $config_values['Feeds'][$idx]['Link'];
        } else {
            $ti = $config_values['Feeds'][$idx]['Name'];
        }
        $html_out .= "<td class='feed_title'><span>$ti</span><span class='matches'></span></td>\n";
        $html_out .= "<td class='hide_feed'>\n";
        $html_out .= "<span class=\"hide_feed_right\">\n";
        $html_out .= "<a href=\"#\" title=\"Hide this feed\" onclick=\"$.toggleFeed(" . $idx . ", 0)\">\n";
        $html_out .= "<img height='14' src=\"images/blank.gif\"></a></span></td>\n";
        $html_out .= "</tr></table></div>\n";
    }
    $html_out .= "<ul id='torrentlist' class='torrentlist'>";
}

function show_down_feed($idx) {
    global $html_out, $config_values;
    if (!$config_values['Feeds'][$idx]['Name']) {
        $ti = $config_values['Feeds'][$idx]['Link'];
    } else {
        $ti = $config_values['Feeds'][$idx]['Name'];
    }
    $html_out .= "<div class=\"errorHeader\">$ti is not available.</div>\n";
}

// Closing the div which contains all the feed items
function close_feed_html() {
    global $html_out, $config_values;
    $html_out .= '</ul></div>';
}