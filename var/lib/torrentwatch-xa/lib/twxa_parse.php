<?php
/*
 * Helper functions for parsing torrent titles
 * currently part of Procedural Programming versions, will be replaced by OOP later
 * guess.php and feeds.php refer to this file
 */

$seps = '\s\.\_'; // separator chars: - and () were formerly also separators but caused problems; we need - for some Season and Episode notations

function sanitizeTitle($ti, $seps = '\s\.\_') {
    // cleans title of symbols, aiming to get the title down to just alphanumerics and reserved separators
    // we sanitize the title to make it easier to use Favorites and match episodes
    $sanitizeRegExPart = preg_quote('[]{}<>,_/','/');

    // Remove soft hyphens
    $ti = str_replace("\xC2\xAD", "", $ti);

    // Replace every tilde with a minus
    $ti = str_replace("~", "-", $ti);

    // replace with space any back-to-back sanitize chars that if taken singly would result in values getting smashed together
    $ti = preg_replace("/([a-z0-9\(\)])[$sanitizeRegExPart]+([a-z0-9\(\)])/i", "$1 $2", $ti);

    // remove all remaining sanitize chars
    $ti = preg_replace("/[$sanitizeRegExPart]/", '', $ti);

    // IMPORTANT: reduce multiple separators down to one separator (will break some matches if removed)
    $ti = preg_replace("/([$seps])+/", "$1", $ti);

    // trim beginning and ending spaces
    $ti = trim($ti);

    return $ti;
}

function normalizeCodecs($ti, $seps= '\s\.\_') {

    $ti = preg_replace("/x[$seps\-]+264/i", 'x264', $ti); // note the separator chars PLUS - char
    $ti = preg_replace("/h[$seps\-]+264/i", 'h264', $ti); // not sure why, but can't use | and $1 with x|h
    $ti = preg_replace("/x[$seps\-]+265/i", 'x265', $ti); // note the separator chars PLUS - char
    $ti = preg_replace("/h[$seps\-]+265/i", 'h265', $ti); // not sure why, but can't use | and $1 with x|h
    $ti = preg_replace("/10[$seps\-]?bit(s)?/i", '10bit', $ti); // normalize 10bit
    $ti = preg_replace("/8[$seps\-]?bit(s)?/i", '8bit', $ti);
    $ti = preg_replace("/FLAC[$seps\-]+2(\.0)?/i", 'FLAC2', $ti);
    $ti = preg_replace("/AAC[$seps\-]+2(\.0)?/i", 'AAC2', $ti);

    return $ti;
}

function simplifyTitle($ti, $seps = '\s\.\_') {
    // combines all the title processing functions

    $ti = sanitizeTitle($ti);

    // MUST normalize these codecs/qualities now so that users get trained to use normalized versions
    $ti = normalizeCodecs($ti);

    //TODO Maybe replace period-style separators with spaces (unless they are sanitized)
    //TODO Maybe pad parentheses with outside spaces (unless they are sanitized)
    //TODO Maybe remove audio codecs (not necessary if episode matching can handle being butted up against a codec)

    // detect and strip out 7 or 8-character checksums
    if(preg_match_all("/([0-9a-f])[0-9a-f]{6,7}/i", $ti, $matches, PREG_SET_ORDER)) { // do not initialize $matches--breaks checksum removal
        // only handle first one--not likely to have more than one checksum in any title
        $wholeMatch = $matches[0][0];
        $firstChar = $matches[0][1];
        if(preg_match("/\D/", $wholeMatch)) {
            // any non-digit means it's a checksum
            $ti = str_replace($wholeMatch, "", $ti);
        }
        else if($firstChar > 2) {
            // if first digit is not 0, 1, or 2, it's likely not a date
            $ti = str_replace($wholeMatch, "", $ti);
        }
        else {
            //TODO remove 8-digit checksums that look like they might be dates
        }
    }

    // run sanitize again due to possibility of checksum removal leaving back-to-back separators
    return sanitizeTitle($ti);
}

function detectResolution($ti, $seps = '\s\.\_') {
    $wByHRegEx = "/(\d{3,})[$seps]*[xX][$seps]*((\d{3,})[iIpP]?)/";
    $hRegEx = "/(\d{3,})[iIpP]/";
    $resolution = "";
    $matchedResolution = "";
    $verticalLines = "";
    $detQualities = [];
    $matches1 = [];
    $matches2 = [];

    // search arbitrarily for #### x #### (might also be Season x Episode or YYYY x MMDD)
    if(preg_match_all($wByHRegEx, $ti, $matches1, PREG_SET_ORDER)) {
        // check aspect ratios
        foreach($matches1 as $match) {
            if(
                $match[1] * 9 / 16 == $match[3] || // 16:9 aspect ratio
                $match[1] * 0.75 == $match[3] || // 4:3 aspect ratio
                $match[1] * 5 / 8 == $match[3] || // 16:10 aspect ratio
                $match[1] * 2 / 3 == $match[3] || // 3:2 aspect ratio
                $match[1] * 0.8 == $match[3] || // 5:4 aspect ratio
                $match[1] * 10 / 19 == $match[3] || // 19:10 4K aspect ratio
                $match[1] * 135 / 256 == $match[3] || // 256:135 4K aspect ratio
                $match[1] * 3 / 7 == $match[3] || // 21:9 4K aspect ratio
                $match[3] == 400 || // some people are forcing 704x400
                $match[3] == 480 || // some people are forcing 848x480p
                $match[3] == 544 || // some people are forcing 720x544
                $match[3] == 576 ||
                $match[3] == 720 ||
                $match[3] == 1076 || // some people are forcing 1920x1076
                $match[3] == 1080 ||
                $match[3] == 1200
            ) {
                $matchedResolution = $match[0];
                $resolution = strtolower($match[2]);
                $verticalLines = $match[3];
                if($resolution == $verticalLines) {
                    $resolution .= 'p'; // default to p if no i or p is specified
                }
                break; // shouldn't be more than one resolution in title
            }
        }
    }
    else if(preg_match_all($hRegEx, $ti, $matches2, PREG_SET_ORDER)) {
        // search for standalone resolutions in ###p or ###i format
        // shouldn't be more than one resolution in title
        $matchedResolution = $matches2[0][0];
        $resolution = strtolower($matchedResolution);
        $verticalLines = $matches2[0][1];
    }

    $ti = preg_replace("/$matchedResolution/", "", $ti);

    if($verticalLines == 720 || $verticalLines == 1080) {
        $detQualities = ["HD","HDTV"];
    }
    else if($verticalLines == 576) {
        $detQualities = ["ED","EDTV"];
        $ti = preg_replace("/SD(TV)?/i", "", $ti); // remove SD also (ED will be removed by detectQualities())
    }
    else if($verticalLines == 480) {
        $detQualities = ["SD","SDTV"];
    }

    $detQualities[] = $resolution;

    return ['parsedTitle' => sanitizeTitle($ti), 'detectedQualities' => $detQualities];
}

function detectQualities($ti, $seps = '\s\.\_') {
    $qualitiesFromResolution = detectResolution($ti, $seps);

    // search for more quality matches and prepend them to detectedQualities
    $ti = $qualitiesFromResolution['parsedTitle'];
    $detQualities = $qualitiesFromResolution['detectedQualities'];

    $qualityList = [
        'BDRip',
        'BRRip',
        'BluRay',
        'BD',
        'HR.HDTV',
        'HDTV',
        'HDTVRip',
        'DSRIP',
        'DVB',
        'DVBRip',
        'TVRip',
        'TVCap',
        'HR.PDTV',
        'PDTV',
        'SatRip',
        'WebRip',
        'DVDR',
        'DVDRip',
        'DVDScr',
        'DVD9',
        'DVD5',
        'XviDVD',
        // DVD regions
        'DVD R0',
        'DVD R1',
        'DVD R2',
        'DVD R3',
        'DVD R4',
        'DVD R5',
        'DVD R6',
        // END DVD regions
        'DVD',
        'DSR',
        'SVCD',
        'WEB-DL',
        'WEB.DL',
        'iTunes',
        // codecs--could be high or low quality, who knows?
        'XviD',
        'x264',
        'h264',
        'x265',
        'h265',
        'Hi10P',
        'Hi10',
        'Ma10p',
        '10bit',
        '8bit',
        'AVC',
        'MP4',
        'MKV',
        'BT.709',
        'BT.601',
        // analog color formats
        'NTSC',
        'PAL',
        'SECAM',
        // text encodings
        'BIG5',
        'BIG5+GB',
        'BIG5_GB',
        'GB', // might match unintended abbreviations
        // framespeeds
        '60fps',
        '30fps',
        '24fps',
        // typically low quality
        'VHSRip',
        'TELESYNC'
        ];

    foreach ($qualityList as $qualityListItem) {
        $qualityListItemRegExPart = preg_quote($qualityListItem, '/');
        if(preg_match("/\b$qualityListItemRegExPart\b/i", $ti)) { // must use boundaries because SxE notation can collide with x264
            $detQualities[] = $qualityListItem;
            // cascade down through, removing immediately-surrouding dashes
            $ti = preg_replace("/\-+$qualityListItemRegExPart\-+/i", '', $ti);
            $ti = preg_replace("/\-+$qualityListItemRegExPart\b/i", '', $ti);
            $ti = preg_replace("/\b$qualityListItemRegExPart\-+/i", '', $ti);
            $ti = preg_replace("/\b$qualityListItemRegExPart\b/i", '', $ti);
        }
    }

    return [
        'parsedTitle' => $ti,
        'detectedQualities' => $detQualities,
    ];
}

function detectAudioCodecs($ti, $seps = '\s\.\_') {
    $detAudioCodecs = [];

    $audioCodecList = [
        'AC3',
        'AAC',
        'AACx2',
        'FLAC2',
        'FLAC',
        '320K',
        '320Kbps',
        'MP3',
        '5.1ch',
        '5.1',
    ];

    foreach ($audioCodecList as $audioCodecListItem) {
        $audioCodecListItemRegExPart = preg_quote($audioCodecListItem, '/');
        if(preg_match("/\b$audioCodecListItemRegExPart\b/i", $ti)) {
            $detAudioCodecs[] = $audioCodecListItem;
            // cascade down through, removing immediately-surrouding dashes
            $ti = preg_replace("/\-+$audioCodecListItemRegExPart\-+/i", '', $ti);
            $ti = preg_replace("/\-+$audioCodecListItemRegExPart\b/i", '', $ti);
            $ti = preg_replace("/\b$audioCodecListItemRegExPart\-+/i", '', $ti);
            $ti = preg_replace("/\b$audioCodecListItemRegExPart\b/i", '', $ti);
        }
    }

    return [
        'parsedTitle' => $ti,
        'detectedAudioCodecs' => $detAudioCodecs
    ];
}

function detectItem($ti, $wereQualitiesDetected = false, $seps = '\s\.\_') {
    // $wereQualitiesDetected is a param because some manga use "Vol. ##" notation

    $itemVersion = 1; // assume default item version of 1--this will handle PROPER/REPACK/RERIP and v## systems

    // $detMediaType state table
    // 0 = Unknown
    // 1 = Video
    // 2 = Audio
    // 4 = Print media
    $detMediaType = 1; // assume default media type = Video

    // $numberSequence allows for parallel numbering sequences
    // like Movie 1, Movie 2, Movie 3 alongside Episode 1, Episode 2, Episode 3

    // 0 = Unknown
    // 1 = Video: Season x Episode, Print Media: Volume x Chapter, Audio: Season x Episode
    // 2 = Video: Date, Print Media: Date, Audio: Date (all these get Season = 0)
    // 4 = Video: Full Season x Volume/Part, Print Media: Full Volume, Audio: Full Season
    // 8 = Video: Preview, Print Media: N/A, Audio: Opening songs
    // 16 = Video: Special, Print Media: N/A, Audio: Ending songs
    // 32 = Video: OVA episode sequence, Print Media: N/A, Audio: Character songs
    // 64 = Video: Movie sequence (Season = 0), Print Media: N/A, Audio: OST
    // 128 = Video: Volume x Disc sequence, Print Media: N/A, Audio: N/A
    $numberSequence = 1; // assume ## x ## number sequence

    $detSeasBatchStart = '';
    $detSeasBatchEnd = '';
    $detEpisBatchStart = '';
    $detEpisBatchEnd = '';
    $matches = [];
    $debugMatchOutput = '0.';

    // IMPORTANT NOTES:
    // treat anime notation as Season 1
    // treat date-based episodes as Season 0 EXCEPT...
    // ...when YYYY-##, use year as the Season and ## as the Episode
    // because of PHP left-to-right matching order, (Season|Seas|Se|S) works but (S|Se|Seas|Season) will match S and move on

    // GOALS:
    // handle Special and OVA episodes
    // handle PROPER and REPACK episodes as version 2 if not specified
    // use short circuits to reduce overhead

    //TODO go back and restrict \d+ to \d{1,4} where appropriate

    // MATCHES STILL IN PROGRESS, NOT DONE OR NOT TESTED ENOUGH:
    // S##E##.#
    // S##.#E##
    // ###.#v3 (anime episode number, version 3)
    // 01 of 20 1978
    // 4x04 (2014)
    // The.Haunting.Of.S04.Revealed.Special (Season 4, Special)
    // "DBZ Abridged Episodes 1-44 + Movies (TFS)" (big batch)
    // CFL.2014.RS.Week18.(25 oct).BC.Lions.v.WPG.Blue.Bombers.504p
    // Batch XX-XX
    // 27th October 2014
    // 14Apr3
    // Serie.A.2014.Day08(26 oct).Cesena.v.Inter.400p

    // decode HTML and URL encoded characters to reduce number of extraneous numerals
    $ti = html_entity_decode($ti, ENT_QUOTES);

    // split off v2 from ##v2
    $ti = preg_replace('/\b(\d{1,3})([Vv]\d{1,2})\b/', "$1 $2", $ti);

    // bucket the matches of all numbers of different lengths
    preg_match_all("/(\d+)/u", $ti, $matchesNumbers, PREG_SET_ORDER); // can't initialize $matchesNumbers here due to isset tests later
    twxa_debug($ti . " => ");

    // is there at least one number? can't have an episode otherwise (except in case of PV preview episode)
    if(isset($matchesNumbers[0])) {
        //TODO add detection of isolated PV (assign episode = 0)
        if(!isset($matchesNumbers[1])) {
            // only one integer found, probably anime-style episode number, but look for preceding words
            $debugMatchOutput = "1.";
            $matchedNumber = $matchesNumbers[0][1];
            $matchedNumberLength = strlen($matchedNumber);

            // three digits or less
            if($matchedNumberLength < 4) {
                // search for the word Season, Temporada; should also catch Season ## Complete
                if(preg_match_all("/(Season|Saison|Seizoen|\bSeas|\bSais|\bSea|\bSe|\bS|Temporada|\bTemp|\bT)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 4;
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                    $detEpisBatchStart = 1;
                    $detEpisBatchEnd = '';
                    $debugMatchOutput = "1. Season ##";
                }
                // search for ##rd/##th Season
                else if(preg_match_all("/(\d{1,2})(rd|nd|th)[$seps]?(Season|Seas\b|Sea\b|Se\b|S\b)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 4;
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchStart = 1;
                    $detEpisBatchEnd = '';
                    $debugMatchOutput = "1. ##rd/##th/##nd Season";
                }
                // search for the word Volume, Volumen
                else if(preg_match_all("/(Volumen|Volume|\bVol)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    if($wereQualitiesDetected == true) {
                        $numberSequence = 4; // video Season x Volume/Part numbering
                        $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                        $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                        $debugMatchOutput = "1. Video Volume ##";
                    }
                    else {
                        $detMediaType = 4; // assume Print Media
                        $numberSequence = 4;
                        $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                        $detEpisBatchEnd = $detEpisBatchStart = 0;
                        $debugMatchOutput = "1. Print Media Volume ##";
                    }
                }
                // search for V. ##--Volume, not version, and not titles like ARC-V
                else if(preg_match_all("/[$seps]V[$seps]{1,2}(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    //TODO may need to move this to later in order
                    if($wereQualitiesDetected == true) {
                        $numberSequence = 4; // video Season x Volume/Part numbering
                        $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                        $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                        $debugMatchOutput = "1. Video V. ## (Volume, not version)";
                    }
                    else {
                        $detMediaType = 4; // assume Print Media
                        $numberSequence = 4;
                        $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                        $detEpisBatchEnd = $detEpisBatchStart = 0;
                        $debugMatchOutput = "1. Print Media V. ## (Volume, not version)";
                    }
                }
                // search for the word Chapter, Capitulo
                else if(preg_match_all("/(Chapter|Capitulo|Chapitre|\bChap|\bCh|\bC)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $detMediaType = 4;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Volume 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "1. Chapter ##";
                }
                // search for the word Movie
                else if(preg_match_all("/(Movie|\bMov)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 64;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // for Movies, assume Season = 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2]; // assume Movie 1
                    $debugMatchOutput = "1. Movie ##";
                }
                // search for Movie v##
                else if(preg_match_all("/(Movie|\bMov)[$seps]?v(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 64;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // for Movies, assume Season = 0
                    $detEpisBatchEnd = $detEpisBatchStart = 1; // assume Movie 1
                    $itemVersion = $matches[0][2];
                    $debugMatchOutput = "1. Movie v##";
                }
                // search for the word Film
                else if(preg_match_all("/(Film|\bF)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 64;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // for Movies, assume Season = 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "1. Film ##";
                }
                // search for the word Film v##
                else if(preg_match_all("/(Film|\bF)[$seps]?v(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 64;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // for Movies, assume Season = 0
                    $detEpisBatchEnd = $detEpisBatchStart = 1; // assume Movie 1
                    $itemVersion = $matches[0][2];
                    $debugMatchOutput = "1. Film v##";
                }
                // search for the word Part
                else if(preg_match_all("/(Part|\bPt)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    //TODO handle Part
                    $debugMatchOutput = "1. Part ##";
                }
                // search for the word Episode
                else if(preg_match_all("/(Episode|\bEpis|\bEp|\bE)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    // should not be any mention of Season ## before Episode ## because only one ## found
                    $detSeasBatchEnd = $detSeasBatchStart = 1;
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "1. Episode ##";
                }
                // search for Special v##
                else if(preg_match_all("/[$seps](Special|Spec)[$seps]?v(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 16;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = 1; // assume Special Episode 1
                    $itemVersion = $matches[0][2]; // only number is the version number
                    $debugMatchOutput = "1. Special v##";
                }
                // search for "02 - Special"
                else if(preg_match_all("/\b(\d+)[$seps]?-?[$seps]?(Special|Spec\b|Sp\b)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 16;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "1. ## Special";
                }
                // search for "Special - 02"
                else if(preg_match_all("/\b(Special|Spec|Sp)[$seps]?-?[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                    // Special - 02
                    // Spec02
                    // SP# (Special #)
                    $numberSequence = 16;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "1. Special ##";
                }
                // search for OVA v##
                else if(preg_match_all("/[$seps](OVA|OAV)[$seps]?v(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 32;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = 1; // assume OVA Episode 1
                    $itemVersion = $matches[0][2]; // only number is the version number
                    $debugMatchOutput = "1. OVA v##";
                }
                //TODO handle "OVA (1983)"
                //TODO handle "Nichijou no 0 Wa | Nichijou OVA"
                // Roman numeral SS-EE, only handle Seasons I, II, and III
                // NOTE: The ability to detect Roman numeral seasons means that to match the title, one must NOT
                // put the Roman numeral season in the Favorite filter. For example: "Sword Art Online II" would not
                // work as a Favorite Filter because the "II" would get stripped out of the title by detectItem().
                // Use "Sword Art Online" instead. But this is counterintuitive--people would think of the "II" as being
                // part of the title.
                else if(preg_match_all("/\b(I{1,3})[$seps]?\-?[$seps]?(\d+)/", $ti, $matches, PREG_SET_ORDER)) {
                    $detSeasBatchEnd = $detSeasBatchStart = strlen($matches[0][1]);
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "1. Roman numeral Season - EE";
                }
                // pound sign and number
                else if(preg_match_all("/#[$seps]?(\d+)/", $ti, $matches, PREG_SET_ORDER)) {
                    // could be Vol., Number, but assume it's an Episode
                    $detSeasBatchEnd = $detSeasBatchStart = 1;
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "1. pound sign ##";
                }
                // apostrophe ## (abbreviated year)
                else if(preg_match_all("/\'[$seps]?(\d\d)/", $ti, $matches, PREG_SET_ORDER)) {
                    $thisYear = getdate()['year'];
                    $guessedYearCurrentCentury = substr($thisYear, 0, 2) . $matches[0][1];
                    $guessedYearPriorCentury = substr($thisYear - 1, 0, 2) . $matches[0][1];
                    if($guessedYearCurrentCentury + 0 <= $thisYear && $guessedYearCurrentCentury + 0 > 1895) {
                        $numberSequence = 2;
                        $detSeasBatchEnd = $detSeasBatchStart = 1;
                        $detEpisBatchEnd = $detEpisBatchStart = $guessedYearCurrentCentury;
                    }
                    else if($guessedYearPriorCentury + 0 <= $thisYear && $guessedYearPriorCentury + 0 > 1895) {
                        $numberSequence = 2;
                        $detSeasBatchEnd = $detSeasBatchStart = 1;
                        $detEpisBatchEnd = $detEpisBatchStart = $guessedYearPriorCentury;
                    }
                    $debugMatchOutput = "1. apostrophe ##";
                }
                // search for uppercase PV 0
                else if(preg_match_all("/\bPV[$seps]?0{1,2}\b/", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 8;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = 0;
                    $debugMatchOutput = "1. PV 0 or PV 00";
                }
                // search for uppercase PV ##
                else if(preg_match_all("/\bPV[$seps]?(\d{1,2})\b/", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 8;
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "1. PV or PV ##";
                }
                // search for standalone Version ##
                else if(preg_match_all("/[$seps]v(\d{1,2})/i", $ti, $matches, PREG_SET_ORDER)) {
                    $numberSequence = 64; // assume Movie numbering
                    $itemVersion = $matches[0][1];
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // for Movies, assume Season = 0;
                    $detEpisBatchEnd = $detEpisBatchStart = 1; // assume Movie 1
                    $debugMatchOutput = "1. Version ##";
                }
                else {
                    // assume it's an anime-style episode number
                    //TODO make sure it's not butted up against text
                    if(preg_match_all("/[$seps\-\(\)\[\]#\x{3010}\x{3011}\x{7B2C}](\d+)([$seps\-\(\)\[\]\x{3010}\x{3011}]|$)/u", $ti, $matches, PREG_SET_ORDER)) {
                        if($matches[0][1] + 0 > 0) {
                            $detSeasBatchEnd = $detSeasBatchStart = 1;
                            $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                            $debugMatchOutput = "1. isolated EEE";
                        }
                        else {
                            $numberSequence = 8;
                            $detSeasBatchEnd = $detSeasBatchStart = 1;
                            $detEpisBatchEnd = $detEpisBatchStart = 0;
                            $debugMatchOutput = "1. isolated EEE = 0, treat as PV 0";
                        }
                    }
                    else if(preg_match_all("/\x{7B2C}(\d+)(\x{8a71}|\x{8bdd})/u", $ti, $matches, PREG_SET_ORDER)) {
                        $detSeasBatchEnd = $detSeasBatchStart = 1;
                        $detEpisBatchEnd = $detEpisBatchStart = mb_convert_kana($matches[0][1], "a", "UTF-8");
                        $debugMatchOutput = "1. Japanese ## Episode";
                    }
                    else if(preg_match_all("/(\x{7B2C}|\x{5168})(\d+)\x{5dfb}/u", $ti, $matches, PREG_SET_ORDER)) {
                        $detMediaType = 4;
                        $numberSequence = 4;
                        $detSeasBatchEnd = $detSeasBatchStart = mb_convert_kana($matches[0][2], "a", "UTF-8");
                        $detEpisBatchEnd = $detEpisBatchStart = 0;
                        $debugMatchOutput = "1. Japanese ## Print Media Book/Volume";
                    }
                    else if(preg_match_all("/\x{7B2C}(\d+)/u", $ti, $matches, PREG_SET_ORDER)) {
                        $detSeasBatchEnd = $detSeasBatchStart = 1;
                        $detEpisBatchEnd = $detEpisBatchStart = mb_convert_kana($matches[0][1], "a", "UTF-8");
                        $debugMatchOutput = "1. Japanese ##";
                    }
                }
            }
            else if($matchedNumberLength == 4) {
                // check if YYYY or MMDD or DDMM or MMYY or YYMM, otherwise assume ####
                // 1896 was year of first moving picture

                $thisYear = getdate()['year'];

                if($matchedNumber > 1895 && $matchedNumber <= $thisYear) {
                    // probably YYYY
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matchedNumber;
                    $debugMatchOutput = "1. YYYY";
                }
                else {
                    $pair1 = substr($matchedNumber, 0, 2);
                    $pair2 = substr($matchedNumber, 2);
                    if(checkdate($pair2, $pair1, $thisYear)) {
                        // probably DDMM (assume YYYY is current year)
                        $numberSequence = 2;
                        $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                        $detEpisBatchEnd = $detEpisBatchStart = $pair2 . $pair1;
                        $debugMatchOutput = "1. DDMM";
                    }
                    else if(checkdate($pair1, $pair2, $thisYear)) {
                        // probably MMDD (assume YYYY is current year)
                        $numberSequence = 2;
                        $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                        $detEpisBatchEnd = $detEpisBatchStart = $matchedNumber;
                        $debugMatchOutput = "1. MMDD";
                    }
                    // we don't handle MMYY or YYMM because it is too tedious to figure out YYYY from YY
                    else {
                        $detSeasBatchEnd = $detSeasBatchStart = 1; // episode notation gets Season 1
                        $detEpisBatchEnd = $detEpisBatchStart = $matchedNumber;
                        $debugMatchOutput = "1. ####";
                    }
                }
            }
            else if($matchedNumberLength == 8) {
                // YYYYMMDD
                // YYYYDDMM
                // MMDDYYYY
                // DDMMYYYY
                // ######## (not likely)
                // 8-digit numeric checksum (should have been filtered out by now)

                // split into four pairs of numerals
                $four1 = substr($matchedNumber, 0, 4);
                $four2 = substr($matchedNumber, 4, 4);
                $pair1 = substr($four1, 0, 2);
                $pair2 = substr($four1, 2, 2);
                $pair3 = substr($four2, 0, 2);
                $pair4 = substr($four2, 2, 2);
                $thisYear = getdate()['year'];

                if(checkdate($pair3 + 0, $pair4 + 0, $four1 + 0) && $four1 + 0 <= $thisYear && $four1 + 0 > 1895) {
                    // YYYYMMDD
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matchedNumber;
                    $debugMatchOutput = "1. YYYYMMDD";
                }
                else if(checkdate($pair4 + 0, $pair3 + 0, $four1 + 0) && $four1 + 0 <= $thisYear && $four1 + 0 > 1895) {
                    // YYYYDDMM
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $four1 . $pair4 . $pair3;
                    $debugMatchOutput = "1. YYYYDDMM";
                }
                else if(checkdate($pair1 + 0, $pair2 + 0, $four2 + 0) && $four2 + 0 <= $thisYear && $four2 + 0 > 1895) {
                    // MMDDYYYY
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $four2 . $four1;
                    $debugMatchOutput = "1. MMDDYYYY";
                }
                else if(checkdate($pair2 + 0, $pair1 + 0, $four2 + 0) && $four2 + 0 <= $thisYear && $four2 + 0 > 1895) {
                    // DDMMYYYY
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $four2 . $pair2 . $pair1;
                    $debugMatchOutput = "1. DDMMYYYY";
                }
                else {
                    // unknown ########
                    if($wereQualitiesDetected) {
                        $detMediaType = 1;
                    }
                    else {
                        $detMediaType = 0;
                    }
                    $numberSequence = 0;
                    $debugMatchOutput = "1. Unknown ########";
                }
            }
            else if($matchedNumberLength == 6) {
                // YYMMDD
                // YYDDMM
                // MMDDYY
                // DDMMYY
                // YYYYMM
                // MMYYYY
                // ######

                // split into three pairs of numerals
                $pair1 = substr($matchedNumber, 0, 2);
                $pair2 = substr($matchedNumber, 2, 2);
                $pair3 = substr($matchedNumber, 4, 2);
                $thisYear = getdate()['year'];
                $thisYearPair1 = substr($thisYear, 0, 2);

                if(checkdate($pair3 + 0, 1, $pair1 . $pair2 + 0) && $pair1 . $pair2 + 0 <= $thisYear && $pair1 . $pair2 + 0 > 1895) {
                    // YYYYMM
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matchedNumber;
                    $debugMatchOutput = "1. YYYYMM";
                }
                else if(checkdate($pair1 + 0, 1, $pair2 . $pair3 + 0) && $pair2 . $pair3 + 0 <= $thisYear && $pair2 . $pair3 + 0 > 1895) {
                    // MMYYYY
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $pair2 . $pair3 . $pair1;
                    $debugMatchOutput = "1. MMYYYY";
                }
                else if(checkdate($pair2 + 0, $pair3 + 0, $thisYearPair1 . $pair1 + 0) && $thisYearPair1 . $pair1 + 0 <= $thisYear && $thisYearPair1 . $pair1 + 0 > 1895) {
                    // YYMMDD
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $thisYearPair1 . $pair1 . $pair2 . $pair3;
                    $debugMatchOutput = "1. YYMMDD";
                }
                else if(checkdate($pair1 + 0, $pair2 + 0, $thisYearPair1 . $pair3 + 0) && $thisYearPair1 . $pair3 + 0 <= $thisYear && $thisYearPair1 . $pair3 + 0 > 1895) {
                    // MMDDYY
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $thisYearPair1 . $pair3 . $pair1 . $pair2;
                    $debugMatchOutput = "1. MMDDYY";
                }
                else if(checkdate($pair2 + 0, $pair1 + 0, $thisYearPair1 . $pair3 + 0) && $thisYearPair1 . $pair3 + 0 <= $thisYear && $thisYearPair1 . $pair3 + 0 > 1895) {
                    // DDMMYY
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $thisYearPair1 . $pair3 . $pair2 . $pair1;
                    $debugMatchOutput = "1. DDMMYY";
                }
                else if(checkdate($pair3 + 0, $pair2 + 0, $thisYearPair1 . $pair1 + 0) && $thisYearPair1 . $pair1 + 0 <= $thisYear && $thisYearPair1 . $pair1 + 0 > 1895) {
                    // YYDDMM
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $thisYearPair1 . $pair1 . $pair3 . $pair2;
                    $debugMatchOutput = "1. YYDDMM";
                }
                else {
                    // ######
                    $numberSequence = 0;
                    if($wereQualitiesDetected) {
                        $detMediaType = 1;
                    }
                    else {
                        $detMediaType = 0;
                    }
                    $debugMatchOutput = "1. Unknown ######";
                }
            }
            else if($matchedNumberLength == 12) {
                // YYYYMMDDHHMM
                $numberSequence = 2;
                $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                $detEpisBatchEnd = $detEpisBatchStart = substr($matchedNumber, 0, 8); // truncate the lengthy Date notation
                $debugMatchOutput = "1. YYYYMMDDHHMM";
            }
            else if($matchedNumberLength == 14) {
                // YYYYMMDDHHMMSS
                $numberSequence = 2;
                $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                $detEpisBatchEnd = $detEpisBatchStart = substr($matchedNumber, 0, 8); // truncate the lengthy Date notation
                $debugMatchOutput = "1. YYYYMMDDHHMMSS";
            }
            else {
                $numberSequence = 0;
                if($wereQualitiesDetected) {
                    $detMediaType = 1;
                }
                else {
                    $detMediaType = 0;
                }
                $debugMatchOutput = "1. unidentifiable #";
            }
        }
        else if(!isset($matchesNumbers[2])) {
            // only two numbers found
            $debugMatchOutput = "2.";
            // go straight for S##E##
            if(preg_match_all("/(Season|\bSeas|\bSe|\bS)[$seps]?(\d+)[$seps]?[\,\-]?[$seps]?(Episode|Epis|Epi|Ep|E|)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // Example:
                // S01E10
                // Season 1 Episode 10
                // Se.2.Ep.5
                // Seas 2, Epis 3
                // S3 - E6
                // S2 - 32
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][4];
                $debugMatchOutput = "2. S##-E##";
            }
            else if(preg_match_all("/\b(\d+)[$seps]?-?[$seps]?(v|V)(\d{1,2})\b/", $ti, $matches, PREG_SET_ORDER)) {
                //\b##v#\b (Episode ## Version #)
                //TODO might need to move this below other superset matches
                $detSeasBatchEnd = $detSeasBatchStart = 1;
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                $itemVersion = $matches[0][3];
                $debugMatchOutput = "2. isolated ##v#";
            }
            else if(preg_match_all("/(Seasons|Season|\bSeas|\bSe|\bS)[$seps]?(\d+)[$seps]?(through|thru|to)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // short-circuit Seasons ## through|thru|to ##
                // Example:
                // Seasons 2 to 4
                // S2 thru 4
                $detSeasBatchStart = $matches[0][2];
                $detSeasBatchEnd = $matches[0][4];
                $detEpisBatchStart = 1;
                $detEpisBatchEnd = '';
                $debugMatchOutput = "2. Seasons ## through|thru|to ##";
            }
            else if(preg_match_all("/(Seasons|\bSeas)[$seps]?1[$seps]?\-[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // short-circuit Seasons 1 - ##
                // Example:
                // Seasons 1 - 4
                // Seas. 1 - 5
                // assume count($matches) == 1
                $detSeasBatchStart = 1;
                $detSeasBatchEnd = $matches[0][2];
                $detEpisBatchStart = 1;
                $detEpisBatchEnd = '';
                $debugMatchOutput = "2. Seasons 1 - ##";
            }
            else if(preg_match_all("/(Season|\bSe|\bS)[$seps]?1[$seps]?\-[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // short-circuit S1 - ###
                // Example:
                // S1 - 24
                // Se 1 - 5
                // Season 1 - 3
                $detSeasBatchStart = 1;
                $detSeasBatchEnd = $matches[0][2];
                $detEpisBatchStart = 1;
                $detEpisBatchEnd = '';
                $debugMatchOutput = "2. Season|Se|S1 - ###";
            }
            // search for the word Season, Temporada; should also catch Season ## Complete; put this last in Season matches
            else if(preg_match_all("/(Season|Saison|Seizoen|Sezona|\bSeas|\bSais|\bSea|\bSe|\bS|Temporada|\bTemp|\bT)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchStart = 1;
                $detEpisBatchEnd = '';
                $debugMatchOutput = "2. Season ##, # elsewhere";
            }
            // search for 1st|2nd|3rd Season ##
            else if(preg_match_all("/(\d{1,2})(st|th|nd)[$seps]?(Season|Saison|Seizoen|Sezona)[$seps]?-?[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][4];
                $debugMatchOutput = "2. #nd Season ##";
            }
            // search for v##c##
            else if(preg_match_all("/\bv[$seps]?(\d+)[$seps]?c[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume ## Chapter ##
                $debugMatchOutput = "2. isolated v##c##";
            }
            // search for c##v##
            else if(preg_match_all("/\bc[$seps]?(\d+)[$seps]?v[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume ## Chapter ##
                $debugMatchOutput = "2. isolated c##v##";
            }
            // search for c## (v##)
            else if(preg_match_all("/\bc[$seps]?(\d+)[$seps]?\(?v[$seps]?(\d+)\)?\b/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume ## Chapter ##
                $debugMatchOutput = "2. isolated c## (v##)";
            }
            // isolated ##-##END
            else if(preg_match_all("/\b(\d+)[$seps]?(-|to|thru|through)[$seps]?end\b/i", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                $debugMatchOutput = "2. isolated ##-##END";
            }
            //TODO handle V##.## (Software Version ##.##)
            // isolated ##x##
            else if(preg_match_all("/\b(\d+)[$seps]?[xX][$seps]?(\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                // search for explicit SSxEE notation
                //TODO make sure x264 doesn't match
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $debugMatchOutput = "2. isolated SSxEE";
            }
            // isolated ## of ##
            else if(preg_match_all("/\b(\d+)[$seps]?(OF|Of|of)[$seps]?(\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1; // if no mention of Season, assume Season 1
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                $debugMatchOutput = "2. isolated ## of ##";
            }
            //TODO handle Volume ## & ##
            //TODO handle Chapter ## & ##
            //TODO handle Season ## & ##
            //TODO handle Episode ## & ##
            // isolated ## & ##
            // must be after other ## & ## but before ## elsewheres
            else if(preg_match_all("/\b(\d+)[$seps]?(\&|and|\+|y|et)[$seps]?(\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                $detEpisBatchEnd = $matches[0][3];
                $detEpisBatchStart = $matches[0][1];
                $debugMatchOutput = "2. isolated ## & ##";
            }
            // search for the word Volume, Volumen
            else if(preg_match_all("/(Volumes|Volumens|Volume|\bVol|\bV)[$seps]?(\d+)[$seps]?(-|to|thru|through)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume
                $debugMatchOutput = "2. Volumes ##-##";
            }
            // search for the word Volume, Volumen, Chapter
            else if(preg_match_all("/(Volumen|Volume|\bVol|\bV\.)[$seps]?(\d+)[$seps]?(Chapitre|Chapter|Chap|\bCh|\bC\.)[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume
                $debugMatchOutput = "2. Volume ##, Chapter ##";
            }
            // search for the word Volume, Volumen
            else if(preg_match_all("/(Volumen|Volume|\bVol|\bV\.)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume
                $debugMatchOutput = "2. Volume ##, # elsewhere";
            }
            // search for the word Chapter ##-##
            else if(preg_match_all("/(Chapters|Chapter|Capitulos|Capitulo|Chapitres|Chapitre|\bChap|\bCh|\bC)[$seps]?(\d+)[$seps]?(-|to|thru|through)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Chapter
                $debugMatchOutput = "2. Chapters ##-##";
            }
            // search for the word Chapter, Capitulo
            else if(preg_match_all("/(Chapter|Capitulo|Chapitre|\bChap|\bCh|\bC)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Chapter
                $debugMatchOutput = "2. Chapter ##, # elsewhere";
            }
            // search for the word Movie
            else if(preg_match_all("/(Movie|\bMov)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Movie
                $debugMatchOutput = "2. Movie ##, # elsewhere";
            }
            // search for the word Film
            else if(preg_match_all("/(Film|\bF)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Film
                $debugMatchOutput = "2. Film ##, # elsewhere";
            }
            // search for the word Part
            else if(preg_match_all("/(Part|\bPt)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Part
                $debugMatchOutput = "2. Part ##, # elsewhere";
            }
            // search for unlabeled Season ## before the word Episode
            else if(preg_match_all("/\b(\d+)[$seps]?\-?[$seps]?(Episode|\bEpis|\bEp|\bE)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // should not be any mention of Season ## before Episode ## because of entire section far above searching for the word Season
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][3];
                $debugMatchOutput = "2. isolated SS - Episode ##";
            }
            // search for the word Episode
            else if(preg_match_all("/(Episode|\bEpis|\bEp|\bE)[$seps]?(\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // should not be any mention of Season ## before Episode ## because of prior match
                $detSeasBatchEnd = $detSeasBatchStart = 1;
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $debugMatchOutput = "2. Episode ##, # elsewhere";
            }
            else if(preg_match_all("/\x{7B2C}(\d+)[$seps]?-[$seps]?(\d+)\x{5dfb}/u", $ti, $matches, PREG_SET_ORDER)) {
                $detMediaType = 4;
                $numberSequence = 4;
                $detSeasBatchEnd = mb_convert_kana($matches[0][2], "a", "UTF-8");
                $detSeasBatchStart = mb_convert_kana($matches[0][1], "a", "UTF-8");
                $detEpisBatchEnd = $detEpisBatchStart = 0;
                $debugMatchOutput = "2. Japanese ##-## Print Media Books/Volumes";
            }
            else if(preg_match_all("/\x{7B2C}(\d+)(\x{8a71}|\x{8bdd})/u", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1;
                $detEpisBatchEnd = $detEpisBatchStart = mb_convert_kana($matches[0][1], "a", "UTF-8");
                $debugMatchOutput = "2. Japanese ## Episode, ## elsewhere";
            }
            else if(preg_match_all("/\x{7B2C}(\d+)\x{5dfb}/u", $ti, $matches, PREG_SET_ORDER)) {
                $detMediaType = 4;
                $numberSequence = 4;
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = 0;
                $debugMatchOutput = "2. Japanese ## Print Media Book/Volume, ## elsewhere";
            }
            // Japanese YYYY MM
            else if(preg_match_all("/(\b|\D)(\d{2}|\d{4})\x{5e74}?[\-$seps]?(\d{1,2})\x{6708}?\x{53f7}/u", $ti, $matches, PREG_SET_ORDER)) {
                if(strlen($matches[0][3]) == 1) {
                    $matches[0][3] = '0' . $matches[0][3];
                }
                $detMediaType = 4;
                $numberSequence = 2;
                $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2] . $matches[0][3];
                $debugMatchOutput = "2. Japanese YYYY MM Print Media";
            }
            // #nd EE
            else if(preg_match_all("/\b(\d{1,2})(nd|th|st)[$seps]?(\d{1,2})\b/i", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][3];
                $debugMatchOutput = "2. #nd EE";
            }
            // isolated ###.#
            else if(preg_match_all("/\b(\d{1,3}\.\d)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1; // if no mention of Season, assume Season 1
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                $debugMatchOutput = "2. isolated ###.#";
            }
            // isolated YYYY-MM
            else if(preg_match_all("/\b(\d{4})[-$seps](\d{1,2})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if(checkdate($matches[0][2] + 0, 1, $matches[0][1] + 0) && $matches[0][1] + 0 <= getdate()['year'] && $matches[0][1] + 0 > 1895) {
                    // YYYY-MM
                    if(strlen($matches[0][2]) == 1) {
                        $matches[0][2] = "0" . $matches[0][2];
                    }
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1] . $matches[0][2];
                    $debugMatchOutput = "2. isolated YYYY-MM";
                }
                else {
                    // #### is probably part of the title
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "2. title #### - EE";
                }
            }
            // isolated MM-YYYY
            else if(preg_match_all("/\b(\d{1,2})[-$seps](\d{4})\b/", $ti, $matches, PREG_SET_ORDER)) {
               if(checkdate($matches[0][1] + 0, 1, $matches[0][2] + 0) && $matches[0][2] + 0 <= getdate()['year'] && $matches[0][2] + 0 > 1895) {
                    // MM-YYYY
                    if(strlen($matches[0][1]) == 1) {
                        $matches[0][1] = "0" . $matches[0][1];
                    }
                    $numberSequence = 2;
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2] . $matches[0][1];
                    $debugMatchOutput = "2. isolated MM-YYYY";
                }
                //TODO handle else
            }
            // (YYYY) - EE
            else if(preg_match_all("/\((\d{4})\)[$seps]?-?[$seps]?(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if($matches[0][1] + 0 <= getdate()['year'] && $matches[0][1] + 0 > 1895) {
                    // (YYYY) - EE
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1]; // Half date notation and half episode: let Season = YYYY
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "2. (YYYY) - EE";
                }
                else {
                    // #### is probably part of the title
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "2. title (####) - EE";
                }
            }
            // isolated No.##-No.##
            else if(preg_match_all("/\b(Num|No\.|No)[$seps]?(\d+)[$seps]?(-|to|thru|through)[$seps]?(Num|No\.|No|)[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                $detMediaType = 4;
                $numberSequence = 4;
                $detSeasBatchEnd = $matches[0][5];
                $detSeasBatchStart = $matches[0][2];
                $detEpisBatchEnd = $detEpisBatchStart = 0;
                $debugMatchOutput = "2. isolated No.##-No.##, Print Media Book/Volume";
            }
            // isolated S1 #10
            else if(preg_match_all("/\b(s|S)(\d{1,2})[$seps]?#(\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][3];
                $debugMatchOutput = "2. isolated S1 #10";
            }
            // isolated ## to ##
            else if(preg_match_all("/\b(\d{1,3})[$seps]?(through|thru|to|\x{e0})[$seps]?(\d{1,3})\b/iu", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                $detEpisBatchEnd = $matches[0][3];
                $detEpisBatchStart = $matches[0][1];
                $debugMatchOutput = "2. ## to ##";
            }
            // isolated ##-##
            else if(preg_match_all("/\b(\d{1,3})[$seps]?\-[$seps]?(\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                // MUST keep first ### less than 4 digits to prevent Magic Kaito 1412 - EE from matching
                if(substr($matches[0][2], 0, 1) == '0' && substr($matches[0][1], 0, 1) != '0') {
                    // certainly S - EE
                    // Examples:
                    // Sword Art Online 2 - 07
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "2. isolated S - 0E";
                }
                else if($matches[0][1] == 1) {
                    // probably EE - EE, since people rarely refer to Season 1 without mentioning Season|Seas|Sea|Se|S
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                    $detEpisBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $matches[0][2];
                    $debugMatchOutput = "2. isolated 1 - EE";
                }
                else if(
                        substr($matches[0][1], 0, 1) == '0' ||
                        (strlen($matches[0][1]) > 1 && strlen($matches[0][2]) - strlen($matches[0][1]) < 2 && $matches[0][1] + 0  < $matches[0][2] + 0)
                        ) {
                    // if leading digit of first ## is 0 or
                    // it's more than 1 digit and second ## is no more than 1 digit longer and second ## is greater than first ##,
                    // it's probably not a season but EE - EE, such as 09 - 11
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                    $detEpisBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $matches[0][2];
                    $debugMatchOutput = "2. isolated EE - EE";
                }
                else {
                    // assume S - EE
                    // Examples:
                    // 3 - 17
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "2. isolated S - EE";
                }
            }
            // isolated SS EE
            else if(preg_match_all("/\b(\d{1,3})[$seps](\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if(
                        $matches[0][1] < $matches[0][2] &&
                        $matches[0][1] < 6 && // most cours never pass 5
                        $matches[0][2] - $matches[0][1] < 15
                        ) {
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "2. isolated SS EE";
                }
                else {
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Seasons 1
                    $detEpisBatchEnd = $matches[0][2];
                    $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "2. isolated EE EE";
                }
            }
            // isolated ###
            else if(preg_match_all("/\b(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if($matches[0][1] > 0) {
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // if no mention of Season, assume Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "2. isolated ###, # elsewhere";
                }
                else {
                    $debugMatchOutput = "2. isolated ### = 0, # elsewhere";
                }
            }
        }
        else if(!isset($matchesNumbers[3])) {
            // three numbers found, use regex
            $debugMatchOutput = "3.";
            //TODO handle the decimal episodes here like S##E##.# and so on
            //TODO remove numbers embedded in the middle of words (common with crew names)

            // go straight for YYYY MM DD
            if(preg_match_all("/\b(\d{4})[$seps\-](\d{1,2})[$seps\-](\d{1,2})\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1] . $matches[0][2] . $matches[0][3];
                $debugMatchOutput = "3. isolated YYYY MM DD";
            }
            // go straight for S##E## - E##
            else if(preg_match_all("/\b[Ss](\d+)[Ee](\d+)[$seps]?\-?[$seps]?[Ee](\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchStart = $matches[0][2];
                $detEpisBatchEnd = $matches[0][3];
                $debugMatchOutput = "3. isolated S##E## - E## range";
            }
            // go straight for S##E## (must be preceded by S##E## - E##)
            else if(preg_match_all("/\b[Ss](\d+)[Ee](\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $debugMatchOutput = "3. isolated S##E##, # elsewhere";
            }
            // isolated ##x##
            else if(preg_match_all("/\b(\d+)[$seps]?[xX][$seps]?(\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                // search for explicit SSxEE notation
                //TODO make sure x264 doesn't match
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $debugMatchOutput = "3. isolated SSxEE, # elsewhere";
            }
            // S2 - ###.#
            else if(preg_match_all("/\bS(\d+)[$seps]?\-[$seps]?(\d{1,3}\.\d|\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $debugMatchOutput = "3. isolated S# - ###.#";
            }
            // S2 - ### - ### (keep just before S2 - ###, # elsewhere)
            else if(preg_match_all("/\bS(\d+)[$seps]?\-?[$seps]?\b(\d{1,3})\b[$seps]?\-?[$seps]?\b(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if($matches[0][3] > $matches[0][2]) {
                    // probably range of Episodes within one Season
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchStart = $matches[0][2];
                    $detEpisBatchEnd = $matches[0][3];
                    $debugMatchOutput = "3. isolated S# - EE - EE";
                }
                else {
                    // not sure what it is, probably extra number on end
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "3. isolated S# - EE, extra ##";
                }
            }
            // S2 - ###, # elsewhere (must be preceded by S2 - ### - ### to trap Episode range)
            else if(preg_match_all("/\bS(\d+)[$seps]?\-?[$seps]?(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $debugMatchOutput = "3. isolated S# - ###, # elsewhere";
            }
            // Japanese YYYY MM DD
            else if(preg_match_all("/(\b|\D)(\d{2}|\d{4})\x{5e74}?\-?(\d{1,2})\x{6708}?\-?(\d+)\x{65e5}?\x{53f7}/u", $ti, $matches, PREG_SET_ORDER)) {
                if(strlen($matches[0][3]) == 1) {
                    $matches[0][3] = "0" . $matches[0][3];
                }
                if(strlen($matches[0][4]) == 1) {
                    $matches[0][4] = "0" . $matches[0][4];
                }
                $detMediaType = 4;
                $numberSequence = 2;
                $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2] . $matches[0][3] . $matches[0][4];
                $debugMatchOutput = "3. Japanese YYYY MM DD Print Media";
            }
            // Japanese YYYY MM
            else if(preg_match_all("/(\b|\D)(\d{2}|\d{4})\x{5e74}?\-?(\d+)\x{53f7}/u", $ti, $matches, PREG_SET_ORDER)) {
                if(strlen($matches[0][3]) == 1) {
                    $matches[0][3] = "0" . $matches[0][3];
                }
                $detMediaType = 4;
                $numberSequence = 2;
                $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2] . $matches[0][3];
                $debugMatchOutput = "3. Japanese YYYY MM Print Media, # elsewhere";
            }
            else if(preg_match_all("/(Season|Saison|Seizoen|Sezona|Seas\b|\bSe\b|\bS\b)[$seps]?(\d+\.\d|\d+)[$seps]?[\,\-]?[$seps]?(Episode|Epizode|\bEpis\b|\bEpi\b|\bEp\b|\bE\b|Capitulo|)[$seps]?(\d+\.\d|\d+)/i", $ti, $matches, PREG_SET_ORDER)) {
                // search for explicit S##.#E###.#
                // Example:
                // S01.5E10.5
                // Season 1 Episode 10
                // Se.2.Ep.5
                // Seas 2, Epis 3
                // S3 - E6
                // S2 - 32 (NOTE: passed S1 - ### short-circuit above, so assume Season 2, Episode 32)
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][4];
                $debugMatchOutput = "3. S##.#E###.#";
            }
            else if(preg_match_all("/(\d{4})\.(\d+\.\d|\d+)[$seps]?[xX][$seps]?(\d+\.\d|\d+)/", $ti, $matches, PREG_SET_ORDER) && preg_match("/^[12]+/", $matches[0][1])) {
                // short-circuit YYYY.SSxEE so that "Doctor.Who.2005.8x10.In" doesn't match later
                if(count($matches) > 1) {
                    // more than one YYYY.SSxEE found--probably a range
                    //TODO handle range
                    $debugMatchOutput = "3. YYYY.SSxEE range";
                }
                else {
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][2];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][3];
                    $debugMatchOutput = "3. YYYY.SSxEE";
                }
            }
            else if(preg_match_all("/(\d{4})[$seps](\d{1,2})[$seps](\d{1,2})/", $ti, $matches, PREG_SET_ORDER)) {
                // search for explicit YYYY MM DD or YYYY M D but not YYYYMMDD
                if(count($matches) > 1) {
                    // more than one YYYY MM DD found--probably a range
                    //TODO handle range of dates
                    $debugMatchOutput = "3. YYYY MM DD or YYYY M D but not YYYYMMDD range";
                }
                else {
                    //TODO make sure YYYY MM and DD make sense
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1] . $matches[0][2] . $matches[0][3];
                    $debugMatchOutput = "3. YYYY MM DD or YYYY M D but not YYYYMMDD";
                }
            }
            else if(preg_match_all("/(\d{4})(\d{2})(\d{2})/", $ti, $matches, PREG_SET_ORDER) && preg_match("/^[12]+/", $matches[0][1])) {
                // search for explicit YYYYMMDD (must have two-digit MM and DD or could be MDD or MMD)
                // check that YYYY begins with 1 or 2 (no year 3000!)
                if(count($matches) > 1) {
                    // more than one YYYY MM DD found--probably a range
                    //TODO handle range of dates
                    $debugMatchOutput = "3. YYYY MM DD range";
                }
                else {
                    //TODO make sure YYYY MM and DD make sense
                    $detSeasBatchEnd = $detSeasBatchStart = 0; // date notation gets Season 0
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1] . $matches[0][2] . $matches[0][3];
                    $debugMatchOutput = "3. YYYY MM DD";
                }
            }
            else if(preg_match_all("/(\d+\.\d|\d+)[$seps](Episode|Epis|Epi|Ep|E)[$seps]?(\d+\.\d|\d+)[\(\)$seps]/i", $ti, $matches, PREG_SET_ORDER)) {
                // search for ## Episode ## but not ##E## (no mention of Season, as that would have matched earlier, must have space to block checksum matches)
                if(count($matches) > 1) {
                    // more than one found--probably a range
                    //TODO handle range of Episodes
                    $debugMatchOutput = "3. ## Episode ## but not ##E## range";
                }
                else {
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][3];
                    $debugMatchOutput = "3. ## Episode ## but not ##E##";
                }
            }
            // search for ## Episode ## but not ##E## at very end of title
            else if(preg_match_all("/(\d+\.\d|\d+)[$seps](Episode|Epis|Epi|Ep|E)[$seps]?(\d+\.\d|\d+)$/i", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][3];
                $debugMatchOutput = "3. ## Episode ## but not ##E## at end of title";
            }
            // search for Episode ##-## (no mention of Season, as that would have matched earlier)
            else if(preg_match_all("/[\(\)$seps](Episodes|Episode|Epis|Epi|Ep|E)[$seps]?(\d+\.\d|\d+)[\(\)$seps]?\-[$seps]?(\d{1,3}\.\d|\d{1,3})/i", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                $detEpisBatchStart = $matches[0][2];
                $detEpisBatchEnd = $matches[0][3];
                $debugMatchOutput = "3. Episode ## - ##, # elsewhere";
            }
            else if(preg_match_all("/[\(\)$seps](Episode|Epis|Epi|Ep|E)[$seps]?(\d+\.\d|\d+)[\(\)$seps]?/i", $ti, $matches, PREG_SET_ORDER)) {
                // search for Episode ## (no mention of Season, as that would have matched earlier)
                if(count($matches) > 1) {
                    // more than one found--probably a range
                    //TODO handle range of Episodes
                    $debugMatchOutput = "3. Episode ## range, # elsewhere";
                }
                else {
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "3. Episode ##, #s elsewhere";
                }
            }
            else if(preg_match_all("/(\d{1,3}\.\d|\d{1,3})[$seps]?(through|thru|to)[$seps]?(\d{1,3}\.\d|\d{1,3})/i", $ti, $matches, PREG_SET_ORDER)) {
                // search for ###.# to ###.# (assume episodes here, because search for multiple seasons happened earlier)
                //TODO fix it so that 2005.08 to 50 doesn't match
                $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                $detEpisBatchStart = $matches[0][1];
                $detEpisBatchEnd = $matches[0][3];
                $debugMatchOutput = "3. ###.# to ###.# episodes";
            }
            //TODO handle YYYY EE - EE            
            // (YYYY) - EE (EEE) (must precede (YYYY) - EE)
            else if(preg_match_all("/\((\d{4})\)[$seps]?\-?[$seps]?(\d{1,3})[$seps]?\-?[$seps]?\((\d{1,4})\)/", $ti, $matches, PREG_SET_ORDER)) {
                if($matches[0][1] + 0 <= getdate()['year'] && $matches[0][1] + 0 > 1895) {
                    // (YYYY) - EE
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1]; // Half date notation and half episode: let Season = YYYY
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "3. (YYYY) - EE (EEE)";
                }
                //TODO handle else
            }
            // (YYYY) - EE
            else if(preg_match_all("/\((\d{4})\)[$seps]?-?[$seps]?(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if($matches[0][1] + 0 <= getdate()['year'] && $matches[0][1] + 0 > 1895) {
                    // (YYYY) - EE
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1]; // Half date notation and half episode: let Season = YYYY
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "3. (YYYY) - EE, # elsewhere";
                }
                //TODO handle else
            }
            // SS \b##v#\b (Season ## Episode ## Version #)
            else if(preg_match_all("/\b(\d+)\b[$seps]?\-?[$seps]?\b(\d+)[$seps]?\-?[$seps]?(v|V)(\d{1,2})\b/", $ti, $matches, PREG_SET_ORDER)) {
                //TODO might need to move this below other superset matches
                $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                $itemVersion = $matches[0][4];
                $debugMatchOutput = "3. isolated SS ##v#";
            }
            //\b##v#\b (Episode ## Version #)
            else if(preg_match_all("/\b(\d+)[$seps]?\-?[$seps]?(v|V)(\d{1,2})\b/", $ti, $matches, PREG_SET_ORDER)) {
                //TODO might need to move this below other superset matches
                $detSeasBatchEnd = $detSeasBatchStart = 1;
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                $itemVersion = $matches[0][3];
                $debugMatchOutput = "3. isolated ##v#, # elsewhere";
            }
            // #### EE - EE 
            else if(preg_match_all("/\b(\d{1,4})[$seps]?\-?[$seps]?\b(\d{1,3})[$seps]?\-[$seps]?(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                if($matches[0][2] < $matches[0][3]) {
                    // could be #### EE - EE
                    if(strlen($matches[0][1]) > 2) {
                        // first ### is probably not a season and is probably part of the title
                        $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                        $detEpisBatchStart = $matches[0][2];
                        $detEpisBatchEnd = $matches[0][3];
                        $debugMatchOutput = "3. #### EE - EE";
                    }
                    else {
                        // assume SS EE - EE (not the same as S2 EE - EE handled far above, because letter S is not specified)
                        $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                        $detEpisBatchStart = $matches[0][2];
                        $detEpisBatchEnd = $matches[0][3];
                        $debugMatchOutput = "3. SS EE - EE";
                    }
                }
                else {
                    // not sure what it is
                    $debugMatchOutput = "3. unidentifiable ## ## - ##";
                }
            }
            // ## - ##, # elsewhere (must be preceded by SS EE - EE)
            else if(preg_match_all("/\b(\d{1,3})\b[$seps]?\-?[$seps]?\b(\d{1,3})\b/", $ti, $matches, PREG_SET_ORDER)) {
                // search for ## - ##, is it SS - EE or EE - EE? Make sure EE - EE first EE < second EE
                if($matches[0][1] >= $matches[0][2]) {
                    // probably SS - EE, not EE - EE
                    $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];
                    $debugMatchOutput = "3. SS - EE, # elsewhere";
                }
                else {
                    if(substr($matches[0][2], 0, 1) == '0' && substr($matches[0][1], 0, 1) != '0') {
                        // almost certainly S - EE, not EE - EE
                        $detSeasBatchEnd = $detSeasBatchStart = $matches[0][1];
                        $detEpisBatchEnd = $detEpisBatchStart = $matches[0][2];                    
                        $debugMatchOutput = "3. S - EE, # elsewhere";                       
                    }
                    else {
                        // probably EE - EE
                        $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1
                        $detEpisBatchStart = $matches[0][1];
                        $detEpisBatchEnd = $matches[0][2];
                        $debugMatchOutput = "3. EE - EE, # elsewhere";
                    }
                }
            }
            // search for the word Volume, Volumen, Chapter, Capitulo
            else if(preg_match_all("/(Volumen|Volume|\bVol|\bV\.)[$seps]?(\d+)[$seps]?(Capitulo|Chapter|Chap|\bCh|\bC\.)[$seps]?(\d+)\b/i", $ti, $matches, PREG_SET_ORDER)) {
                //TODO handle Volume
                $debugMatchOutput = "3. Volume ##, Chapter ##, # elsewhere";
            }
            // SAVE THE BELOW FOR THE END, BECAUSE SINGLE NUMBERS CAN MATCH SO MANY LONGER PATTERNS
            else if(preg_match_all("/\D[$seps]?\-[$seps]?(\d{1,3}\.\d|\d{1,3})/", $ti, $matches, PREG_SET_ORDER)) {
                // search for - ##, not ## - ## (which is matched earlier)
                if(count($matches) > 1) {
                    // more than one found--not likely to ever happen, since this should be matched as a range earlier
                    //TODO handle range of episodes
                    $debugMatchOutput = "3. - ##, not ## - ##, range";
                }
                else {
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "3. - ##, not ## - ##";
                }
            }
            else if(preg_match_all("/[\(\)$seps](\d{1,3}\.\d|\d{1,3})[\(\)$seps]/", $ti, $matches, PREG_SET_ORDER)) {
                // search for 1 to 3-digit numbers with at most tenths place (last resort, otherwise may match earlier, longer strings)
                if(count($matches) > 1) {
                    // more than one ###.# found--probably a range
                    //TODO handle range of episodes
                    $debugMatchOutput = "3. ###.# range";
                }
                else {
                    //TODO make sure this is not part of a date!
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "3. ###.#";
                }
            }
            // isolated ###.#, # elsewhere
            else if(preg_match_all("/\b(\d{1,3}\.\d|\d+)\b/", $ti, $matches, PREG_SET_ORDER)) {
                $detSeasBatchEnd = $detSeasBatchStart = 1;
                $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                $debugMatchOutput = "3. isolated ###.#, # elsewhere";
            }
            else if(preg_match_all("/[\(\)$seps](\d{1,3}\.\d|\d{1,3})$/", $ti, $matches, PREG_SET_ORDER)) {
                // search for 1 to 3-digit numbers with at most tenths place (last resort, otherwise may match earlier, longer strings)
                // searches at the very end of the title
                if(count($matches) > 1) {
                    // more than one ###.# found--probably a range
                    //TODO handle range of episodes
                    $debugMatchOutput = "3. ###.# at end, range";
                }
                else {
                    //TODO make sure this is not part of a date!
                    $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                    $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                    $debugMatchOutput = "3. ###.# at end";
                }
            }
            // HANDLE 4-DIGIT NUMBERS BY CHECKING THAT THEY ARE NOT YYYY OR MMDD
            else if(preg_match_all("/[\(\)$seps](\d{4}\.\d|\d{4})[\(\)$seps]/", $ti, $matches, PREG_SET_ORDER)) {
                // search for 4-digit numbers with at most tenths place (very last resort, otherwise may short-circuit earlier matches)
                if(count($matches) > 1) {
                    // more than one ####.# found--probably a range
                    //TODO handle range of dates or episodes
                    $debugMatchOutput = "3. ####.# range";
                }
                else {
                    // invention of moving pictures on film was in 1896
                    if($matches[0][1] > 1895) {
                        // probably YYYY
                        $detSeasBatchEnd = $detSeasBatchStart = 0; // date-notation gets Season 0
                        $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                        $debugMatchOutput = "3. YYYY";
                    }
                    else if(substr($matches[0][1], 0, 1) == '0') {
                        // probably MMDD
                        $detSeasBatchEnd = $detSeasBatchStart = 0; // date-notation gets Season 0
                        $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                        $debugMatchOutput = "3. MMDD";
                    }
                    else {
                        // probably anime episode between 1000 and 1895, could be MMDD
                        $detSeasBatchEnd = $detSeasBatchStart = 1; // anime episode notation gets Season 1
                        $detEpisBatchEnd = $detEpisBatchStart = $matches[0][1];
                        $debugMatchOutput = "3. ####";
                    }
                }
            }
        }
        else if(!isset($matchesNumbers[4])) {
            // four numbers found
            $debugMatchOutput = "4.";
        }
        else {
            // five or more numbers found, ignore
            $debugMatchOutput = "5.";
        } // end if(!isset($matchesNumbers[1]))

        // trim off leading zeroes
        if($detEpisBatchEnd != '') {
            $detEpisBatchEnd += 0;
        }
        if($detEpisBatchStart != '') {
            $detEpisBatchStart += 0;
        }
        if($detSeasBatchEnd != '') {
            $detSeasBatchEnd += 0;
        }
        if($detSeasBatchStart != '') {
            $detSeasBatchStart +=0;
        }
    }
    else {
        // handle no-numeral episodes
        // search for the isolated word Special
        if(preg_match_all("/(\bSpecial|\bSpec)[$seps]?/i", $ti, $matches, PREG_SET_ORDER)) {
            $detClassification = 16;
            $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1 since no number was provided
            $detEpisBatchEnd = $detEpisBatchStart = 1; // assume Special 1 since no number was provided
            $debugMatchOutput = "0. Special";
        }
        // search for the isolated word OVA
        else if(preg_match_all("/\bOVA[$seps]?/i", $ti, $matches, PREG_SET_ORDER)) {
            $detClassification = 32;
            $detSeasBatchEnd = $detSeasBatchStart = 1; // assume Season 1 since no number was provided
            $detEpisBatchEnd = $detEpisBatchStart = 1; // assume OVA 1 since no number was provided
            $debugMatchOutput = "0. OVA";
        }
    } //END if(isset($matchesNumbers[0]))

    //twxa_debug($debugMatchOutput . "\n"); //TODO set this up to obey $verbosity

    return [ 'detectedSeasonBatchStart' => $detSeasBatchStart,
        'detectedSeasonBatchEnd' => $detSeasBatchEnd,
        'detectedEpisodeBatchStart' => $detEpisBatchStart,
        'detectedEpisodeBatchEnd' => $detEpisBatchEnd,
        'mediaType' => $detMediaType,
        'itemVersion' => $itemVersion,
        'numberSequence' => $numberSequence,
        'debugMatch' => $debugMatchOutput
            ];
}
