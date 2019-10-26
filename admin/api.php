<?php
include "./functions.php";
if (!isset($_SESSION['hash'])) { exit; }

if (isset($_GET["action"])) {
    if ($_GET["action"] == "stream") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rStreamID = intval($_GET["stream_id"]);
        $rServerID = intval($_GET["server_id"]);
        $rSub = $_GET["sub"];
        $rAPI = "http://".$rServers[$_INFO["server_id"]]["server_ip"].":".$rServers[$_INFO["server_id"]]["http_broadcast_port"]."/api.php";
        if (in_array($rSub, Array("start", "stop"))) {
            $rURL = $rAPI."?action=stream&sub=".$rSub."&stream_ids[]=".$rStreamID."&servers[]=".$rServerID;
            echo file_get_contents($rURL);exit;
        } else if ($rSub == "restart") {
            if (json_decode(file_get_contents($rAPI."?action=stream&sub=stop&stream_ids[]=".$rStreamID."&servers[]=".$rServerID), True)["result"]) {
                if (json_decode(file_get_contents($rAPI."?action=stream&sub=start&stream_ids[]=".$rStreamID."&servers[]=".$rServerID), True)["result"]) {
                    echo json_encode(Array("result" => True));exit;
                }
            }
            echo json_encode(Array("result" => False));exit;
        } else if ($rSub == "delete") {
            $db->query("DELETE FROM `streams_sys` WHERE `stream_id` = ".$db->real_escape_string($rStreamID)." AND `server_id` = ".$db->real_escape_string($rServerID).";");
            $result = $db->query("SELECT COUNT(`server_stream_id`) AS `count` FROM `streams_sys` WHERE `stream_id` = ".$db->real_escape_string($rStreamID).";");
            if ($result->fetch_assoc()["count"] == 0) {
                $db->query("DELETE FROM `streams` WHERE `id` = ".$db->real_escape_string($rStreamID).";");
            }
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "movie") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rStreamID = intval($_GET["stream_id"]);
        $rServerID = intval($_GET["server_id"]);
        $rSub = $_GET["sub"];
        $rAPI = "http://".$rServers[$_INFO["server_id"]]["server_ip"].":".$rServers[$_INFO["server_id"]]["http_broadcast_port"]."/api.php";
        if (in_array($rSub, Array("start", "stop"))) {
            $rURL = $rAPI."?action=vod&sub=".$rSub."&stream_ids[]=".$rStreamID."&servers[]=".$rServerID;
            echo file_get_contents($rURL);exit;
        } else if ($rSub == "delete") {
            $db->query("DELETE FROM `streams_sys` WHERE `stream_id` = ".$db->real_escape_string($rStreamID)." AND `server_id` = ".$db->real_escape_string($rServerID).";");
            $result = $db->query("SELECT COUNT(`server_stream_id`) AS `count` FROM `streams_sys` WHERE `stream_id` = ".$db->real_escape_string($rStreamID).";");
            if ($result->fetch_assoc()["count"] == 0) {
                $db->query("DELETE FROM `streams` WHERE `id` = ".$db->real_escape_string($rStreamID).";");
                deleteMovieFile($rServerID, $rStreamID);
            }
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "user") {
        $rUserID = intval($_GET["user_id"]);
        // Check if this user falls under the reseller or subresellers.
        if (($rPermissions["is_reseller"]) && (!hasPermissions("user", $rUserID))) {
            echo json_encode(Array("result" => False));exit;
        }
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                if ($rPermissions["is_reseller"]) {
                    $rUserDetails = getUser($rUserID);
                    if ($rUserDetails) {
                        if ($rUserDetails["is_mag"]) {
                            $db->query("INSERT INTO `reg_userlog`(`owner`, `username`, `password`, `date`, `type`) VALUES(".intval($rUserInfo["id"]).", '".$db->real_escape_string($rUserDetails["username"])."', '".$db->real_escape_string($rUserDetails["password"])."', ".intval(time()).", '[<b>UserPanel</b> -> <u>Delete MAG</u>]');");
                        } else if ($rUserDetails["is_e2"]) {
                            $db->query("INSERT INTO `reg_userlog`(`owner`, `username`, `password`, `date`, `type`) VALUES(".intval($rUserInfo["id"]).", '".$db->real_escape_string($rUserDetails["username"])."', '".$db->real_escape_string($rUserDetails["password"])."', ".intval(time()).", '[<b>UserPanel</b> -> <u>Delete Enigma</u>]');");
                        } else {
                            $db->query("INSERT INTO `reg_userlog`(`owner`, `username`, `password`, `date`, `type`) VALUES(".intval($rUserInfo["id"]).", '".$db->real_escape_string($rUserDetails["username"])."', '".$db->real_escape_string($rUserDetails["password"])."', ".intval(time()).", '[<b>UserPanel</b> -> <u>Delete Line</u>]');");
                        }
                    }
                }
                $db->query("DELETE FROM `users` WHERE `id` = ".$db->real_escape_string($rUserID).";");
                $db->query("DELETE FROM `user_output` WHERE `user_id` = ".$db->real_escape_string($rUserID).";");
                $db->query("DELETE FROM `enigma2_devices` WHERE `user_id` = ".$db->real_escape_string($rUserID).";");
                $db->query("DELETE FROM `mag_devices` WHERE `user_id` = ".$db->real_escape_string($rUserID).";");
                echo json_encode(Array("result" => True));exit;
            } else {
                echo json_encode(Array("result" => False));exit;
            }
        } else if ($rSub == "enable") {
            $db->query("UPDATE `users` SET `enabled` = 1 WHERE `id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "disable") {
            $db->query("UPDATE `users` SET `enabled` = 0 WHERE `id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "ban") {
            $db->query("UPDATE `users` SET `admin_enabled` = 0 WHERE `id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "unban") {
            $db->query("UPDATE `users` SET `admin_enabled` = 1 WHERE `id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "kill") {
            $rResult = $db->query("SELECT `pid`, `server_id` FROM `user_activity_now` WHERE `user_id` = ".$db->real_escape_string($rUserID).";");
            if (($rResult) && ($rResult->num_rows > 0)) {
                while ($rRow = $rResult->fetch_assoc()) {
                    sexec($rRow["server_id"], "kill -9 ".$rRow["pid"]);
                }
            }
            $db->query("DELETE FROM `user_activity_now` WHERE `user_id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "user_activity") {
        $rPID = intval($_GET["pid"]);
        // Check if the user running this PID falls under the reseller or subresellers.
        if (($rPermissions["is_reseller"]) && (!hasPermissions("pid", $rPID))) {
            echo json_encode(Array("result" => False));exit;
        }
        $rSub = $_GET["sub"];
        if ($rSub == "kill") {
            $rResult = $db->query("SELECT `server_id` FROM `user_activity_now` WHERE `pid` = ".intval($rPID)." LIMIT 1;");
            if (($rResult) && ($rResult->num_rows == 1)) {
                sexec($rResult->fetch_assoc()["server_id"], "kill -9 ".$rPID);
                $db->query("DELETE FROM `user_activity_now` WHERE `pid` = ".$db->real_escape_string($rPID).";");
                echo json_encode(Array("result" => True));exit;
            }
        }
        echo json_encode(Array("result" => False));exit;
    } else if ($_GET["action"] == "reg_user") {
        $rUserID = intval($_GET["user_id"]);
        // Check if this registered user falls under the reseller or subresellers.
        if (($rPermissions["is_reseller"]) && (!hasPermissions("reg_user", $rUserID))) {
            echo json_encode(Array("result" => False));exit;
        }
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                if ($rPermissions["is_reseller"]) {
                    $rUserDetails = getRegisteredUser($rUserID);
                    if ($rUserDetails) {
                        $db->query("INSERT INTO `reg_userlog`(`owner`, `username`, `password`, `date`, `type`) VALUES(".intval($rUserInfo["id"]).", '".$db->real_escape_string($rUserDetails["username"])."', '', ".intval(time()).", '[<b>UserPanel</b> -> <u>Delete Subreseller</u>]');");
                    }
                    $rPrevOwner = getRegisteredUser($rUserDetails["owner_id"]);
                    $rCredits = $rUserDetails["credits"];
                    $rNewCredits = $rPrevOwner["credits"] + $rCredits;
                    $db->query("UPDATE `reg_users` SET `credits` = ".$rNewCredits." WHERE `id` = ".intval($rPrevOwner["id"]).";");
                }
                $db->query("DELETE FROM `reg_users` WHERE `id` = ".$db->real_escape_string($rUserID).";");
                echo json_encode(Array("result" => True));exit;
            } else {
                echo json_encode(Array("result" => False));exit;
            }
        } else if ($rSub == "enable") {
            $db->query("UPDATE `reg_users` SET `status` = 1 WHERE `id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "disable") {
            $db->query("UPDATE `reg_users` SET `status` = 0 WHERE `id` = ".$db->real_escape_string($rUserID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "ticket") {
        $rTicketID = intval($_GET["ticket_id"]);
        // Check if this ticket falls under the reseller or subresellers.
        if (($rPermissions["is_reseller"]) && (!hasPermissions("ticket", $rTicketID))) {
            echo json_encode(Array("result" => False));exit;
        }
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `tickets` WHERE `id` = ".$db->real_escape_string($rTicketID).";");
            $db->query("DELETE FROM `tickets_replies` WHERE `ticket_id` = ".$db->real_escape_string($rTicketID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "close") {
            $db->query("UPDATE `tickets` SET `status` = 0 WHERE `id` = ".$db->real_escape_string($rTicketID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "reopen") {
            $db->query("UPDATE `tickets` SET `status` = 1 WHERE `id` = ".$db->real_escape_string($rTicketID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "unread") {
            $db->query("UPDATE `tickets` SET `admin_read` = 0 WHERE `id` = ".$db->real_escape_string($rTicketID).";");
            echo json_encode(Array("result" => True));exit;
        } else if ($rSub == "read") {
            $db->query("UPDATE `tickets` SET `admin_read` = 1 WHERE `id` = ".$db->real_escape_string($rTicketID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "mag") {
        $rMagID = intval($_GET["mag_id"]);
        // Check if this device falls under the reseller or subresellers.
        if (($rPermissions["is_reseller"]) && (!hasPermissions("mag", $rMagID))) {
            echo json_encode(Array("result" => False));exit;
        }
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $rMagDetails = getMag($rMagID);
            if (isset($rMagDetails["user_id"])) {
                $db->query("DELETE FROM `users` WHERE `id` = ".$db->real_escape_string($rMagDetails["user_id"]).";");
                $db->query("DELETE FROM `user_output` WHERE `user_id` = ".$db->real_escape_string($rMagDetails["user_id"]).";");
            }
            $db->query("DELETE FROM `mag_devices` WHERE `mag_id` = ".$db->real_escape_string($rMagID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "mag_event") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rMagID = intval($_GET["mag_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `mag_events` WHERE `id` = ".$db->real_escape_string($rMagID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "epg") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rEPGID = intval($_GET["epg_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `epg` WHERE `id` = ".$db->real_escape_string($rEPGID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "profile") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rProfileID = intval($_GET["profile_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `transcoding_profiles` WHERE `profile_id` = ".$db->real_escape_string($rProfileID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "subreseller_setup") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rID = intval($_GET["id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `subreseller_setup` WHERE `id` = ".$db->real_escape_string($rID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "enigma") {
        $rEnigmaID = intval($_GET["enigma_id"]);
        // Check if this device falls under the reseller or subresellers.
        if (($rPermissions["is_reseller"]) && (!hasPermissions("e2", $rEnigmaID))) {
            echo json_encode(Array("result" => False));exit;
        }
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $rEnigmaDetails = getEnigma($rEnigmaID);
            if (isset($rEnigmaDetails["user_id"])) {
                $db->query("DELETE FROM `users` WHERE `id` = ".$db->real_escape_string($rEnigmaDetails["user_id"]).";");
                $db->query("DELETE FROM `user_output` WHERE `user_id` = ".$db->real_escape_string($rEnigmaDetails["user_id"]).";");
            }
            $db->query("DELETE FROM `enigma2_devices` WHERE `device_id` = ".$db->real_escape_string($rEnigmaID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "server") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rServerID = intval($_GET["server_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            if ($rServers[$_GET["server_id"]]["can_delete"] == 1) {
                $db->query("DELETE FROM `streaming_servers` WHERE `id` = ".$db->real_escape_string($rServerID).";");
                $db->query("DELETE FROM `streams_sys` WHERE `server_id` = ".$db->real_escape_string($rServerID).";");
                echo json_encode(Array("result" => True));exit;
            } else {
                echo json_encode(Array("result" => False));exit;
            }
        } else if ($rSub == "kill") {
            $rResult = $db->query("SELECT `pid`, `server_id` FROM `user_activity_now` WHERE `server_id` = ".$db->real_escape_string($rServerID).";");
            if (($rResult) && ($rResult->num_rows > 0)) {
                while ($rRow = $rResult->fetch_assoc()) {
                    sexec($rRow["server_id"], "kill -9 ".$rRow["pid"]);
                }
            }
            $db->query("DELETE FROM `user_activity_now` WHERE `server_id` = ".$db->real_escape_string($rServerID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "package") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rPackageID = intval($_GET["package_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `packages` WHERE `id` = ".$db->real_escape_string($rPackageID).";");
            echo json_encode(Array("result" => True));exit;
        } else if (in_array($rSub, Array("is_trial", "is_official", "can_gen_mag", "can_gen_e2", "only_mag", "only_e2"))) {
            $db->query("UPDATE `packages` SET `".$db->real_escape_string($rSub)."` = ".intval($_GET["value"])." WHERE `id` = ".$db->real_escape_string($rPackageID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "group") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rGroupID = intval($_GET["group_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `member_groups` WHERE `group_id` = ".$db->real_escape_string($rGroupID)." AND `can_delete` = 1;");
            echo json_encode(Array("result" => True));exit;
        } else if (in_array($rSub, Array("is_banned", "is_admin", "is_reseller"))) {
            $db->query("UPDATE `member_groups` SET `".$db->real_escape_string($rSub)."` = ".intval($_GET["value"])." WHERE `group_id` = ".$db->real_escape_string($rGroupID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "bouquet") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rBouquetID = intval($_GET["bouquet_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `bouquets` WHERE `id` = ".$db->real_escape_string($rBouquetID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "category") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rCategoryID = intval($_GET["category_id"]);
        $rSub = $_GET["sub"];
        if ($rSub == "delete") {
            $db->query("DELETE FROM `stream_categories` WHERE `id` = ".$db->real_escape_string($rCategoryID).";");
            echo json_encode(Array("result" => True));exit;
        } else {
            echo json_encode(Array("result" => False));exit;
        }
    } else if ($_GET["action"] == "get_package") {
        $rReturn = Array();
        $rResult = $db->query("SELECT `bouquets`, `official_credits` AS `cost_credits`, `official_duration`, `official_duration_in`, `max_connections`, `can_gen_mag`, `can_gen_e2`, `only_mag`, `only_e2` FROM `packages` WHERE `id` = ".intval($_GET["package_id"]).";");
        if (($rResult) && ($rResult->num_rows == 1)) {
            $rData = $rResult->fetch_assoc();
            $rData["exp_date"] = date('Y-m-d', strtotime('+'.intval($rData["official_duration"]).' '.$rData["official_duration_in"]));
            if (isset($_GET["user_id"])) {
                if ($rUser = getUser($_GET["user_id"])) {
                    if(time() < $rUser["exp_date"]) {
                        $rData["exp_date"] = date('Y-m-d', strtotime('+'.intval($rData["official_duration"]).' '.$rData["official_duration_in"], $rUser["exp_date"]));
                    } else {
                        $rData["exp_date"] = date('Y-m-d', strtotime('+'.intval($rData["official_duration"]).' '.$rData["official_duration_in"]));
                    }
                }
            }
            foreach (json_decode($rData["bouquets"], True) as $rBouquet) {
                $rResult = $db->query("SELECT * FROM `bouquets` WHERE `id` = ".intval($rBouquet).";");
                if (($rResult) && ($rResult->num_rows == 1)) {
                    $rRow = $rResult->fetch_assoc();
                    $rReturn[] = Array("id" => $rRow["id"], "bouquet_name" => $rRow["bouquet_name"], "bouquet_channels" => json_decode($rRow["bouquet_channels"], True), "bouquet_series" => json_decode($rRow["bouquet_series"], True));
                }
            }
            echo json_encode(Array("result" => True, "bouquets" => $rReturn, "data" => $rData));
        } else {
            echo json_encode(Array("result" => False));
        }
        exit;
    } else if ($_GET["action"] == "get_package_trial") {
        $rReturn = Array();
        $rResult = $db->query("SELECT `bouquets`, `trial_credits` AS `cost_credits`, `trial_duration`, `trial_duration_in`, `max_connections`, `can_gen_mag`, `can_gen_e2`, `only_mag`, `only_e2` FROM `packages` WHERE `id` = ".intval($_GET["package_id"]).";");
        if (($rResult) && ($rResult->num_rows == 1)) {
            $rData = $rResult->fetch_assoc();
            $rData["exp_date"] = date('Y-m-d', strtotime('+'.intval($rData["trial_duration"]).' '.$rData["trial_duration_in"]));
            foreach (json_decode($rData["bouquets"], True) as $rBouquet) {
                $rResult = $db->query("SELECT * FROM `bouquets` WHERE `id` = ".intval($rBouquet).";");
                if (($rResult) && ($rResult->num_rows == 1)) {
                    $rRow = $rResult->fetch_assoc();
                    $rReturn[] = Array("id" => $rRow["id"], "bouquet_name" => $rRow["bouquet_name"], "bouquet_channels" => json_decode($rRow["bouquet_channels"], True), "bouquet_series" => json_decode($rRow["bouquet_series"], True));
                }
            }
            echo json_encode(Array("result" => True, "bouquets" => $rReturn, "data" => $rData));
        } else {
            echo json_encode(Array("result" => False));
        }
        exit;
    } else if ($_GET["action"] == "streams") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rData = Array();
        $rStreamIDs = json_decode($_GET["stream_ids"], True);
        $rStreams = getStreams(null, false, $rStreamIDs);
        echo json_encode(Array("result" => True, "data" => $rStreams));
        exit;
    } else if ($_GET["action"] == "stats") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $return = Array("cpu" => 0, "mem" => 0, "uptime" => "--", "total_running_streams" => 0, "bytes_sent" => 0, "bytes_received" => 0, "offline_streams" => 0);
        if (isset($_GET["server_id"])) {
            $rServerID = intval($_GET["server_id"]);
            $rWatchDog = json_decode($rServers[$rServerID]["watchdog_data"], True);
            if (is_array($rWatchDog)) {
                $return["uptime"] = $rWatchDog["uptime"];
                $return["mem"] = intval($rWatchDog["total_mem_used_percent"]);
                $return["cpu"] = intval($rWatchDog["cpu_avg"]);
                //$return["total_running_streams"] = intval(trim($rWatchDog["total_running_streams"]));
                $return["bytes_received"] = intval($rWatchDog["bytes_received"]);
                $return["bytes_sent"] = intval($rWatchDog["bytes_sent"]);
            }
            $result = $db->query("SELECT COUNT(*) AS `count` FROM `user_activity_now` WHERE `server_id` = ".$rServerID.";");
            $return["open_connections"] = $result->fetch_assoc()["count"];
            $result = $db->query("SELECT COUNT(*) AS `count` FROM `user_activity_now`;");
            $return["total_connections"] = $result->fetch_assoc()["count"];
            $result = $db->query("SELECT COUNT(`user_id`) AS `count` FROM `user_activity_now` WHERE `server_id` = ".$rServerID." GROUP BY `user_id`;");
            $return["online_users"] = $result->num_rows;
            $result = $db->query("SELECT COUNT(`user_id`) AS `count` FROM `user_activity_now` GROUP BY `user_id`;");
            $return["total_users"] = $result->num_rows;
            $result = $db->query("SELECT COUNT(*) AS `count` FROM `streams_sys` LEFT JOIN `streams` ON `streams`.`id` = `streams_sys`.`stream_id` WHERE `server_id` = ".$rServerID." AND `stream_status` <> 2 AND `type` IN (1,3);");
            $return["total_streams"] = $result->fetch_assoc()["count"];
            $result = $db->query("SELECT COUNT(*) AS `count` FROM `streams_sys` LEFT JOIN `streams` ON `streams`.`id` = `streams_sys`.`stream_id` WHERE `server_id` = ".$rServerID." AND `pid` > 0 AND `type` IN (1,3);");
            $return["total_running_streams"] = $result->fetch_assoc()["count"];
            $result = $db->query("SELECT COUNT(*) AS `count` FROM `streams_sys` LEFT JOIN `streams` ON `streams`.`id` = `streams_sys`.`stream_id` WHERE `server_id` = ".$rServerID." AND ((`streams_sys`.`monitor_pid` IS NOT NULL AND `streams_sys`.`monitor_pid` > 0) AND (`streams_sys`.`pid` IS NULL OR `streams_sys`.`pid` <= 0) AND `streams_sys`.`stream_status` <> 0);");
            $return["offline_streams"] = $result->fetch_assoc()["count"];
            $return["network_guaranteed_speed"] = $rServers[$rServerID]["network_guaranteed_speed"];
        } else {
            $rUptime = 0;
            foreach (array_keys($rServers) as $rServerID) {
                $rWatchDog = json_decode($rServers[$rServerID]["watchdog_data"], True);
                if (is_array($rWatchDog)) {
                    foreach (explode(" ", $rWatchDog["uptime"]) as $rPart) {
                        if (substr($rPart, -1) == "d") {
                            $rUptime += intval(substr($rPart, 0, -1)) * 86400;
                        } else if (substr($rPart, -1) == "h") {
                            $rUptime += intval(substr($rPart, 0, -1)) * 3600;
                        } else if (substr($rPart, -1) == "m") {
                            $rUptime += intval(substr($rPart, 0, -1)) * 60;
                        } else if (substr($rPart, -1) == "s") {
                            $rUptime += intval(substr($rPart, 0, -1));
                        }
                    }
                    $return["mem"] += intval($rWatchDog["total_mem_used_percent"]);
                    $return["cpu"] += intval($rWatchDog["cpu_avg"]);
                    $return["bytes_received"] += intval($rWatchDog["bytes_received"]);
                    $return["bytes_sent"] += intval($rWatchDog["bytes_sent"]);
                }
                $result = $db->query("SELECT COUNT(*) AS `count` FROM `user_activity_now` WHERE `server_id` = ".$rServerID.";");
                $return["open_connections"] += $result->fetch_assoc()["count"];
                $result = $db->query("SELECT COUNT(*) AS `count` FROM `user_activity_now`;");
                $return["total_connections"] = $result->fetch_assoc()["count"];
                $result = $db->query("SELECT `user_id` FROM `user_activity_now` WHERE `server_id` = ".$rServerID." GROUP BY `user_id`;");
                $return["online_users"] += $result->num_rows;
                $result = $db->query("SELECT `user_id` FROM `user_activity_now` GROUP BY `user_id`;");
                $return["total_users"] = $result->num_rows;
                $result = $db->query("SELECT COUNT(*) AS `count` FROM `streams_sys` LEFT JOIN `streams` ON `streams`.`id` = `streams_sys`.`stream_id` WHERE `server_id` = ".$rServerID." AND `stream_status` <> 2 AND `type` IN (1,3);");
                $return["total_streams"] += $result->fetch_assoc()["count"];
                $result = $db->query("SELECT COUNT(*) AS `count` FROM `streams_sys` LEFT JOIN `streams` ON `streams`.`id` = `streams_sys`.`stream_id` WHERE `server_id` = ".$rServerID." AND `pid` > 0 AND `type` IN (1,3);");
                $return["total_running_streams"] += $result->fetch_assoc()["count"];
                $result = $db->query("SELECT COUNT(*) AS `count` FROM `streams_sys` LEFT JOIN `streams` ON `streams`.`id` = `streams_sys`.`stream_id` WHERE `server_id` = ".$rServerID." AND ((`streams_sys`.`monitor_pid` IS NOT NULL AND `streams_sys`.`monitor_pid` > 0) AND (`streams_sys`.`pid` IS NULL OR `streams_sys`.`pid` <= 0) AND `streams_sys`.`stream_status` <> 0);");
                $return["offline_streams"] += $result->fetch_assoc()["count"];
                $return["network_guaranteed_speed"] += $rServers[$rServerID]["network_guaranteed_speed"];
            }
            $return["mem"] = intval($return["mem"] / count($rServers));
            $return["cpu"] = intval($return["cpu"] / count($rServers));
            $return["uptime"] = "";
            $rUptime = secondsToTime($rUptime);
            if ($rUptime["d"] > 0) { $return["uptime"] .= $rUptime["d"]."d "; }
            if (($rUptime["h"] > 0) OR (strlen($return["uptime"]) > 0)) { $return["uptime"] .= $rUptime["h"]."h "; }
            if (($rUptime["m"] > 0) OR (strlen($return["uptime"]) > 0)) { $return["uptime"] .= $rUptime["m"]."m "; }
            if (($rUptime["s"] > 0) OR (strlen($return["uptime"]) > 0)) { $return["uptime"] .= $rUptime["s"]."s "; }
            
        }
        echo json_encode($return);exit;
    } else if ($_GET["action"] == "reseller_dashboard") {
        if ($rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $return = Array("open_connections" => 0, "online_users" => 0, "active_accounts" => 0, "credits" => 0);
        $result = $db->query("SELECT `activity_id` FROM `user_activity_now` AS `a` INNER JOIN `users` AS `u` ON `a`.`user_id` = `u`.`id` WHERE `u`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).");");
        $return["open_connections"] = $result->num_rows;
        $result = $db->query("SELECT `activity_id` FROM `user_activity_now` AS `a` INNER JOIN `users` AS `u` ON `a`.`user_id` = `u`.`id` WHERE `u`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).") GROUP BY `a`.`user_id`;");
        $return["online_users"] = $result->num_rows;
        $result = $db->query("SELECT `id` FROM `users` WHERE `member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).");");
        $return["active_accounts"] = $result->num_rows;
        $return["credits"] = $rUserInfo["credits"];
        echo json_encode($return);exit;
    } else if ($_GET["action"] == "review_bouquet") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $return = Array("streams" => Array(), "vod" => Array(), "series" => Array(), "result" => true);
        if (isset($_POST["data"]["stream"])) {
            foreach ($_POST["data"]["stream"] as $rStreamID) {
                $rResult = $db->query("SELECT `id`, `stream_display_name`, `type` FROM `streams` WHERE `id` = ".intval($rStreamID).";");
                if (($rResult) && ($rResult->num_rows == 1)) {
                    $rData = $rResult->fetch_assoc();
                    if ($rData["type"] == 2) {
                        $return["vod"][] = $rData;
                    } else {
                        $return["streams"][] = $rData;
                    }
                }
            }
        }
        if (isset($_POST["data"]["series"])) {
            foreach ($_POST["data"]["series"] as $rSeriesID) {
                $rResult = $db->query("SELECT `id`, `title` FROM `series` WHERE `id` = ".intval($rSeriesID).";");
                if (($rResult) && ($rResult->num_rows == 1)) {
                    $rData = $rResult->fetch_assoc();
                    $return["series"][] = $rData;
                }
            }
        }
        echo json_encode($return);exit;
    } else if ($_GET["action"] == "userlist") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $return = Array("total_count" => 0, "items" => Array(), "result" => true);
        if (isset($_GET["search"])) {
            if (isset($_GET["page"])) {
                $rPage = intval($_GET["page"]);
            } else {
                $rPage = 1;
            }
            $rResult = $db->query("SELECT COUNT(`id`) AS `id` FROM `users` WHERE `username` LIKE '%".$db->real_escape_string($_GET["search"])."%' AND `is_e2` = 0 AND `is_mag` = 0;");
            $return["total_count"] = $rResult->fetch_assoc()["id"];
            $rResult = $db->query("SELECT `id`, `username` FROM `users` WHERE `username` LIKE '%".$db->real_escape_string($_GET["search"])."%' AND `is_e2` = 0 AND `is_mag` = 0 ORDER BY `username` ASC LIMIT ".(($rPage-1) * 100).", 100;");
            if (($rResult) && ($rResult->num_rows > 0)) {
                while ($rRow = $rResult->fetch_assoc()) {
                    $return["items"][] = Array("id" => $rRow["id"], "text" => $rRow["username"]);
                }
            }
        }
        echo json_encode($return);exit;
    } else if ($_GET["action"] == "force_epg") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        sexec($_INFO["server_id"], "/home/xtreamcodes/iptv_xtream_codes/php/bin/php /home/xtreamcodes/iptv_xtream_codes/crons/epg.php");
        echo json_encode(Array("result" => True));exit;
    } else if ($_GET["action"] == "sort_bouquet") {
        if (!$rPermissions["is_admin"]) { echo json_encode(Array("result" => False)); exit; }
        $rBouquet = getBouquet($_GET["bouquet_id"]);
        $rOrdered = Array();
        if (($_GET["type"] == "stream") OR ($_GET["type"] == "movie")) {
            $rChannels = json_decode($rBouquet["bouquet_channels"], True);
            if (is_array($rChannels)) {
                if ($_GET["type"] == "stream") {
                    $result = $db->query("SELECT `streams`.`id`, `streams`.`type`, `streams`.`category_id`, `streams`.`stream_display_name`, `stream_categories`.`category_name` FROM `streams`, `stream_categories` WHERE `streams`.`type` IN (1,3) AND `streams`.`category_id` = `stream_categories`.`id` AND `streams`.`id` IN (".$db->real_escape_string(join(",", $rChannels)).") ORDER BY `streams`.`stream_display_name` ASC;");
                } else {
                    $result = $db->query("SELECT `streams`.`id`, `streams`.`type`, `streams`.`category_id`, `streams`.`stream_display_name`, `stream_categories`.`category_name` FROM `streams`, `stream_categories` WHERE `streams`.`type` = 2 AND `streams`.`category_id` = `stream_categories`.`id` AND `streams`.`id` IN (".$db->real_escape_string(join(",", $rChannels)).") ORDER BY `streams`.`stream_display_name` ASC;");
                }
                if (($result) && ($result->num_rows > 0)) {
                    while ($row = $result->fetch_assoc()) {
                        $rOrdered[] = intval($row["id"]);
                    }
                }
            }
            foreach ($rChannels as $rChannel) {
                if (!in_array(intval($rChannel), $rOrdered)) {
                    $rOrdered[] = intval($rChannel);
                }
            }
            if (count($rOrdered) > 0) {
                $db->query("UPDATE `bouquets` SET `bouquet_channels` = '".$db->real_escape_string(json_encode($rOrdered))."' WHERE `id` = ".intval($rBouquet["id"]).";");
            }
            echo json_encode(Array("result" => True));exit;
        } else {
            $rSeries = json_decode($rBouquet["bouquet_series"], True);
            if (is_array($rSeries)) {
                $result = $db->query("SELECT `series`.`id`, `series`.`category_id`, `series`.`title`, `stream_categories`.`category_name` FROM `series`, `stream_categories` WHERE `series`.`category_id` = `stream_categories`.`id` AND `series`.`id` IN (".$db->real_escape_string(join(",", $rSeries)).") ORDER BY `series`.`title` ASC;");
                if (($result) && ($result->num_rows > 0)) {
                    while ($row = $result->fetch_assoc()) {
                        $rOrdered[] = intval($row["id"]);
                    }
                }
            }
            if (count($rOrdered) > 0) {
                $db->query("UPDATE `bouquets` SET `bouquet_series` = '".$db->real_escape_string(join(",", $rOrdered))."' WHERE `id` = ".intval($rBouquet["id"]).";");
            }
            echo json_encode(Array("result" => True));exit;
        }
        echo json_encode(Array("result" => False));exit;
    } else if ($_GET["action"] == "tmdb_search") {
        include "tmdb.php";
        $rTMDB = new TMDb($rSettings["tmdb_api_key"]);
        $rTerm = $_GET["term"];
        if ($_GET["type"] == "movie") {
            if (strlen($rAdminSettings["tmdb_language"]) > 0) {
                
                $rResults = $rTMDB->searchMovie($rTerm, 1, false, $rAdminSettings["tmdb_language"]);
            } else {
                $rResults = $rTMDB->searchMovie($rTerm);
            }
        } else {
        }
        if (count($rResults) > 0) {
            echo json_encode(Array("result" => True, "data" => $rResults)); exit;
        }
        echo json_encode(Array("result" => False));exit;
    } else if ($_GET["action"] == "tmdb") {
        include "tmdb.php";
        $rTMDB = new TMDb($rSettings["tmdb_api_key"]);
        $rID = $_GET["id"];
        if ($_GET["type"] == "movie") {
            if (strlen($rAdminSettings["tmdb_language"]) > 0) {
                $rResult = $rTMDB->getMovie($rID, $rAdminSettings["tmdb_language"]);
                $rResult["videos"] = $rTMDB->getMovieTrailers($rID, $rAdminSettings["tmdb_language"]);
            } else {
                $rResult = $rTMDB->getMovie($rID);
                $rResult["videos"] = $rTMDB->getMovieTrailers($rID);
            }
            $rResult["cast"] = $rTMDB->getMovieCast($rID);
        }
        if ($rResult) {
            echo json_encode(Array("result" => True, "data" => $rResult)); exit;
        }
        echo json_encode(Array("result" => False));exit;
    } else if ($_GET["action"] == "listdir") {
        if ($_GET["filter"] == "video") {
            $rFilter = Array("mp4", "mkv", "mov", "avi", "mpg", "mpeg", "flv", "wmv");
        } else if ($_GET["filter"] == "subs") {
            $rFilter = Array("srt", "sub", "sbv");
        } else {
            $rFilter = null;
        }
		if ((isset($_GET["server"])) && (isset($_GET["dir"]))) {
            echo json_encode(Array("result" => True, "data" => listDir(intval($_GET["server"]), $_GET["dir"], $rFilter))); exit;
        }
        echo json_encode(Array("result" => False));exit;
	}
}
echo json_encode(Array("result" => False));
?>