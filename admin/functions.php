<?php
$rRelease = 14;

session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$rTimeout = 15;
set_time_limit($rTimeout);
ini_set('mysql.connect_timeout', $rTimeout);
ini_set('max_execution_time', $rTimeout);
ini_set('default_socket_timeout', $rTimeout);

define("MAIN_DIR", "/home/xtreamcodes/iptv_xtream_codes/");
define("CONFIG_CRYPT_KEY", "5709650b0d7806074842c6de575025b1");

require_once realpath(dirname(__FILE__))."/mobiledetect.php";
require_once realpath(dirname(__FILE__))."/gauth.php";

$detect = new Mobile_Detect;
$rStatusArray = Array(0 => "Stopped", 1 => "Running", 2 => "Starting", 3 => "<strong style='color:#cc9999'>DOWN</strong>", 4 => "On Demand", 5 => "Direct");
$rClientFilters = Array(
    "NOT_IN_BOUQUET" => "Not in Bouquet",
    "CON_SVP" => "Connection Issue",
    "ISP_LOCK_FAILED" => "ISP Lock Failed",
    "USER_DISALLOW_EXT" => "Extension Disallowed",
    "AUTH_FAILED" => "Authentication Failed",
    "USER_EXPIRED" => "User Expired",
    "USER_DISABLED" => "User Disabled",
    "USER_BAN" => "User Banned"
);

// Exec replacement for remote machines.
function sexec($rServerID, $rCommand) {
    global $rServers, $rSettings;
    $rAPI = "http://".$rServers[intval($rServerID)]["server_ip"].":".$rServers[intval($rServerID)]["http_broadcast_port"]."/system_api.php?password=".urlencode($rSettings["live_streaming_pass"])."&action=BackgroundCLI&cmds[]=".urlencode($rCommand);
    return file_get_contents($rAPI);
}

function listDir($rServerID, $rDirectory, $rAllowed=null) {
    global $rServers, $_INFO, $rSettings;
    set_time_limit(180);
    ini_set('max_execution_time', 180);
	$rReturn = Array("dirs" => Array(), "files" => Array());
    if ($rServerID == $_INFO["server_id"]) {
        $rFiles = scanDir($rDirectory);
        foreach ($rFiles as $rKey => $rValue) {
            if (!in_array($rValue, Array(".",".."))) {
                if (is_dir($rDirectory."/".$rValue)) {
                    $rReturn["dirs"][] = $rValue;
                } else {
                    $rExt = strtolower(pathinfo($rValue)["extension"]);
                    if (((is_array($rAllowed)) && (in_array($rExt, $rAllowed))) OR (!$rAllowed)) {
                        $rReturn["files"][] = $rValue;
                    }
                }
            }
        }
    } else {
        $rAPI = "http://".$rServers[intval($rServerID)]["server_ip"].":".$rServers[intval($rServerID)]["http_broadcast_port"]."/system_api.php?password=".urlencode($rSettings["live_streaming_pass"])."&action=viewDir&dir=".urlencode($rDirectory);
        $rData = file_get_contents($rAPI);
        $rDocument = new DOMDocument();
        $rDocument->loadHTML($rData);
        $rFiles = $rDocument->getElementsByTagName('li');
        foreach($rFiles as $rFile) {
            if (stripos($rFile->getAttribute('class'), "directory") !== false) {
                $rReturn["dirs"][] = $rFile->nodeValue;
            } else if (stripos($rFile->getAttribute('class'), "file") !== false) {
                $rExt = strtolower(pathinfo($rFile->nodeValue)["extension"]);
                if (((is_array($rAllowed)) && (in_array($rExt, $rAllowed))) OR (!$rAllowed)) {
                    $rReturn["files"][] = $rFile->nodeValue;
                }
            }
        }
    }
    return $rReturn;
}

function getTimeDifference($rServerID) {
	global $rServers, $rSettings;
    $rAPI = "http://".$rServers[intval($rServerID)]["server_ip"].":".$rServers[intval($rServerID)]["http_broadcast_port"]."/system_api.php?password=".urlencode($rSettings["live_streaming_pass"])."&action=getDiff&main_time=".intval(time());
    return intval(file_get_contents($rAPI));
}

function deleteMovieFile($rServerID, $rID) {
	global $rServers, $rSettings;
    $rCommand = "rm ".MAIN_DIR."movies/".$rID.".*";
    $rAPI = "http://".$rServers[intval($rServerID)]["server_ip"].":".$rServers[intval($rServerID)]["http_broadcast_port"]."/system_api.php?password=".urlencode($rSettings["live_streaming_pass"])."&action=BackgroundCLI&cmds[]=".urlencode($rCommand);
    return file_get_contents($rAPI);
}

function generateString($strength = 10) {
    $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
    return $random_string;
}

function xor_parse($data, $key) {
    $i = 0;
    $output = '';
    foreach (str_split($data) as $char) {
        $output.= chr(ord($char) ^ ord($key[$i++ % strlen($key)]));
    }
    return $output;
}

function getTimezone() {
    global $db;
    $result = $db->query("SELECT `default_timezone` FROM `settings`;");
    if ((isset($result)) && ($result->num_rows == 1)) {
        return $result->fetch_assoc()["default_timezone"];
    } else {
        return "Europe/London";
    }
}

$_INFO = json_decode(xor_parse(base64_decode(file_get_contents(MAIN_DIR . "config")), CONFIG_CRYPT_KEY), True);
if (!$db = new mysqli($_INFO["host"], $_INFO["db_user"], $_INFO["db_pass"], $_INFO["db_name"], $_INFO["db_port"])) { exit("No MySQL connection!"); } 
$db->set_charset("utf8");
date_default_timezone_set(getTimezone());

function getStreamingServers() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `streaming_servers` ORDER BY `id` ASC;");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $return[$row["id"]] = $row;
        }
    }
    return $return;
}

function getStreamingServersByID($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `streaming_servers` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getSettings() {
    global $db;
    $result = $db->query("SELECT * FROM `settings` LIMIT 1;");
    return $result->fetch_assoc();
}

function getStreamList() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT `streams`.`id`, `streams`.`stream_display_name`, `stream_categories`.`category_name` FROM `streams` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` ORDER BY `streams`.`stream_display_name` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[] = $row;
        }
    }
    return $return;
}

function getStreams($category_id=null, $full=false, $stream_ids=null) {
    global $db;
    $return = Array();
    if ($stream_ids) {
        $result = $db->query("SELECT * FROM `streams` WHERE `type` = 1 AND `id` IN (".join(",", $stream_ids).") ORDER BY `id` ASC;");
    } else {
        if ($category_id) {
            $result = $db->query("SELECT * FROM `streams` WHERE `type` = 1 AND `category_id` = ".intval($category_id)." ORDER BY `id` ASC;");
        } else {
            $result = $db->query("SELECT * FROM `streams` WHERE `type` = 1 ORDER BY `id` ASC;");
        }
    }
    $stream_ids = Array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($full) {
                $return[] = $row;
            } else {
                $return[] = Array("id" => $row["id"]);
            }
            $stream_ids[] = $row["id"];
        }
    }
    $streams_sys = Array();
    $result = $db->query("SELECT * FROM `streams_sys` WHERE `stream_id` IN (".join(",", $stream_ids).");");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $streams_sys[intval($row["stream_id"])][intval($row["server_id"])] = $row;
        }
    }
    $activity = Array();
    $result = $db->query("SELECT `stream_id`, `server_id`, COUNT(`activity_id`) AS `active` FROM `user_activity_now` WHERE `stream_id` IN (".join(",", $stream_ids).") GROUP BY `stream_id`, `server_id`;");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activity[intval($row["stream_id"])][intval($row["server_id"])] = $row["active"];
        }
    }
    if (count($return) > 0) {
        foreach (range(0, count($return)-1) as $i) {
            $return[$i]["servers"] = Array();
            foreach($streams_sys[intval($return[$i]["id"])] as $rServerID => $rStreamSys) {
                $rServerArray = Array("server_id" => $rServerID);
                if (isset($activity[intval($return[$i]["id"])][$rServerID])) {
                    $rServerArray["active_count"] = $activity[intval($return[$i]["id"])][$rServerID];
                } else {
                    $rServerArray["active_count"] = 0;
                }
                $rServerArray["uptime"] = 0;
                if (intval($return[$i]["direct_source"]) == 1) {
                    // Direct
                    $rServerArray["actual_status"] = 5;
                } else if ($rStreamSys["monitor_pid"]) {
                    // Started
                    if (($rStreamSys["pid"]) && ($rStreamSys["pid"] > 0)) {
                        // Running
                        $rServerArray["actual_status"] = 1;
                        $rServerArray["uptime"] = time() - intval($rStreamSys["stream_started"]);
                    } else {
                        if (intval($rStreamSys["stream_status"]) == 0) {
                            // Starting
                            $rServerArray["actual_status"] = 2;
                        } else {
                            // Stalled
                            $rServerArray["actual_status"] = 3;
                        }
                    }
                } else if (intval($rStreamSys["on_demand"]) == 1) {
                    // On Demand
                    $rServerArray["actual_status"] = 4;
                } else {
                    // Stopped
                    $rServerArray["actual_status"] = 0;
                }
                $rServerArray["current_source"] = $rStreamSys["current_source"];
                $rServerArray["uptime_text"] = sprintf('%02dh %02dm %02ds', ($rServerArray["uptime"]/3600),($rServerArray["uptime"]/60%60), ($rServerArray["uptime"]%60));
                $rServerArray["on_demand"] = $rStreamSys["on_demand"];
                $rStreamInfo = json_decode($rStreamSys["stream_info"], True);
                $rServerArray["stream_text"] = "<div style='font-size: 12px; text-align: center;'>Not Available</div>";
                if ($rServerArray["actual_status"] == 1) {
                    if (!isset($rStreamInfo["codecs"]["video"])) {
                        $rStreamInfo["codecs"]["video"] = "N/A";
                    }
                    if (!isset($rStreamInfo["codecs"]["audio"])) {
                        $rStreamInfo["codecs"]["audio"] = "N/A";
                    }
                    if ($rStreamSys['bitrate'] == 0) { 
                        $rStreamSys['bitrate'] = "?";
                    }
                    $rServerArray["stream_text"] = "<div style='font-size: 12px; text-align: center;'>
                        <div class='row'>
                            <div class='col'>".$rStreamSys['bitrate']." Kbps</div>
                            <div class='col' style='color: #20a009;'><i class='mdi mdi-video' data-name='mdi-video'></i></div>
                            <div class='col' style='color: #20a009;'><i class='mdi mdi-volume-high' data-name='mdi-volume-high'></i></div>
                        </div>
                        <div class='row'>
                            <div class='col'>".$rStreamInfo["codecs"]["video"]["width"]." x ".$rStreamInfo["codecs"]["video"]["height"]."</div>
                            <div class='col'>".$rStreamInfo["codecs"]["video"]["codec_name"]."</div>
                            <div class='col'>".$rStreamInfo["codecs"]["audio"]["codec_name"]."</div>
                        </div>
                    </div>";
                }
                $return[$i]["servers"][] = $rServerArray;
            }
        }
    }
    return $return;
}

function getConnections($rServerID) {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `user_activity_now` WHERE `server_id` = '".$db->real_escape_string($rServerID)."';");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[] = $row;
        }
    }
    return $return;
}

function getUserConnections($rUserID) {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `user_activity_now` WHERE `user_id` = '".$db->real_escape_string($rUserID)."';");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[] = $row;
        }
    }
    return $return;
}

function getEPGSources() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `epg`;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[$row["id"]] = $row;
        }
    }
    return $return;
}

function findEPG($rEPGName) {
    global $db;
    $result = $db->query("SELECT `id`, `data` FROM `epg`;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            foreach (json_decode($row["data"], True) as $rChannelID => $rChannelData) {
                if ($rChannelID == $rEPGName) {
                    if (count($rChannelData["langs"]) > 0) {
                        $rEPGLang = $rChannelData["langs"][0];
                    } else {
                        $rEPGLang = "";
                    }
                    return Array("channel_id" => $rChannelID, "epg_lang" => $rEPGLang, "epg_id" => intval($row["id"]));
                }
            }
        }
    }
    return null;
}

function getStreamArguments() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `streams_arguments` ORDER BY `id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[$row["argument_key"]] = $row;
        }
    }
    return $return;
}

function getTranscodeProfiles() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `transcoding_profiles` ORDER BY `profile_id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[] = $row;
        }
    }
    return $return;
}

function getStream($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `streams` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getUser($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `users` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getRegisteredUser($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `reg_users` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getRegisteredUserHash($rHash) {
    global $db;
    $result = $db->query("SELECT * FROM `reg_users` WHERE MD5(`username`) = '".$db->real_escape_string($rHash)."' LIMIT 1;");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getEPG($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `epg` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getStreamOptions($rID) {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `streams_options` WHERE `stream_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["argument_id"])] = $row;
        }
    }
    return $return;
}

function getStreamSys($rID) {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `streams_sys` WHERE `stream_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["server_id"])] = $row;
        }
    }
    return $return;
}

function getRegisteredUsers($rOwner=null, $rIncludeSelf=true) {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `reg_users` ORDER BY `username` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            if ((!$rOwner) OR ($row["owner_id"] == $rOwner) OR (($row["id"] == $rOwner) && ($rIncludeSelf))) {
                $return[intval($row["id"])] = $row;
            }
        }
    }
    if (count($return) == 0) { $return[-1] = Array(); }
    return $return;
}

function hasPermissions($rType, $rID) {
    global $rUserInfo, $db;
    if ($rType == "user") {
        if (in_array(intval(getUser($rID)["member_id"]), array_keys(getRegisteredUsers($rUserInfo["id"])))) {
            return true;
        }
    } else if ($rType == "pid") {
        $result = $db->query("SELECT `user_id` FROM `user_activity_now` WHERE `pid` = ".intval($rID).";");
        if (($result) && ($result->num_rows > 0)) {
            if (in_array(intval(getUser($result->fetch_assoc()["user_id"])["member_id"]), array_keys(getRegisteredUsers($rUserInfo["id"])))) {
                return true;
            }
        }
    } else if ($rType == "reg_user") {
        if ((in_array(intval($rID), array_keys(getRegisteredUsers($rUserInfo["id"])))) && (intval($rID) <> intval($rUserInfo["id"]))) {
            return true;
        }
    } else if ($rType == "ticket") {
        if (in_array(intval(getTicket($rID)["member_id"]), array_keys(getRegisteredUsers($rUserInfo["id"])))) {
            return true;
        }
    } else if ($rType == "mag") {
        $result = $db->query("SELECT `user_id` FROM `mag_devices` WHERE `mag_id` = ".intval($rID).";");
        if (($result) && ($result->num_rows > 0)) {
            if (in_array(intval(getUser($result->fetch_assoc()["user_id"])["member_id"]), array_keys(getRegisteredUsers($rUserInfo["id"])))) {
                return true;
            }
        }
    } else if ($rType == "e2") {
        $result = $db->query("SELECT `user_id` FROM `enigma2_devices` WHERE `device_id` = ".intval($rID).";");
        if (($result) && ($result->num_rows > 0)) {
            if (in_array(intval(getUser($result->fetch_assoc()["user_id"])["member_id"]), array_keys(getRegisteredUsers($rUserInfo["id"])))) {
                return true;
            }
        }
    }
    return false;
}

function getMemberGroups() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `member_groups` ORDER BY `group_id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["group_id"])] = $row;
        }
    }
    return $return;
}

function getMemberGroup($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `member_groups` WHERE `group_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return null;
}

function getRegisteredUsernames() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT `id`, `username` FROM `reg_users` ORDER BY `id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row["username"];
        }
    }
    return $return;
}

function getOutputs($rUser=null) {
    global $db;
    $return = Array();
    if ($rUser) {
        $result = $db->query("SELECT `access_output_id` FROM `user_output` WHERE `user_id` = ".intval($rUser).";");
    } else {
        $result = $db->query("SELECT * FROM `access_output` ORDER BY `access_output_id` ASC;");
    }
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            if ($rUser) {
                $return[] = $row["access_output_id"];
            } else {
                $return[] = $row;
            }
        }
    }
    return $return;
}

function getBouquets() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `bouquets` ORDER BY `id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row;
        }
    }
    return $return;
}

function getBouquet($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `bouquets` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return null;
}

function addToBouquet($rType, $rBouquetID, $rID) {
    global $db;
    $rBouquet = getBouquet($rBouquetID);
    if ($rBouquet) {
        if ($rType == "stream") {
            $rColumn = "bouquet_channels";
        } else {
            $rColumn = "bouquet_series";
        }
        $rChannels = json_decode($rBouquet[$rColumn], True);
        if (!in_array($rID, $rChannels)) {
            $rChannels[] = $rID;
            if (count($rChannels) > 0) {
                $db->query("UPDATE `bouquets` SET `".$rColumn."` = '".$db->real_escape_string(json_encode(array_values($rChannels)))."' WHERE `id` = ".intval($rBouquetID).";");
            }
        }
    }
}

function removeFromBouquet($rType, $rBouquetID, $rID) {
    global $db;
    $rBouquet = getBouquet($rBouquetID);
    if ($rBouquet) {
        if ($rType == "stream") {
            $rColumn = "bouquet_channels";
        } else {
            $rColumn = "bouquet_series";
        }
        $rChannels = json_decode($rBouquet[$rColumn], True);
        if (($rKey = array_search($rID, $rChannels)) !== false) {
            unset($rChannels[$rKey]);
            $db->query("UPDATE `bouquets` SET `".$rColumn."` = '".$db->real_escape_string(json_encode(array_values($rChannels)))."' WHERE `id` = ".intval($rBouquetID).";");
        }
    }
}

function getPackages() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `packages` ORDER BY `id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row;
        }
    }
    return $return;
}

function getPackage($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `packages` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return null;
}

function getTranscodeProfile($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `transcoding_profiles` WHERE `profile_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return null;
}

function getEPGs() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `epg` ORDER BY `id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row;
        }
    }
    return $return;
}

function getCategories($rType="live") {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `stream_categories` WHERE `category_type` = '".$db->real_escape_string($rType)."' ORDER BY `cat_order` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row;
        }
    }
    return $return;
}

function getChannels($rType="live") {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `stream_categories` WHERE `category_type` = '".$db->real_escape_string($rType)."' ORDER BY `cat_order` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row;
        }
    }
    return $return;
}

function getChannelsByID($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `streams` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return False;
}

function getMag($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `mag_devices` WHERE `mag_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        $row = $result->fetch_assoc();
        $result = $db->query("SELECT `pair_id` FROM `users` WHERE `id` = ".intval($row["user_id"]).";");
        if (($result) && ($result->num_rows == 1)) {
            $magrow = $result->fetch_assoc();
            $row["paired_user"] = $magrow["pair_id"];
            $row["username"] = getUser($row["paired_user"])["username"];
        }
        return $row;
    }
    return Array();
}

function getEnigma($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `enigma2_devices` WHERE `device_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        $row = $result->fetch_assoc();
        $result = $db->query("SELECT `pair_id` FROM `users` WHERE `id` = ".intval($row["user_id"]).";");
        if (($result) && ($result->num_rows == 1)) {
            $e2row = $result->fetch_assoc();
            $row["paired_user"] = $e2row["pair_id"];
            $row["username"] = getUser($row["paired_user"])["username"];
        }
        return $row;
    }
    return Array();
}

function getMAGUser($rID) {
    global $db;
    $result = $db->query("SELECT `mac` FROM `mag_devices` WHERE `user_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return base64_decode($result->fetch_assoc()["mac"]);
    }
    return "";
}

function getE2User($rID) {
    global $db;
    $result = $db->query("SELECT `mac` FROM `enigma2_devices` WHERE `user_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc()["mac"];
    }
    return "";
}

function getTicket($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `tickets` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows > 0)) {
        $row = $result->fetch_assoc();
        $row["replies"] = Array();
        $row["title"] = htmlspecialchars($row["title"]);
        $result = $db->query("SELECT * FROM `tickets_replies` WHERE `ticket_id` = ".intval($rID)." ORDER BY `date` ASC;");
        while ($reply = $result->fetch_assoc()) {
            // Hack to fix display issues on short text.
            $reply["message"] = htmlspecialchars($reply["message"]);
            if (strlen($reply["message"]) < 80) {
                $reply["message"] .= str_repeat("&nbsp; ", 80-strlen($reply["message"]));
            }
            $row["replies"][] = $reply;
        }
        $row["user"] = getRegisteredUser($row["member_id"]);
        return $row;
    }
    return null;
}

function getTickets($rID=null) {
    global $db;
    $return = Array();
    if ($rID) {
        $result = $db->query("SELECT `tickets`.`id`, `tickets`.`member_id`, `tickets`.`title`, `tickets`.`status`, `tickets`.`admin_read`, `tickets`.`user_read`, `reg_users`.`username` FROM `tickets`, `reg_users` WHERE `member_id` = ".intval($rID)." AND `reg_users`.`id` = `tickets`.`member_id` ORDER BY `id` DESC;");
    } else {
        $result = $db->query("SELECT `tickets`.`id`, `tickets`.`member_id`, `tickets`.`title`, `tickets`.`status`, `tickets`.`admin_read`, `tickets`.`user_read`, `reg_users`.`username` FROM `tickets`, `reg_users` WHERE `reg_users`.`id` = `tickets`.`member_id` ORDER BY `id` DESC;");
    }
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $dateresult = $db->query("SELECT MIN(`date`) AS `date` FROM `tickets_replies` WHERE `ticket_id` = ".intval($row["id"])." AND `admin_reply` = 0;");
            if ($rDate = $dateresult->fetch_assoc()["date"]) {
                $row["created"] = date("Y-m-d H:i", $rDate);
            } else {
                $row["created"] = "";
            }
            $dateresult = $db->query("SELECT MAX(`date`) AS `date` FROM `tickets_replies` WHERE `ticket_id` = ".intval($row["id"])." AND `admin_reply` = 1;");
            if ($rDate = $dateresult->fetch_assoc()["date"]) {
                $row["last_reply"] = date("Y-m-d H:i", $rDate);
            } else {
                $row["last_reply"] = "";
            }
            if ($row["status"] <> 0) {
                if ($row["user_read"] == 0) {
                    $row["status"] = 2;
                }
                if ($row["admin_read"] == 1) {
                    $row["status"] = 3;
                }
            }
            $return[] = $row;
        }
    }
    return $return;
}

function checkTrials() {
    global $db, $rPermissions, $rUserInfo;
    $rTotal = $rPermissions["total_allowed_gen_trials"];
    if ($rTotal > 0) {
        $rTotalIn = $rPermissions["total_allowed_gen_in"];
        if ($rTotalIn == "hours") {
            $rTime = time() - (intval($rTotal) * 3600);
        } else {
            $rTime = time() - (intval($rTotal) * 3600 * 24);
        }
        $result = $db->query("SELECT COUNT(`id`) AS `count` FROM `users` WHERE `member_id` = ".intval($rUserInfo["id"])." AND `created_at` >= ".$rTime." AND `is_trial` = 1;");
        return $result->fetch_assoc()["count"] < $rTotal;
    }
    return false;
}

function cryptPassword($password, $salt="xtreamcodes", $rounds=20000) {
    if ($salt == "") {
        $salt = substr(bin2hex(openssl_random_pseudo_bytes(16)),0,16);
    }
    $hash = crypt($password, sprintf('$6$rounds=%d$%s$', $rounds, $salt));
    return $hash;
}

function getIP(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function getPermissions($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `member_groups` WHERE `group_id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return null;
}

function doLogin($rUsername, $rPassword) {
    global $db;
    $result = $db->query("SELECT `id`, `username`, `password`, `member_group_id`, `google_2fa_sec`, `status` FROM `reg_users` WHERE `username` = '".$db->real_escape_string($rUsername)."' LIMIT 1;");
    if (($result) && ($result->num_rows == 1)) {
        $rRow = $result->fetch_assoc();
        if (cryptPassword($rPassword) == $rRow["password"]) {
            return $rRow;
        }
    }
    return null;
}

function getSubresellerSetups() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT * FROM `subreseller_setup` ORDER BY `id` ASC;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[intval($row["id"])] = $row;
        }
    }
    return $return;
}

function getSubresellerSetup($rID) {
    global $db;
    $result = $db->query("SELECT * FROM `subreseller_setup` WHERE `id` = ".intval($rID).";");
    if (($result) && ($result->num_rows == 1)) {
        return $result->fetch_assoc();
    }
    return null;
}

function checkTable($rTable) {
    global $db;
    $rTableQuery = Array(
        "subreseller_setup" => "CREATE TABLE `subreseller_setup` (`id` int(11) NOT NULL AUTO_INCREMENT, `reseller` int(8) NOT NULL DEFAULT '0', `subreseller` int(8) NOT NULL DEFAULT '0', `status` int(1) NOT NULL DEFAULT '1', `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;",
        "admin_settings" => "CREATE TABLE `admin_settings` (`type` varchar(128) NOT NULL DEFAULT '', `value` varchar(4096) NOT NULL DEFAULT '', PRIMARY KEY (`type`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
    );
    if ((!$db->query("DESCRIBE `".$rTable."`;")) && (isset($rTableQuery[$rTable]))) {
        // Doesn't exist! Create it.
        $db->query($rTableQuery[$rTable]);
    }
}

function secondsToTime($inputSeconds) {
    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 24 * $secondsInAnHour;
    $days = floor($inputSeconds / $secondsInADay);
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);
    $obj = array(
        'd' => (int) $days,
        'h' => (int) $hours,
        'm' => (int) $minutes,
        's' => (int) $seconds,
    );
    return $obj;
}

function getAdminSettings() {
    global $db;
    $return = Array();
    $result = $db->query("SELECT `type`, `value` FROM `admin_settings`;");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $return[$row["type"]] = $row["value"];
        }
    }
    return $return;
}

function writeAdminSettings() {
    global $rAdminSettings, $db;
    foreach ($rAdminSettings as $rKey => $rValue) {
        if (strlen($rKey) > 0) {
            $db->query("REPLACE INTO `admin_settings`(`type`, `value`) VALUES('".$db->real_escape_string($rKey)."', '".$db->real_escape_string($rValue)."');");
        }
    }
}

function downloadImage($rImage) {
    if ((strlen($rImage) > 0) && (substr(strtolower($rImage), 0, 4) == "http")) {
        $rExt = pathinfo($rImage, PATHINFO_EXTENSION);
        if (in_array(strtolower($rExt), Array("jpg", "jpeg", "png"))) {
            $rData = file_get_contents($rImage);
            if (strlen($rData) > 0) {
                $rFilename = generateString(32);
                $rPath = MAIN_DIR . "wwwdir/images/".$rFilename.".".$rExt;
                file_put_contents($rPath, $rData);
                if (strlen(file_get_contents($rPath)) == strlen($rData)) {
                    return getURL()."/images/".$rFilename.".".$rExt;
                }
            }
        }
    }
    return $rImage;
}

function getFooter() {
    // Don't be a dick. Leave it.
    global $rAdminSettings, $rPermissions, $rSettings, $rRelease;
    if ($rPermissions["is_admin"]) {
        return "Copyright &copy; 2019 - <a href=\"https://xtream-ui.com\">Xtream UI</a> R".$rRelease." - Free & Open Source Forever";
    } else {
        return $rSettings["copyrights_text"];
    }
}

function getURL() {
    global $rServers, $_INFO;
    if (strlen($rServers[$_INFO["server_id"]]["domain_name"]) > 0) {
        return "http://".$rServers[$_INFO["server_id"]]["domain_name"].":".$rServers[$_INFO["server_id"]]["http_broadcast_port"];
    } else if (strlen($rServers[$_INFO["server_id"]]["vpn_ip"]) > 0) {
        return "http://".$rServers[$_INFO["server_id"]]["vpn_ip"].":".$rServers[$_INFO["server_id"]]["http_broadcast_port"];
    } else {
        return "http://".$rServers[$_INFO["server_id"]]["server_ip"].":".$rServers[$_INFO["server_id"]]["http_broadcast_port"];
    }
}

if (isset($_SESSION['hash'])) {
    $rUserInfo = getRegisteredUserHash($_SESSION['hash']);
    if ($rUserInfo) {
        $rPermissions = getPermissions($rUserInfo['member_group_id']);
        if ($rPermissions["is_admin"]) {
            $rPermissions["is_reseller"] = 0; // Don't allow Admin & Reseller!
        }
        $rAdminSettings = getAdminSettings();
        $rSettings = getSettings();
        $rSettings["sidebar"] = $rAdminSettings["sidebar"];
        $rCategories = getCategories();
        $rServers = getStreamingServers();
        $rServerError = False;
        foreach ($rServers as $rServer) {
            if (((((time() - $rServer["last_check_ago"]) > 360)) OR ($rServer["status"] == 2)) AND ($rServer["can_delete"] == 1) AND ($rServer["status"] <> 3)) { $rServerError = True; }
            if (($rServer["status"] == 3) && ($rServer["last_check_ago"] > 0)) {
                $db->query("UPDATE `streaming_servers` SET `status` = 1 WHERE `id` = ".intval($rServer["id"]).";");
                $rServers[intval($rServer["id"])]["status"] = 1;
            }
        }
    } else {
        session_destroy();
    }
}
?>