<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { exit; }
$rStatusArray = Array(0 => "Stopped", 1 => "Running", 2 => "Starting", 3 => "<strong style='color:#cc9999'>DOWN</strong>", 4 => "On Demand", 5 => "Direct");
$rVODStatusArray = Array(0 => "<i class='text-dark mdi mdi-checkbox-blank-circle-outline'></i>", 1 => "<i class='text-success mdi mdi-check-circle'></i>", 2 => "<i class='text-warning mdi mdi-checkbox-blank-circle'></i>", 3 => "<i class='text-primary mdi mdi-web'></i>", 4 => "<i class='text-danger mdi mdi-triangle'></i>");

$rType = $_GET["id"];
$rStart = intval($_GET["start"]);
$rLimit = intval($_GET["length"]);

if (($rLimit > 1000) OR ($rLimit == -1) OR ($rLimit == 0)) { $rLimit = 1000; }

if ($rType == "users") {
	$rAvailableMembers = array_keys(getRegisteredUsers($rUserInfo["id"]));
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`users`.`id`", "`users`.`username`", "`users`.`password`", "`reg_users`.`username`", "`users`.`enabled`", "`active_connections`", "`users`.`is_trial`", "`users`.`exp_date`", "`users`.`max_connections`", "`users`.`max_connections`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_admin"]) {
        $rWhere[] = "`users`.`is_mag` = 0 AND `users`.`is_e2` = 0";
    } else {
        $rWhere[] = "`users`.`is_mag` = 0 AND `users`.`is_e2` = 0 AND `users`.`member_id` IN (".join(",", $rAvailableMembers).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`users`.`username` LIKE '%{$rSearch}%' OR `users`.`password` LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%' OR from_unixtime(`exp_date`) LIKE '%{$rSearch}%' OR `users`.`max_connections` LIKE '%{$rSearch}%' OR `users`.`reseller_notes` LIKE '%{$rSearch}%' OR `users`.`admin_notes` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`users`.`admin_enabled` = 1 AND `users`.`enabled` = 1 AND (`users`.`exp_date` IS NULL OR `users`.`exp_date` > UNIX_TIMESTAMP()))";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "`users`.`enabled` = 0";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "`users`.`admin_enabled` = 0";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`users`.`exp_date` IS NOT NULL AND `users`.`exp_date` <= UNIX_TIMESTAMP())";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`users`.`is_trial` = 1";
        }
    }
	if (strlen($_GET["reseller"]) > 0) {
		$rWhere[] = "`users`.`member_id` = ".intval($_GET["reseller"]);
	}
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(`users`.`id`) AS `count` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `users`.`id`, `users`.`member_id`, `users`.`username`, `users`.`password`, `users`.`exp_date`, `users`.`admin_enabled`, `users`.`enabled`, `users`.`admin_notes`, `users`.`reseller_notes`, `users`.`max_connections`,  `users`.`is_trial`, `reg_users`.`username` AS `owner_name`, (SELECT count(*) FROM `user_activity_now` WHERE `users`.`id` = `user_activity_now`.`user_id`) AS `active_connections`, (SELECT MAX(`date_start`) FROM `user_activity` WHERE `users`.`id` = `user_activity`.`user_id`) AS `last_active` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
				if ((in_array($rRow["member_id"], $rAvailableMembers)) OR ($rPermissions["is_admin"])) { // Temporary double check to ensure query doesn't contain all users.
					// Format Rows
					if (!$rRow["admin_enabled"]) {
						$rStatus = '<i class="text-danger fas fa-circle"></i>';
					} else {
						if (!$rRow["enabled"]) {
							$rStatus = '<i class="text-secondary fas fa-circle"></i>';
						} else if (($rRow["exp_date"]) && ($rRow["exp_date"] < time())) {
							$rStatus = '<i class="text-warning far fa-circle"></i>';
						} else {
							$rStatus = '<i class="text-success fas fa-circle"></i>';
						}
					}
					if ($rRow["active_connections"] > 0) {
						$rActive = '<i class="text-success fas fa-circle"></i>';
					} else {
						$rActive = '<i class="text-warning far fa-circle"></i>';
					}
					if ($rRow["is_trial"]) {
						$rTrial = '<i class="text-warning fas fa-circle"></i>';
					} else {
						$rTrial = '<i class="text-secondary far fa-circle"></i>';
					}
					if ($rRow["exp_date"]) {
						if ($rRow["exp_date"] < time()) {
							$rExpDate = "<span class=\"expired\">".date("Y-m-d", $rRow["exp_date"])."</span>";
						} else {
							$rExpDate = date("Y-m-d", $rRow["exp_date"]);
						}
					} else {
						$rExpDate = "Never";
					}
					if ($rRow["max_connections"] == 0) {
						$rRow["max_connections"] = "&infin;";
					}
					$rActiveConnections = "<a href=\"./live_connections.php?user_id=".$rRow["id"]."\">".$rRow["active_connections"]."</a>";
					if ($rPermissions["is_admin"]) {
						$rButtons = '<a href="./user.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
						';
					} else {
						$rButtons = '<a href="./user_reseller.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
						';
					}
					if ((($rPermissions["is_reseller"]) && ($rPermissions["allow_download"])) OR ($rPermissions["is_admin"])) {
						$rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="download(\''.$rRow["username"].'\', \''.$rRow["password"].'\');"><i class="mdi mdi-download"></i></button>
						';
					}
					$rButtons .= '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'kill\');"><i class="fas fa-hammer"></i></button>
					';
					if ($rPermissions["is_admin"]) {
						if ($rRow["admin_enabled"] == 1) {
							$rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
							';
						} else {
							$rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
							';
						}
					}
					if ($rRow["enabled"] == 1) {
						$rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
						';
					} else {
						$rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
						';
					}
					if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
						$rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
					}
					if ($rRow["last_active"]) {
						$rLastActive = date("Y-m-d", $rRow["last_active"]);
					} else {
						$rLastActive = "Never";
					}
					$rReturn["data"][] = Array($rRow["id"], $rRow["username"], $rRow["password"], $rRow["owner_name"], $rStatus, $rActive, $rTrial, $rExpDate, $rActiveConnections, $rRow["max_connections"], $rLastActive, $rButtons);
				}
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "mags") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`users`.`id`", "`users`.`username`", "`mag_devices`.`mac`", "`reg_users`.`username`", "`users`.`enabled`", "`active_connections`", "`users`.`is_trial`", "`users`.`exp_date`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_reseller"]) {
        $rWhere[] = "`users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`users`.`username` LIKE '%{$rSearch}%' OR from_base64(`mag_devices`.`mac`) LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%' OR from_unixtime(`exp_date`) LIKE '%{$rSearch}%' OR `users`.`reseller_notes` LIKE '%{$rSearch}%' OR `users`.`admin_notes` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`users`.`admin_enabled` = 1 AND `users`.`enabled` = 1 AND (`users`.`exp_date` IS NULL OR `users`.`exp_date` > UNIX_TIMESTAMP()))";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "`users`.`enabled` = 0";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "`users`.`admin_enabled` = 0";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`users`.`exp_date` IS NOT NULL AND `users`.`exp_date` <= UNIX_TIMESTAMP())";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`users`.`is_trial` = 1";
        }
    }
    if ($rPermissions["is_admin"]) {
        if (strlen($_GET["reseller"]) > 0) {
            $rWhere[] = "`users`.`member_id` = ".intval($_GET["reseller"]);
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(`users`.`id`) AS `count` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `mag_devices` ON `mag_devices`.`user_id` = `users`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `users`.`id`, `users`.`username`, `mag_devices`.`mac`, `users`.`exp_date`, `users`.`admin_enabled`, `users`.`enabled`, `users`.`admin_notes`, `users`.`reseller_notes`, `users`.`max_connections`,  `users`.`is_trial`, `reg_users`.`username` AS `owner_name`, (SELECT count(*) FROM `user_activity_now` WHERE `users`.`id` = `user_activity_now`.`user_id`) AS `active_connections` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `mag_devices` ON `mag_devices`.`user_id` = `users`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if (!$rRow["admin_enabled"]) {
                    $rStatus = '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if (!$rRow["enabled"]) {
                        $rStatus = '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($rRow["exp_date"]) && ($rRow["exp_date"] < time())) {
                        $rStatus = '<i class="text-warning far fa-circle"></i>';
                    } else {
                        $rStatus = '<i class="text-success fas fa-circle"></i>';
                    }
                }
                if ($rRow["active_connections"] > 0) {
                    $rActive = '<i class="text-success fas fa-circle"></i>';
                } else {
                    $rActive = '<i class="text-warning far fa-circle"></i>';
                }
                if ($rRow["is_trial"]) {
                    $rTrial = '<i class="text-warning fas fa-circle"></i>';
                } else {
                    $rTrial = '<i class="text-secondary far fa-circle"></i>';
                }
                if ($rRow["exp_date"]) {
                    if ($rRow["exp_date"] < time()) {
                        $rExpDate = "<span class=\"expired\">".date("Y-m-d", $rRow["exp_date"])."</span>";
                    } else {
                        $rExpDate = date("Y-m-d", $rRow["exp_date"]);
                    }
                } else {
                    $rExpDate = "Never";
                }
                $rActiveConnections = "<a href=\"./live_connections.php?user_id=".$rRow["id"]."\">".$rRow["active_connections"]."</a>";
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($rPermissions["is_admin"]) {
                    if ($rRow["admin_enabled"] == 1) {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    } else {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    }
                }
                if ($rRow["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                $rReturn["data"][] = Array($rRow["id"], $rRow["username"], base64_decode($rRow["mac"]), $rRow["owner_name"], $rStatus, $rActive, $rTrial, $rExpDate, $rButtons);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "enigmas") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`users`.`id`", "`users`.`username`", "`enigma2_devices`.`mac`", "`reg_users`.`username`", "`users`.`enabled`", "`active_connections`", "`users`.`is_trial`", "`users`.`exp_date`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_reseller"]) {
        $rWhere[] = "`users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`users`.`username` LIKE '%{$rSearch}%' OR `enigma2_devices`.`mac` LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%' OR from_unixtime(`exp_date`) LIKE '%{$rSearch}%' OR `users`.`reseller_notes` LIKE '%{$rSearch}%' OR `users`.`admin_notes` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`users`.`admin_enabled` = 1 AND `users`.`enabled` = 1 AND (`users`.`exp_date` IS NULL OR `users`.`exp_date` > UNIX_TIMESTAMP()))";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "`users`.`enabled` = 0";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "`users`.`admin_enabled` = 0";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`users`.`exp_date` IS NOT NULL AND `users`.`exp_date` <= UNIX_TIMESTAMP())";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`users`.`is_trial` = 1";
        }
    }
    if ($rPermissions["is_admin"]) {
        if (strlen($_GET["reseller"]) > 0) {
            $rWhere[] = "`users`.`member_id` = ".intval($_GET["reseller"]);
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(`users`.`id`) AS `count` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `enigma2_devices` ON `enigma2_devices`.`user_id` = `users`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `users`.`id`, `users`.`username`, `enigma2_devices`.`mac`, `users`.`exp_date`, `users`.`admin_enabled`, `users`.`enabled`, `users`.`admin_notes`, `users`.`reseller_notes`, `users`.`max_connections`,  `users`.`is_trial`, `reg_users`.`username` AS `owner_name`, (SELECT count(*) FROM `user_activity_now` WHERE `users`.`id` = `user_activity_now`.`user_id`) AS `active_connections` FROM `users` LEFT JOIN `reg_users` ON `reg_users`.`id` = `users`.`member_id` INNER JOIN `enigma2_devices` ON `enigma2_devices`.`user_id` = `users`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if (!$rRow["admin_enabled"]) {
                    $rStatus = '<i class="text-danger fas fa-circle"></i>';
                } else {
                    if (!$rRow["enabled"]) {
                        $rStatus = '<i class="text-secondary fas fa-circle"></i>';
                    } else if (($rRow["exp_date"]) && ($rRow["exp_date"] < time())) {
                        $rStatus = '<i class="text-warning far fa-circle"></i>';
                    } else {
                        $rStatus = '<i class="text-success fas fa-circle"></i>';
                    }
                }
                if ($rRow["active_connections"] > 0) {
                    $rActive = '<i class="text-success fas fa-circle"></i>';
                } else {
                    $rActive = '<i class="text-warning far fa-circle"></i>';
                }
                if ($rRow["is_trial"]) {
                    $rTrial = '<i class="text-warning fas fa-circle"></i>';
                } else {
                    $rTrial = '<i class="text-secondary far fa-circle"></i>';
                }
                if ($rRow["exp_date"]) {
                    if ($rRow["exp_date"] < time()) {
                        $rExpDate = "<span class=\"expired\">".date("Y-m-d", $rRow["exp_date"])."</span>";
                    } else {
                        $rExpDate = date("Y-m-d", $rRow["exp_date"]);
                    }
                } else {
                    $rExpDate = "Never";
                }
                $rActiveConnections = "<a href=\"./live_connections.php?user_id=".$rRow["id"]."\">".$rRow["active_connections"]."</a>";
                if ($rPermissions["is_admin"]) {
                    $rButtons = '<a href="./user.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                } else {
                    $rButtons = '<a href="./user_reseller.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    ';
                }
                if ($rPermissions["is_admin"]) {
                    if ($rRow["admin_enabled"] == 1) {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'ban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    } else {
                        $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'unban\');"><i class="mdi mdi-power"></i></button>
                        ';
                    }
                }
                if ($rRow["enabled"] == 1) {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'disable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                } else {
                    $rButtons .= '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'enable\');"><i class="mdi mdi-lock"></i></button>
                    ';
                }
                if ((($rPermissions["is_reseller"]) && ($rPermissions["delete_users"])) OR ($rPermissions["is_admin"])) {
                    $rButtons .= '<button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                }
                $rReturn["data"][] = Array($rRow["id"], $rRow["username"], $rRow["mac"], $rRow["owner_name"], $rStatus, $rActive, $rTrial, $rExpDate, $rButtons);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "streams") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`streams`.`id`", "`streams`.`stream_display_name`", "`streams_sys`.`current_source`", "`streaming_servers`.`server_name`", "`clients`", "`streams_sys`.`stream_started`", false, false, "`streams_sys`.`bitrate`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    $rWhere[] = "`streams`.`type` in (1,3)";
    if (isset($_GET["stream_id"])) {
        $rWhere[] = "`streams`.`id` = ".intval($_GET["stream_id"]);
        $rOrderBy = "ORDER BY `streams_sys`.`server_stream_id` ASC";
    } else {
        if (strlen($_GET["search"]["value"]) > 0) {
            $rSearch = $db->real_escape_string($_GET["search"]["value"]);
            $rWhere[] = "(`streams`.`id` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `streams`.`notes` LIKE '%{$rSearch}%' OR `streams_sys`.`current_source` LIKE '%{$rSearch}%' OR `stream_categories`.`category_name` LIKE '%{$rSearch}%' OR `streaming_servers`.`server_name` LIKE '%{$rSearch}%')";
        }
        if (strlen($_GET["filter"]) > 0) {
            if ($_GET["filter"] == 1) {
                $rWhere[] = "(`streams_sys`.`monitor_pid` > 0 AND `streams_sys`.`pid` > 0)";
            } else if ($_GET["filter"] == 2) {
                $rWhere[] = "((`streams_sys`.`monitor_pid` IS NOT NULL AND `streams_sys`.`monitor_pid` > 0) AND (`streams_sys`.`pid` IS NULL OR `streams_sys`.`pid` <= 0) AND `streams_sys`.`stream_status` <> 0)";
            } else if ($_GET["filter"] == 3) {
                $rWhere[] = "(`streams`.`direct_source` = 0 AND (`streams_sys`.`monitor_pid` IS NULL OR `streams_sys`.`monitor_pid` <= 0) AND `streams_sys`.`on_demand` = 0)";
            } else if ($_GET["filter"] == 4) {
                $rWhere[] = "((`streams_sys`.`monitor_pid` IS NOT NULL AND `streams_sys`.`monitor_pid` > 0) AND (`streams_sys`.`pid` IS NULL OR `streams_sys`.`pid` <= 0) AND `streams_sys`.`stream_status` = 0)";
            } else if ($_GET["filter"] == 5) {
                $rWhere[] = "`streams_sys`.`on_demand` = 1";
            } else if ($_GET["filter"] == 6) {
                $rWhere[] = "`streams`.`direct_source` = 1";
            }
        }
        if (strlen($_GET["category"]) > 0) {
            $rWhere[] = "`streams`.`category_id` = ".intval($_GET["category"]);
        }
        if (strlen($_GET["server"]) > 0) {
            $rWhere[] = "`streams_sys`.`server_id` = ".intval($_GET["server"]);
        }
        if ($rOrder[$rOrderRow]) {
            $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `streams` LEFT JOIN `streams_sys` ON `streams_sys`.`stream_id` = `streams`.`id` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` LEFT JOIN `streaming_servers` ON `streaming_servers`.`id` = `streams_sys`.`server_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `streams`.`id`, `streams`.`stream_display_name`, `streams_sys`.`server_id`, `streams`.`notes`, `streams`.`direct_source`, `streams_sys`.`pid`, `streams_sys`.`monitor_pid`, `streams_sys`.`stream_status`, `streams_sys`.`stream_started`, `streams_sys`.`stream_info`, `streams_sys`.`current_source`, `streams_sys`.`bitrate`, `streams_sys`.`progress_info`, `streams_sys`.`on_demand`, `stream_categories`.`category_name`, `streaming_servers`.`server_name`, (SELECT COUNT(*) FROM `user_activity_now` WHERE `user_activity_now`.`server_id` = `streams_sys`.`server_id` AND `user_activity_now`.`stream_id` = `streams`.`id`) AS `clients` FROM `streams` LEFT JOIN `streams_sys` ON `streams_sys`.`stream_id` = `streams`.`id` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` LEFT JOIN `streaming_servers` ON `streaming_servers`.`id` = `streams_sys`.`server_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                $rCategory = $rRow["category_name"] ?: "No Category";
                $rStreamName = "<b>".$rRow['stream_display_name']."</b><br><span style='font-size:11px;'>{$rCategory}</span>";
                if ($rPermissions["is_admin"]) {
                    $rStreamSource = "<span style='font-size:11px;'>".parse_url($rRow["current_source"])['host']."</span>";
                } else {
                    $rStreamSource = "";
                }
                if ($rRow["server_name"]) {
                    if ($rPermissions["is_admin"]) {
                        $rServerName = $rRow["server_name"];
                    } else {
                        $rServerName = "Server #".$rRow["server_id"];
                    }
                } else {
                    $rServerName = "No Server Selected";
                }
                $rUptime = 0;
                $rActualStatus = 0;
                if (intval($rRow["direct_source"]) == 1) {
                    // Direct
                    $rActualStatus = 5;
                } else if ($rRow["monitor_pid"]) {
                    // Started
                    if (($rRow["pid"]) && ($rRow["pid"] > 0)) {
                        // Running
                        $rActualStatus = 1;
                        $rUptime = time() - intval($rRow["stream_started"]);
                    } else {
                        if (intval($rRow["stream_status"]) == 0) {
                            // Starting
                            $rActualStatus = 2;
                        } else {
                            // Stalled
                            $rActualStatus = 3;
                        }
                    }
                } else if (intval($rRow["on_demand"]) == 1) {
                    // On Demand
                    $rActualStatus = 4;
                } else {
                    // Stopped
                    $rActualStatus = 0;
                }
                $rClients = "<a href=\"./live_connections.php?stream_id=".$rRow["id"]."&server_id=".$rRow["server_id"]."\">".$rRow["clients"]."</a>";
                if ($rPermissions["is_admin"]) {
                    if ($rActualStatus == 1) {
                        $rUptime = sprintf('%02dh %02dm %02ds', ($rUptime/3600),($rUptime/60%60), ($rUptime%60));
                    } else {
                        $rUptime = $rStatusArray[$rActualStatus];
                    }
                } else {
                    $rUptime = "";
                }
                if ($rPermissions["is_admin"]) {
                    if (!$rRow["server_id"]) { $rRow["server_id"] = 0; }
                    if ((intval($rActualStatus) == 1) OR (intval($rActualStatus) == 3) OR ($rRow["on_demand"] == 1) OR ($rActualStatus == 5)) {
                        $rButtons = '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs api-stop" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'stop\');"><i class="mdi mdi-stop"></i></button>
                        ';
                        $rStatus = '';
                    } else {
                        $rButtons = '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs api-start" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'start\');"><i class="mdi mdi-play"></i></button>
                        ';
                        $rStatus = ' disabled';
                    }
                    $rButtons .= '<button type="button" class="btn btn-outline-primary waves-effect waves-light btn-xs api-restart" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'restart\');"'.$rStatus.'><i class="mdi mdi-refresh"></i></button>
                    <a href="./stream.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    <button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                } else {
                    $rButtons = "";
                }
                if ($rPermissions["is_admin"]) {
                    if (((intval($rActualStatus) == 1) OR ($rRow["on_demand"] == 1) OR ($rActualStatus == 5)) && ((strlen($rAdminSettings["admin_username"]) > 0) && (strlen($rAdminSettings["admin_password"]) > 0))) {
                        $rPlayer = '<button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs" onClick="player('.$rRow["id"].');"><i class="mdi mdi-play"></i></button>';
                    } else {
                        $rPlayer = '<button type="button" disabled class="btn btn-outline-secondary waves-effect waves-light btn-xs"><i class="mdi mdi-play"></i></button>';
                    }
                } else {
                    $rPlayer = "";
                }
                $rStreamInfoText = "<div style='font-size: 12px; text-align: center;'>Not Available</div>";
                $rStreamInfo = json_decode($rRow["stream_info"], True);
                if ($rActualStatus == 1) {
                    if (!isset($rStreamInfo["codecs"]["video"])) {
                        $rStreamInfo["codecs"]["video"] = "N/A";
                    }
                    if (!isset($rStreamInfo["codecs"]["audio"])) {
                        $rStreamInfo["codecs"]["audio"] = "N/A";
                    }
                    if ($rRow['bitrate'] == 0) { 
                        $rRow['bitrate'] = "?";
                    }
                    $rStreamInfoText = "<table style='font-size: 12px;' class='text-center'>
                        <tbody>
                            <tr>
                                <td class='col'>".$rRow['bitrate']." Kbps</td>
                                <td class='col' style='color: #20a009;'><i class='mdi mdi-video' data-name='mdi-video'></i></td>
                                <td class='col' style='color: #20a009;'><i class='mdi mdi-volume-high' data-name='mdi-volume-high'></i></td>
                            </tr>
                            <tr>
                                <td class='col'>".$rStreamInfo["codecs"]["video"]["width"]." x ".$rStreamInfo["codecs"]["video"]["height"]."</td>
                                <td class='col'>".$rStreamInfo["codecs"]["video"]["codec_name"]."</td>
                                <td class='col'>".$rStreamInfo["codecs"]["audio"]["codec_name"]."</td>
                            </tr>
                        </tbody>
                    </table>";
                }
                $rReturn["data"][] = Array($rRow["id"], $rStreamName, $rServerName, $rStreamSource, $rClients, $rUptime, $rButtons, $rPlayer, $rStreamInfoText);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "movies") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`streams`.`id`", "`streams`.`stream_display_name`", "`streams_sys`.`current_source`", "`streaming_servers`.`server_name`", "`clients`", "`streams_sys`.`stream_started`", false, false, "`streams_sys`.`bitrate`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    $rWhere[] = "`streams`.`type` = 2";
    if (isset($_GET["stream_id"])) {
        $rWhere[] = "`streams`.`id` = ".intval($_GET["stream_id"]);
        $rOrderBy = "ORDER BY `streams_sys`.`server_stream_id` ASC";
    } else {
        if (strlen($_GET["search"]["value"]) > 0) {
            $rSearch = $db->real_escape_string($_GET["search"]["value"]);
            $rWhere[] = "(`streams`.`id` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `streams`.`notes` LIKE '%{$rSearch}%' OR `streams_sys`.`current_source` LIKE '%{$rSearch}%' OR `stream_categories`.`category_name` LIKE '%{$rSearch}%' OR `streaming_servers`.`server_name` LIKE '%{$rSearch}%')";
        }
        if (strlen($_GET["filter"]) > 0) {
            if ($_GET["filter"] == 1) {
                $rWhere[] = "(`streams`.`direct_source` = 0 AND `streams_sys`.`pid` > 0 AND `streams_sys`.`to_analyze` = 0 AND `streams_sys`.`stream_status` <> 1)";
            } else if ($_GET["filter"] == 2) {
                $rWhere[] = "(`streams`.`direct_source` = 0 AND `streams_sys`.`pid` > 0 AND `streams_sys`.`to_analyze` = 1 AND `streams_sys`.`stream_status` <> 1)";
            } else if ($_GET["filter"] == 3) {
                $rWhere[] = "(`streams`.`direct_source` = 0 AND `streams_sys`.`stream_status` = 1)";
            } else if ($_GET["filter"] == 4) {
                $rWhere[] = "(`streams`.`direct_source` = 0 AND (`streams_sys`.`pid` IS NULL OR `streams_sys`.`pid` <= 0) AND `streams_sys`.`stream_status` <> 1)";
            } else if ($_GET["filter"] == 5) {
                $rWhere[] = "`streams`.`direct_source` = 1";
            }
        }
        if (strlen($_GET["category"]) > 0) {
            $rWhere[] = "`streams`.`category_id` = ".intval($_GET["category"]);
        }
        if ($rOrder[$rOrderRow]) {
            $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
        }
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `streams` LEFT JOIN `streams_sys` ON `streams_sys`.`stream_id` = `streams`.`id` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` LEFT JOIN `streaming_servers` ON `streaming_servers`.`id` = `streams_sys`.`server_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `streams`.`id`, `streams_sys`.`to_analyze`, `streams`.`target_container`, `streams`.`stream_display_name`, `streams_sys`.`server_id`, `streams`.`notes`, `streams`.`direct_source`, `streams_sys`.`pid`, `streams_sys`.`monitor_pid`, `streams_sys`.`stream_status`, `streams_sys`.`stream_started`, `streams_sys`.`stream_info`, `streams_sys`.`current_source`, `streams_sys`.`bitrate`, `streams_sys`.`progress_info`, `streams_sys`.`on_demand`, `stream_categories`.`category_name`, `streaming_servers`.`server_name`, (SELECT COUNT(*) FROM `user_activity_now` WHERE `user_activity_now`.`server_id` = `streams_sys`.`server_id` AND `user_activity_now`.`stream_id` = `streams`.`id`) AS `clients` FROM `streams` LEFT JOIN `streams_sys` ON `streams_sys`.`stream_id` = `streams`.`id` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` LEFT JOIN `streaming_servers` ON `streaming_servers`.`id` = `streams_sys`.`server_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                $rCategory = $rRow["category_name"] ?: "No Category";
                $rStreamName = "<b>".$rRow['stream_display_name']."</b><br><span style='font-size:11px;'>{$rCategory}</span>";
                if ($rPermissions["is_admin"]) {
                    $rStreamSource = "<span style='font-size:11px;'>".parse_url($rRow["current_source"])['host']."</span>";
                } else {
                    $rStreamSource = "";
                }
                if ($rRow["server_name"]) {
                    if ($rPermissions["is_admin"]) {
                        $rServerName = $rRow["server_name"];
                    } else {
                        $rServerName = "Server #".$rRow["server_id"];
                    }
                } else {
                    $rServerName = "No Server Selected";
                }
                $rUptime = 0;
                $rActualStatus = 0;
                if (intval($rRow["direct_source"]) == 1) {
                    // Direct
                    $rActualStatus = 3;
                } else if ($rRow["pid"]) {
                    if ($rRow["to_analyze"] == 1) {
                        $rActualStatus = 2; // Encoding
                    } else if ($rRow["stream_status"] == 1) {
                        $rActualStatus = 4; // Down
                    } else {
                        $rActualStatus = 1; // Encoded
                    }
                } else {
                    // Not Encoded
                    $rActualStatus = 0;
                }
                $rClients = "<a href=\"./live_connections.php?stream_id=".$rRow["id"]."&server_id=".$rRow["server_id"]."\">".$rRow["clients"]."</a>";
                if ($rPermissions["is_admin"]) {
                    if (!$rRow["server_id"]) { $rRow["server_id"] = 0; }
                    if (intval($rActualStatus) == 1) {
                        $rButtons = '<button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs api-start" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'start\');"><i class="mdi mdi-refresh"></i></button>
                        ';
                    } else if (intval($rActualStatus) == 3) {
                        $rButtons = '<button disabled type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs api-stop"><i class="mdi mdi-stop"></i></button>
                        ';
                    } else if (intval($rActualStatus) == 2) {
                        $rButtons = '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs api-stop" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'stop\');"><i class="mdi mdi-stop"></i></button>
                        ';
                    } else {
                        $rButtons = '<button type="button" class="btn btn-outline-success waves-effect waves-light btn-xs api-start" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'start\');"><i class="mdi mdi-play"></i></button>
                        ';
                    }
                    $rButtons .= '<a href="./movie.php?id='.$rRow["id"].'"><button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                    <button type="button" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api('.$rRow["id"].', '.$rRow["server_id"].', \'delete\');"><i class="mdi mdi-close"></i></button>';
                } else {
                    $rButtons = "";
                }
                if ($rPermissions["is_admin"]) {
                    if (((intval($rActualStatus) == 1) OR ($rActualStatus == 3)) && ((strlen($rAdminSettings["admin_username"]) > 0) && (strlen($rAdminSettings["admin_password"]) > 0))) {
                        $rPlayer = '<button type="button" class="btn btn-outline-info waves-effect waves-light btn-xs" onClick="player('.$rRow["id"].', \''.json_decode($rRow["target_container"], True)[0].'\');"><i class="mdi mdi-play"></i></button>';
                    } else {
                        $rPlayer = '<button type="button" disabled class="btn btn-outline-secondary waves-effect waves-light btn-xs"><i class="mdi mdi-play"></i></button>';
                    }
                } else {
                    $rPlayer = "";
                }
                $rStreamInfoText = "<div style='font-size: 12px; text-align: center;'>Not Available</div>";
                $rStreamInfo = json_decode($rRow["stream_info"], True);
                if ($rActualStatus == 1) {
                    if (!isset($rStreamInfo["codecs"]["video"])) {
                        $rStreamInfo["codecs"]["video"] = "N/A";
                    }
                    if (!isset($rStreamInfo["codecs"]["audio"])) {
                        $rStreamInfo["codecs"]["audio"] = "N/A";
                    }
                    if ($rRow['bitrate'] == 0) { 
                        $rRow['bitrate'] = "?";
                    }
                    $rStreamInfoText = "<table style='font-size: 12px;' class='text-center'>
                        <tbody>
                            <tr>
                                <td class='col'>".$rRow['bitrate']." Kbps</td>
                                <td class='col' style='color: #20a009;'><i class='mdi mdi-video' data-name='mdi-video'></i></td>
                                <td class='col' style='color: #20a009;'><i class='mdi mdi-volume-high' data-name='mdi-volume-high'></i></td>
                            </tr>
                            <tr>
                                <td class='col'>".$rStreamInfo["codecs"]["video"]["width"]." x ".$rStreamInfo["codecs"]["video"]["height"]."</td>
                                <td class='col'>".$rStreamInfo["codecs"]["video"]["codec_name"]."</td>
                                <td class='col'>".$rStreamInfo["codecs"]["audio"]["codec_name"]."</td>
                            </tr>
                        </tbody>
                    </table>";
                }
                $rReturn["data"][] = Array($rRow["id"], $rStreamName, $rServerName, $rClients, $rVODStatusArray[$rActualStatus], $rButtons, $rPlayer, $rStreamInfoText);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "user_activity") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`user_activity`.`activity_id`", "`users`.`username`", "`streams`.`stream_display_name`", "`streaming_servers`.`server_name`", "`user_activity`.`date_start`", "`user_activity`.`date_end`", "`user_activity`.`user_ip`", "`user_activity`.`geoip_country_code`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_reseller"]) {
        $rWhere[] = "`users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`user_activity`.`user_agent` LIKE '%{$rSearch}%' OR `user_activity`.`user_agent` LIKE '%{$rSearch}%' OR `user_activity`.`user_ip` LIKE '%{$rSearch}%' OR `user_activity`.`container` LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`user_activity`.`date_start`) LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`user_activity`.`date_end`) LIKE '%{$rSearch}%' OR `user_activity`.`geoip_country_code` LIKE '%{$rSearch}%' OR `users`.`username` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `streaming_servers`.`server_name` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["range"]) > 0) {
        $rStartTime = substr($_GET["range"], 0, 10);
        $rEndTime = substr($_GET["range"], strlen($_GET["range"])-10, 10);
        if (!$rStartTime = strtotime($rStartTime. " 00:00:00")) {
            $rStartTime = null;
        }
        if (!$rEndTime = strtotime($rEndTime." 23:59:59")) {
            $rEndTime = null;
        }
        if (($rStartTime) && ($rEndTime)) {
            $rWhere[] = "(`user_activity`.`date_start` >= ".$rStartTime." AND `user_activity`.`date_end` <= ".$rEndTime.")";
        }
    }
    if (strlen($_GET["server"]) > 0) {
        $rWhere[] = "`user_activity`.`server_id` = ".intval($_GET["server"]);
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `user_activity` LEFT JOIN `users` ON `user_activity`.`user_id` = `users`.`id` LEFT JOIN `streams` ON `user_activity`.`stream_id` = `streams`.`id` LEFT JOIN `streaming_servers` ON `user_activity`.`server_id` = `streaming_servers`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `user_activity`.`activity_id`, `user_activity`.`user_id`, `user_activity`.`stream_id`, `user_activity`.`server_id`, `user_activity`.`user_agent`, `user_activity`.`user_ip`, `user_activity`.`container`, `user_activity`.`date_start`, `user_activity`.`date_end`, `user_activity`.`geoip_country_code`, `users`.`username`, `streams`.`stream_display_name`, `streams`.`type`, `streaming_servers`.`server_name` FROM `user_activity` LEFT JOIN `users` ON `user_activity`.`user_id` = `users`.`id` LEFT JOIN `streams` ON `user_activity`.`stream_id` = `streams`.`id` LEFT JOIN `streaming_servers` ON `user_activity`.`server_id` = `streaming_servers`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if ($rPermissions["is_admin"]) {
                    $rUsername = "<a href='./user.php?id=".$rRow["user_id"]."'>".$rRow["username"]."</a>";
                } else {
                    $rUsername = "<a href='./user_reseller.php?id=".$rRow["user_id"]."'>".$rRow["username"]."</a>";
                }
                if ($rPermissions["is_admin"]) {
                    if ($rRow["type"] == 1) {
                        $rChannel = "<a href='./stream.php?id=".$rRow["stream_id"]."'>".$rRow["stream_display_name"]."</a>";
                    } else if ($rRow["type"] == 2) {
                        $rChannel = "<a href='./movie.php?id=".$rRow["stream_id"]."'>".$rRow["stream_display_name"]."</a>";
                    } else {
                        $rChannel = $rRow["stream_display_name"];
                    }
                } else {
                    $rChannel = $rRow["stream_display_name"];
                }
                if ($rPermissions["is_admin"]) {
                    $rServer = "<a href='./server.php?id=".$rRow["server_id"]."'>".$rRow["server_name"]."</a>";
                } else {
                    $rServer = "Server #".$rRow["server_id"];
                }
                if ($rRow["user_ip"]) {
                    $rIP = "<a target='_blank' href='https://www.ip-tracker.org/locator/ip-lookup.php?ip=".$rRow["user_ip"]."'>".$rRow["user_ip"]."</a>";
                } else {
                    $rIP = "";
                }
                if (strlen($rRow["geoip_country_code"]) > 0) {
                    $rGeoCountry = "<img src='https://www.ip-tracker.org/images/ip-flags/".strtolower($rRow["geoip_country_code"]).".png'></img>";
                } else {
                    $rGeoCountry = "";
                }
                if ($rRow["date_start"]) {
                    $rStart = date("Y-m-d H:i:s", $rRow["date_start"]);
                } else {
                    $rStart = "";
                }
                if ($rRow["date_end"]) {
                    $rStop = date("Y-m-d H:i:s", $rRow["date_end"]);
                } else {
                    $rStop = "";
                }
                $rReturn["data"][] = Array($rRow["activity_id"], $rUsername, $rChannel, $rServer, $rStart, $rStop, $rIP, $rGeoCountry);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "live_connections") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`user_activity_now`.`activity_id`", "`user_activity_now`.`divergence`", "`users`.`username`", "`streams`.`stream_display_name`", "`streaming_servers`.`server_name`", "`user_activity_now`.`date_start`", "`user_activity_now`.`user_ip`", "`user_activity_now`.`geoip_country_code`", false);
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if ($rPermissions["is_reseller"]) {
        $rWhere[] = "`users`.`member_id` IN (".join(",", array_keys(getRegisteredUsers($rUserInfo["id"]))).")";
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`user_activity_now`.`user_agent` LIKE '%{$rSearch}%' OR `user_activity_now`.`user_agent` LIKE '%{$rSearch}%' OR `user_activity_now`.`user_ip` LIKE '%{$rSearch}%' OR `user_activity_now`.`container` LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`user_activity_now`.`date_start`) LIKE '%{$rSearch}%' OR `user_activity_now`.`geoip_country_code` LIKE '%{$rSearch}%' OR `users`.`username` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `streaming_servers`.`server_name` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["server_id"]) > 0) {
        $rWhere[] = "`user_activity_now`.`server_id` = ".intval($_GET["server_id"]);
    }
    if (strlen($_GET["stream_id"]) > 0) {
        $rWhere[] = "`user_activity_now`.`stream_id` = ".intval($_GET["stream_id"]);
    }
    if (strlen($_GET["user_id"]) > 0) {
        $rWhere[] = "`user_activity_now`.`user_id` = ".intval($_GET["user_id"]);
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `user_activity_now` LEFT JOIN `users` ON `user_activity_now`.`user_id` = `users`.`id` LEFT JOIN `streams` ON `user_activity_now`.`stream_id` = `streams`.`id` LEFT JOIN `streaming_servers` ON `user_activity_now`.`server_id` = `streaming_servers`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `user_activity_now`.`activity_id`, `user_activity_now`.`divergence`, `user_activity_now`.`user_id`, `user_activity_now`.`stream_id`, `user_activity_now`.`server_id`, `user_activity_now`.`user_agent`, `user_activity_now`.`user_ip`, `user_activity_now`.`container`, `user_activity_now`.`pid`, `user_activity_now`.`date_start`, `user_activity_now`.`geoip_country_code`, `users`.`username`, `streams`.`stream_display_name`, `streams`.`type`, `streaming_servers`.`server_name` FROM `user_activity_now` LEFT JOIN `users` ON `user_activity_now`.`user_id` = `users`.`id` LEFT JOIN `streams` ON `user_activity_now`.`stream_id` = `streams`.`id` LEFT JOIN `streaming_servers` ON `user_activity_now`.`server_id` = `streaming_servers`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                // Format Rows
                if ($rRow["divergence"] <= 10) {
                    $rDivergence = '<i class="text-success fas fa-circle"></i>';
                } else if ($rRow["divergence"] <= 50) {
                    $rDivergence = '<i class="text-warning fas fa-circle"></i>';
                } else {
                    $rDivergence = '<i class="text-danger fas fa-circle"></i>';
                }
                if ($rPermissions["is_admin"]) {
                    $rUsername = "<a href='./user.php?id=".$rRow["user_id"]."'>".$rRow["username"]."</a>";
                } else {
                    $rUsername = "<a href='./user_reseller.php?id=".$rRow["user_id"]."'>".$rRow["username"]."</a>";
                }
                if ($rPermissions["is_admin"]) {
                    if ($rRow["type"] == 1) {
                        $rChannel = "<a href='./stream.php?id=".$rRow["stream_id"]."'>".$rRow["stream_display_name"]."</a>";
                    } else if ($rRow["type"] == 2) {
                        $rChannel = "<a href='./movie.php?id=".$rRow["stream_id"]."'>".$rRow["stream_display_name"]."</a>";
                    } else {
                        $rChannel = $rRow["stream_display_name"];
                    }
                } else {
                    $rChannel = $rRow["stream_display_name"];
                }
                if ($rPermissions["is_admin"]) {
                    $rServer = "<a href='./server.php?id=".$rRow["server_id"]."'>".$rRow["server_name"]."</a>";
                } else {
                    $rServer = "Server #".$rRow["server_id"];
                }
                if ($rRow["user_ip"]) {
                    $rIP = "<a target='_blank' href='https://www.ip-tracker.org/locator/ip-lookup.php?ip=".$rRow["user_ip"]."'>".$rRow["user_ip"]."</a>";
                } else {
                    $rIP = "";
                }
                if (strlen($rRow["geoip_country_code"]) > 0) {
                    $rGeoCountry = "<img src='https://www.ip-tracker.org/images/ip-flags/".strtolower($rRow["geoip_country_code"]).".png'></img>";
                } else {
                    $rGeoCountry = "";
                }
                if ($rRow["date_start"]) {
                    $rTime = intval(time()) - intval($rRow["date_start"]);
					$rTime = sprintf('%02d:%02d:%02d', ($rTime/3600),($rTime/60%60), $rTime%60);
                } else {
                    $rTime = "";
                }
                $rButtons = '<button type="button" class="btn btn-outline-warning waves-effect waves-light btn-xs" onClick="api('.$rRow["pid"].', \'kill\');"><i class="fas fa-hammer"></i></button>';
                $rReturn["data"][] = Array($rRow["activity_id"], $rDivergence, $rUsername, $rChannel, $rServer, $rTime, $rIP, $rGeoCountry, $rButtons);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "stream_list") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`streams`.`id`", "`streams`.`stream_display_name`", "`stream_categories`.`category_name`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    $rWhere[] = "`streams`.`type` = 1";
    if (strlen($_GET["category"]) > 0) {
        $rWhere[] = "`streams`.`category_id` = ".intval($_GET["category"]);
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`streams`.`id` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `stream_categories`.`category_name` LIKE '%{$rSearch}%')";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `streams` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `streams`.`id`, `streams`.`stream_display_name`, `stream_categories`.`category_name` FROM `streams` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                $rReturn["data"][] = Array($rRow["id"], $rRow["stream_display_name"], $rRow["category_name"]);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "movie_list") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`streams`.`id`", "`streams`.`stream_display_name`", "`stream_categories`.`category_name`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    $rWhere[] = "`streams`.`type` = 2";
    if (strlen($_GET["category"]) > 0) {
        $rWhere[] = "`streams`.`category_id` = ".intval($_GET["category"]);
    }
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`streams`.`id` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `stream_categories`.`category_name` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["filter"]) > 0) {
        if ($_GET["filter"] == 1) {
            $rWhere[] = "(`streams`.`direct_source` = 0 AND `streams_sys`.`pid` > 0 AND `streams_sys`.`to_analyze` = 0 AND `streams_sys`.`stream_status` <> 1)";
        } else if ($_GET["filter"] == 2) {
            $rWhere[] = "(`streams`.`direct_source` = 0 AND `streams_sys`.`pid` > 0 AND `streams_sys`.`to_analyze` = 1 AND `streams_sys`.`stream_status` <> 1)";
        } else if ($_GET["filter"] == 3) {
            $rWhere[] = "(`streams`.`direct_source` = 0 AND `streams_sys`.`stream_status` = 1)";
        } else if ($_GET["filter"] == 4) {
            $rWhere[] = "(`streams`.`direct_source` = 0 AND (`streams_sys`.`pid` IS NULL OR `streams_sys`.`pid` <= 0) AND `streams_sys`.`stream_status` <> 1)";
        } else if ($_GET["filter"] == 5) {
            $rWhere[] = "`streams`.`direct_source` = 1";
        }
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `streams` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` LEFT JOIN `streams_sys` ON `streams_sys`.`stream_id` = `streams`.`id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `streams`.`id`, `streams`.`stream_display_name`, `stream_categories`.`category_name`, `streams`.`direct_source`, `streams_sys`.`to_analyze`, `streams_sys`.`pid` FROM `streams` LEFT JOIN `stream_categories` ON `stream_categories`.`id` = `streams`.`category_id` LEFT JOIN `streams_sys` ON `streams_sys`.`stream_id` = `streams`.`id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                $rActualStatus = 0;
                if (intval($rRow["direct_source"]) == 1) {
                    // Direct
                    $rActualStatus = 3;
                } else if ($rRow["pid"]) {
                    if ($rRow["to_analyze"] == 1) {
                        $rActualStatus = 2; // Encoding
                    } else if ($rRow["stream_status"] == 1) {
                        $rActualStatus = 4; // Down
                    } else {
                        $rActualStatus = 1; // Encoded
                    }
                } else {
                    // Not Encoded
                    $rActualStatus = 0;
                }
                $rReturn["data"][] = Array($rRow["id"], $rRow["stream_display_name"], $rRow["category_name"], $rVODStatusArray[$rActualStatus]);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "credits_log") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`credits_log`.`id`", "`owner_username`", "`target_username`", "`credits_log`.`amount`", "`credits_log`.`reason`", "`date`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`target`.`username` LIKE '%{$rSearch}%' OR `owner`.`username` LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`date`) LIKE '%{$rSearch}%' OR `credits_log`.`amount` LIKE '%{$rSearch}%' OR `credits_log`.`reason` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["range"]) > 0) {
        $rStartTime = substr($_GET["range"], 0, 10);
        $rEndTime = substr($_GET["range"], strlen($_GET["range"])-10, 10);
        if (!$rStartTime = strtotime($rStartTime. " 00:00:00")) {
            $rStartTime = null;
        }
        if (!$rEndTime = strtotime($rEndTime." 23:59:59")) {
            $rEndTime = null;
        }
        if (($rStartTime) && ($rEndTime)) {
            $rWhere[] = "(`credits_log`.`date` >= ".$rStartTime." AND `credits_log`.`date` <= ".$rEndTime.")";
        }
    }
    if (strlen($_GET["reseller"]) > 0) {
        $rWhere[] = "(`credits_log`.`target_id` = ".intval($_GET["reseller"])." OR `credits_log`.`admin_id` = ".intval($_GET["reseller"]).")";
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `credits_log` LEFT JOIN `reg_users` AS `target` ON `target`.`id` = `credits_log`.`target_id` LEFT JOIN `reg_users` AS `owner` ON `owner`.`id` = `credits_log`.`admin_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `credits_log`.`id`, `credits_log`.`target_id`, `credits_log`.`admin_id`, `target`.`username` AS `target_username`, `owner`.`username` AS `owner_username`, `amount`, FROM_UNIXTIME(`date`) AS `date`, `credits_log`.`reason` FROM `credits_log` LEFT JOIN `reg_users` AS `target` ON `target`.`id` = `credits_log`.`target_id` LEFT JOIN `reg_users` AS `owner` ON `owner`.`id` = `credits_log`.`admin_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                $rReturn["data"][] = Array($rRow["id"], "<a href='./reg_user.php?id=".$rRow["admin_id"]."'>".$rRow["owner_username"]."</a>", "<a href='./reg_user.php?id=".$rRow["target_id"]."'>".$rRow["target_username"]."</a>", $rRow["amount"], $rRow["reason"], $rRow["date"]);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "client_logs") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`client_logs`.`id`", "`users`.`username`", "`streams`.`stream_display_name`", "`client_logs`,`client_status`", "`client_logs`.`user_agent`", "`client_logs`.`ip`", "`client_logs`.`date`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`client_logs`.`client_status` LIKE '%{$rSearch}%' OR `client_logs`.`query_string` LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`date`) LIKE '%{$rSearch}%' OR `client_logs`.`user_agent` LIKE '%{$rSearch}%' OR `client_logs`.`ip` LIKE '%{$rSearch}%' OR `streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `users`.`username` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["range"]) > 0) {
        $rStartTime = substr($_GET["range"], 0, 10);
        $rEndTime = substr($_GET["range"], strlen($_GET["range"])-10, 10);
        if (!$rStartTime = strtotime($rStartTime. " 00:00:00")) {
            $rStartTime = null;
        }
        if (!$rEndTime = strtotime($rEndTime." 23:59:59")) {
            $rEndTime = null;
        }
        if (($rStartTime) && ($rEndTime)) {
            $rWhere[] = "(`client_logs`.`date` >= ".$rStartTime." AND `client_logs`.`date` <= ".$rEndTime.")";
        }
    }
    if (strlen($_GET["filter"]) > 0) {
        $rWhere[] = "`client_logs`.`client_status` = '".$db->real_escape_string($_GET["filter"])."'";
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `client_logs` LEFT JOIN `streams` ON `streams`.`id` = `client_logs`.`stream_id` LEFT JOIN `users` ON `users`.`id` = `client_logs`.`user_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `client_logs`.`id`, `client_logs`.`user_id`, `client_logs`.`stream_id`, `streams`.`stream_display_name`, `users`.`username`, `client_logs`.`client_status`, `client_logs`.`query_string`, `client_logs`.`user_agent`, `client_logs`.`ip`, FROM_UNIXTIME(`client_logs`.`date`) AS `date` FROM `client_logs` LEFT JOIN `streams` ON `streams`.`id` = `client_logs`.`stream_id` LEFT JOIN `users` ON `users`.`id` = `client_logs`.`user_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                $rReturn["data"][] = Array($rRow["id"], "<a href='./user.php?id=".$rRow["user_id"]."'>".$rRow["username"]."</a>", "<a href='./stream.php?id=".$rRow["stream_id"]."'>".$rRow["stream_display_name"]."</a>", $rRow["client_status"], $rRow["user_agent"], "<a target='_blank' href='https://www.ip-tracker.org/locator/ip-lookup.php?ip=".$rRow["ip"]."'>".$rRow["ip"]."</a>", $rRow["date"]);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "reg_user_logs") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`reg_userlog`.`id`", "`reg_users`.`username`", "`reg_userlog`.`username`", "`reg_userlog`.`type`", "`reg_userlog`.`date`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`reg_userlog`.`username` LIKE '%{$rSearch}%' OR `reg_userlog`.`type` LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`date`) LIKE '%{$rSearch}%' OR `reg_users`.`username` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["range"]) > 0) {
        $rStartTime = substr($_GET["range"], 0, 10);
        $rEndTime = substr($_GET["range"], strlen($_GET["range"])-10, 10);
        if (!$rStartTime = strtotime($rStartTime. " 00:00:00")) {
            $rStartTime = null;
        }
        if (!$rEndTime = strtotime($rEndTime." 23:59:59")) {
            $rEndTime = null;
        }
        if (($rStartTime) && ($rEndTime)) {
            $rWhere[] = "(`reg_userlog`.`date` >= ".$rStartTime." AND `reg_userlog`.`date` <= ".$rEndTime.")";
        }
    }
    if (strlen($_GET["reseller"]) > 0) {
        $rWhere[] = "`reg_userlog`.`owner` = '".intval($_GET["reseller"])."'";
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `reg_userlog` LEFT JOIN `reg_users` ON `reg_users`.`id` = `reg_userlog`.`owner` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `reg_userlog`.`id`, `reg_userlog`.`owner` as `owner_id`, `reg_users`.`username` AS `owner`, `reg_userlog`.`username`, `reg_userlog`.`type`, FROM_UNIXTIME(`reg_userlog`.`date`) AS `date` FROM `reg_userlog` LEFT JOIN `reg_users` ON `reg_users`.`id` = `reg_userlog`.`owner` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                $rReturn["data"][] = Array($rRow["id"], "<a href='./reg_user.php?id=".$rRow["owner_id"]."'>".$rRow["owner"]."</a>", $rRow["username"], strip_tags($rRow["type"]), $rRow["date"]);
            }
        }
    }
    echo json_encode($rReturn);exit;
} else if ($rType == "stream_logs") {
    $rReturn = Array("draw" => $_GET["draw"], "recordsTotal" => 0, "recordsFiltered" => 0, "data" => Array());
    $rOrder = Array("`stream_logs`.`id`", "`streams`.`stream_display_name`", "`streaming_servers`.`server_name`", "`stream_logs`.`error`", "`stream_logs`.`date`");
    if (strlen($_GET["order"][0]["column"]) > 0) {
        $rOrderRow = intval($_GET["order"][0]["column"]);
    } else {
        $rOrderRow = 0;
    }
    $rWhere = Array();
    if (strlen($_GET["search"]["value"]) > 0) {
        $rSearch = $db->real_escape_string($_GET["search"]["value"]);
        $rWhere[] = "(`streams`.`stream_display_name` LIKE '%{$rSearch}%' OR `streaming_servers`.`server_name` LIKE '%{$rSearch}%' OR FROM_UNIXTIME(`date`) LIKE '%{$rSearch}%' OR `stream_logs`.`error` LIKE '%{$rSearch}%')";
    }
    if (strlen($_GET["range"]) > 0) {
        $rStartTime = substr($_GET["range"], 0, 10);
        $rEndTime = substr($_GET["range"], strlen($_GET["range"])-10, 10);
        if (!$rStartTime = strtotime($rStartTime. " 00:00:00")) {
            $rStartTime = null;
        }
        if (!$rEndTime = strtotime($rEndTime." 23:59:59")) {
            $rEndTime = null;
        }
        if (($rStartTime) && ($rEndTime)) {
            $rWhere[] = "(`stream_logs`.`date` >= ".$rStartTime." AND `stream_logs`.`date` <= ".$rEndTime.")";
        }
    }
    if (strlen($_GET["server"]) > 0) {
        $rWhere[] = "`stream_logs`.`server_id` = '".intval($_GET["server"])."'";
    }
    if (count($rWhere) > 0) {
        $rWhereString = "WHERE ".join(" AND ", $rWhere);
    } else {
        $rWhereString = "";
    }
    if ($rOrder[$rOrderRow]) {
        $rOrderBy = "ORDER BY ".$rOrder[$rOrderRow]." ".$_GET["order"][0]["dir"];
    }
    $rCountQuery = "SELECT COUNT(*) AS `count` FROM `stream_logs` LEFT JOIN `streams` ON `streams`.`id` = `stream_logs`.`stream_id` LEFT JOIN `streaming_servers` ON `streaming_servers`.`id` = `stream_logs`.`server_id` {$rWhereString};";
    $rResult = $db->query($rCountQuery);
    if (($rResult) && ($rResult->num_rows == 1)) {
        $rReturn["recordsTotal"] = $rResult->fetch_assoc()["count"];
    } else {
        $rReturn["recordsTotal"] = 0;
    }
    $rReturn["recordsFiltered"] = $rReturn["recordsTotal"];
    if ($rReturn["recordsTotal"] > 0) {
        $rQuery = "SELECT `stream_logs`.`id`, `stream_logs`.`stream_id`, `stream_logs`.`server_id`, `streams`.`stream_display_name`, `streaming_servers`.`server_name`, `stream_logs`.`error`, FROM_UNIXTIME(`stream_logs`.`date`) AS `date` FROM `stream_logs` LEFT JOIN `streams` ON `streams`.`id` = `stream_logs`.`stream_id` LEFT JOIN `streaming_servers` ON `streaming_servers`.`id` = `stream_logs`.`server_id` {$rWhereString} {$rOrderBy} LIMIT {$rStart}, {$rLimit};";
        $rResult = $db->query($rQuery);
        if (($rResult) && ($rResult->num_rows > 0)) {
            while ($rRow = $rResult->fetch_assoc()) {
                $rReturn["data"][] = Array($rRow["id"], "<a href='".$rRow["stream_id"]."'>".$rRow["stream_display_name"]."</a>", "<a href='./server.php?id=".$rRow["server_id"]."'>".$rRow["server_name"]."</a>", $rRow["error"], $rRow["date"]);
            }
        }
    }
    echo json_encode($rReturn);exit;
}
?>