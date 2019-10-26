<?php
$rRelease = 14;

include "../forum/config.php";
if (!$db = new mysqli($dbhost, $dbuser, $dbpasswd, $dbname, $dbport)) { exit("No MySQL connection!"); }

$rDonators = Array();
$result = $db->query("SELECT `user_id` FROM `phpbb_user_group` WHERE `group_id` = 8;");
while ($row = $result->fetch_assoc()) {
    $rDonators[] = intval($row["user_id"]);
}

if ((isset($_GET["username"])) && (isset($_GET["password"]))) {
    $rUsername = $_GET["username"];
    $rPassword = $_GET["password"];
    $result = $db->query("SELECT `user_id`, `user_password` FROM `phpbb_users` WHERE LOWER(`username`) = '".$db->real_escape_string(strtolower($rUsername))."';");
    if (($result) && ($result->num_rows > 0)) {
        $rUser = $result->fetch_assoc();
        if ((crypt($rPassword, $rUser["user_password"]) == $rUser["user_password"]) && (in_array(intval($rUser["user_id"]), $rDonators))) {
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary"); 
            header("Content-disposition: attachment; filename=\"release_{$rRelease}.zip\"");
            readfile("../../donator/{$rRelease}.zip");
        }
    }
}
?>