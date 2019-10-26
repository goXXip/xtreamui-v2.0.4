<?php
include "functions.php";
if (isset($_SESSION['hash'])) { header("Location: ./dashboard.php"); exit; }

$rAdminSettings = getAdminSettings();

$rGA = new PHPGangsta_GoogleAuthenticator();
if ((isset($_POST["username"])) && (isset($_POST["password"]))) {
    $rUserInfo = doLogin($_POST["username"], $_POST["password"]);
    if (isset($rUserInfo)) {
        if ((isset($rAdminSettings["google_2factor"])) && ($rAdminSettings["google_2factor"])) {
            if (strlen($rUserInfo["google_2fa_sec"]) == 0) {
                $rGA = new PHPGangsta_GoogleAuthenticator();
                $rSecret = $rGA->createSecret();
                $rUserInfo["google_2fa_sec"] = $rSecret;
                $db->query("UPDATE `reg_users` SET `google_2fa_sec` = '".$db->real_escape_string($rSecret)."' WHERE `id` = ".intval($rUserInfo["id"]).";");
                $rNew2F = true;
            }
            $rQR = $rGA->getQRCodeGoogleUrl('Xtream UI', $rUserInfo["google_2fa_sec"]);
        } else {
            $rPermissions = getPermissions($rUserInfo["member_group_id"]);
            if (($rPermissions) && ((($rPermissions["is_admin"]) OR ($rPermissions["is_reseller"])) && ((!$rPermissions["is_banned"]) && ($rUserInfo["status"] == 1)))) {
                $db->query("UPDATE `reg_users` SET `last_login` = UNIX_TIMESTAMP(), `ip` = '".$db->real_escape_string(getIP())."' WHERE `id` = ".intval($rUserInfo["id"]).";");
                $_SESSION['hash'] = md5($rUserInfo["username"]);
                if ($rPermissions["is_admin"]) {
                    header("Location: ./dashboard.php");
                } else {
                    $db->query("INSERT INTO `reg_userlog`(`owner`, `username`, `password`, `date`, `type`) VALUES(".intval($rUserInfo["id"]).", '', '', ".intval(time()).", '[<b>UserPanel</b> -> <u>Logged In</u>]');");
                    header("Location: ./reseller.php");
                }
            } else if (($rPermissions) && ((($rPermissions["is_admin"]) OR ($rPermissions["is_reseller"])) && ($rPermissions["is_banned"]))) {
                $_STATUS = 2;
            } else if (($rPermissions) && ((($rPermissions["is_admin"]) OR ($rPermissions["is_reseller"])) && (!$rUserInfo["status"]))) {
                $_STATUS = 3;
            } else {
                $_STATUS = 4;
            }
        }
    } else {
        $_STATUS = 0;
    }
} else if ((isset($_POST["gauth"])) && (isset($_POST["hash"]))) {
    $rUserInfo = getRegisteredUserHash($_POST["hash"]);
    if ($rUserInfo) {
        if ($rGA->verifyCode($rUserInfo["google_2fa_sec"], $_POST["gauth"], 2)) {
            $rPermissions = getPermissions($rUserInfo["member_group_id"]);
            if (($rPermissions) && ((($rPermissions["is_admin"]) OR ($rPermissions["is_reseller"])) && ((!$rPermissions["is_banned"]) && ($rUserInfo["status"] == 1)))) {
                $db->query("UPDATE `reg_users` SET `last_login` = UNIX_TIMESTAMP(), `ip` = '".$db->real_escape_string(getIP())."' WHERE `id` = ".intval($rUserInfo["id"]).";");
                $_SESSION['hash'] = md5($rUserInfo["username"]);
                if ($rPermissions["is_admin"]) {
                    header("Location: ./dashboard.php");
                } else {
                    $db->query("INSERT INTO `reg_userlog`(`owner`, `username`, `password`, `date`, `type`) VALUES(".intval($rUserInfo["id"]).", '', '', ".intval(time()).", '[<b>UserPanel</b> -> <u>Logged In</u>]');");
                    header("Location: ./reseller.php");
                }
            } else if (($rPermissions) && ((($rPermissions["is_admin"]) OR ($rPermissions["is_reseller"])) && ($rPermissions["is_banned"]))) {
                $_STATUS = 2;
            } else if (($rPermissions) && ((($rPermissions["is_admin"]) OR ($rPermissions["is_reseller"])) && (!$rUserInfo["status"]))) {
                $_STATUS = 3;
            } else {
                $_STATUS = 4;
            }
        } else {
            $rQR = $rGA->getQRCodeGoogleUrl('Xtream UI', $rUserInfo["google_2fa_sec"]);
            $_STATUS = 1;
        }
    } else {
        $_STATUS = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Xtream UI - Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">
        <!-- App css -->
        <link href="assets/css/bootstrap.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/app.css" rel="stylesheet" type="text/css" />
    </head>
    <body class="authentication-bg authentication-bg-pattern">
        <div class="account-pages mt-5 mb-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <?php if ((isset($_STATUS)) && ($_STATUS == 0)) { ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Incorrect username or password! Please try again.
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 1)) { ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Incorrect Two Factor authentication code entered!
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 2)) { ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            You have been banned from accessing this system.
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 3)) { ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Your account has been disabled, you are no longer able to access the system.
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 4)) { ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            You do not have permission to access the user interface.
                        </div>
                        <?php } ?>
                        <div class="card">
                            <div class="card-body p-4">
                                <div class="text-center w-75 m-auto">
                                    <span><img src="assets/images/logo-back.png" width="200px" alt=""></span>
                                    <p class="text-muted mb-4 mt-3"></p>
                                </div>
                                <h5 class="auth-title">Admin & Reseller Interface</h5>
                                <form action="./login.php" method="POST">
                                    <?php if (!isset($rQR)) { ?>
                                    <div class="form-group mb-3">
                                        <label for="username">Username</label>
                                        <input class="form-control" autocomplete="off" type="text" id="username" name="username" required="" placeholder="Enter your username">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="password">Password</label>
                                        <input class="form-control" autocomplete="off" type="password" required="" id="password" name="password" placeholder="Enter your password">
                                    </div>
                                    <?php } else { ?>
                                    <input type="hidden" name="hash" value="<?=md5($rUserInfo["username"])?>" />
                                    <?php if (isset($rNew2F)) { ?>
                                    <div class="form-group mb-3 text-center">
                                        <p>Please scan your Two Factor QR into the Google Authenticator application. You will not see this code again.</p>
                                        <img src="<?=$rQR?>">
                                    </div>
                                    <?php } ?>
                                    <div class="form-group mb-3">
                                        <label for="gauth">Google Authenticator Code</label>
                                        <input class="form-control" autocomplete="off" type="gauth" required="" id="gauth" name="gauth" placeholder="Enter your auth code">
                                    </div>
                                    <?php } ?>
                                    <div class="form-group mb-0 text-center">
                                        <button class="btn btn-danger btn-block" type="submit"> LOGIN </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
    </body>
</html>