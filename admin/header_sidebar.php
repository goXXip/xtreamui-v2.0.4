<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Xtream UI</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- third party css -->
        <link href="assets/libs/jquery-nice-select/nice-select.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/switchery/switchery.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/datatables/responsive.bootstrap4.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/datatables/buttons.bootstrap4.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/datatables/select.bootstrap4.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/bootstrap-select/bootstrap-select.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/treeview/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/clockpicker/bootstrap-clockpicker.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/nestable2/jquery.nestable.min.css" rel="stylesheet" />
        <link href="assets/libs/magnific-popup/magnific-popup.css" rel="stylesheet" type="text/css" />
        <!-- third party css end -->

        <!-- App css -->
        <link href="assets/css/bootstrap.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/app_sidebar.css" rel="stylesheet" type="text/css" />

    </head>
    <body class="enlarged topbar-dark left-side-menu-light" data-keep-enlarged="true">
        <!-- Begin page -->
        <div id="wrapper">
            <!-- Topbar Start -->
            <div class="navbar-custom">
                <ul class="list-unstyled topnav-menu float-right mb-0">
                    <?php if (($rServerError) && ($rPermissions["is_admin"] == 1)) { ?>
                    <li class="notification-list">
                        <a href="./servers.php" class="nav-link right-bar-toggle waves-effect text-warning">
                            <i class="mdi mdi-wifi-strength-off noti-icon"></i>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if ($rPermissions["is_reseller"]) { ?>
                    <li class="notification-list">
                        <a class="nav-link text-white waves-effect" href="#" role="button">
                            <i class="mdi mdi-coins noti-icon"></i>
                            <?php if (floor($rUserInfo["credits"]) == $rUserInfo["credits"]) {
                                echo number_format($rUserInfo["credits"], 0);
                            } else {
                                echo number_format($rUserInfo["credits"], 2);
                            } ?>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if ($rPermissions["is_admin"] == 1) { ?>
                    <li class="dropdown notification-list">
                        <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect text-white" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <i class="fe-settings noti-icon"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                            <a href="./settings.php" class="dropdown-item notify-item"><span>Settings</span></a>
                            <a href="./epgs.php" class="dropdown-item notify-item"><span>EPG's</span></a>
                            <a href="./subresellers.php" class="dropdown-item notify-item"><span>Subresellers</span></a>
                            <a href="./groups.php" class="dropdown-item notify-item"><span>Groups</span></a>
                            <a href="./packages.php" class="dropdown-item notify-item"><span>Packages</span></a>
                            <a href="./profiles.php" class="dropdown-item notify-item"><span>Transcode Profiles</span></a>
                            <a href="./stream_categories.php" class="dropdown-item notify-item"><span>Stream Categories</span></a>
                            <a href="./movie_categories.php" class="dropdown-item notify-item"><span>Movie Categories</span></a>
                            <a href="#" class="dropdown-item notify-item"><span><span>Series Categories <i class="la la-exclamation-triangle"></i></span></a>
                            <a href="./mass_delete.php" class="dropdown-item notify-item"><span>Mass Delete</span></a>
                        </div>
                    </li>
                    <?php } ?>
                    <li class="notification-list">
                        <a href="./logout.php" class="nav-link right-bar-toggle waves-effect text-white">
                            <i class="fe-power noti-icon"></i>
                        </a>
                    </li>
                </ul>

                <!-- LOGO -->
                <div class="logo-box">
                    <a href="<?php if ($rPermissions["is_admin"]) { ?>dashboard.php<?php } else { ?>reseller.php<?php } ?>" class="logo text-center">
                        <span class="logo-lg">
                            <img src="assets/images/logo.png" alt="" height="26">
                        </span>
                        <span class="logo-sm">
                            <img src="assets/images/logo-sm.png" alt="" height="26">
                        </span>
                    </a>
                </div>

                <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
                    <li>
                        <button class="button-menu-mobile waves-effect text-white">
                            <span></span>
                            <span></span>
                            <span></span>
                        </button>
                    </li>
                </ul>
            </div>
            <!-- end Topbar -->
            <!-- ========== Left Sidebar Start ========== -->
            <div class="left-side-menu">
                <div class="slimscroll-menu">
                    <!--- Sidemenu -->
                    <div id="sidebar-menu">
                        <ul class="metismenu" id="side-menu">
                            <li>
                                <a href="./<?php if ($rPermissions["is_admin"]) { ?>dashboard.php<?php } else { ?>reseller.php<?php } ?>"><i class="la la-dashboard"></i><span>Dashboard</span></a>
                            </li>
                            <?php if (($rPermissions["is_reseller"]) && ($rPermissions["reseller_client_connection_logs"])) { ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-exchange"></i><span>Connections</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./live_connections.php">Live Connections</a></li>
                                    <li><a href="./user_activity.php">Activity Logs</a></li>
                                </ul>
                            </li>
                            <?php }
                            if ($rPermissions["is_admin"]) { ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-server"></i><span>Servers</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./server.php">Add Server</a></li>
                                    <li><a href="./install_server.php">Install Load Balancer</a></li>
                                    <li><a href="./servers.php">Manage Servers</a></li>
                                    <li class="separator"></li>
                                    <li><a href="./live_connections.php">Live Connections</a></li>
                                    <li><a href="./user_activity.php">Activity Logs</a></li>
                                </ul>
                            </li>
                            <?php } ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-user"></i><span>Users</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <?php if (($rPermissions["is_reseller"]) && ($rPermissions["total_allowed_gen_trials"] > 0)) { ?>
                                    <li><a href="./user_reseller.php?trial">Generate Trial</a></li>
                                    <?php } ?>
                                    <li><a href="./user<?php if ($rPermissions["is_reseller"]) { echo "_reseller"; } ?>.php">Add User</a></li>
                                    <li><a href="./users.php">Manage Users</a></li>
                                    <?php if ($rPermissions["is_admin"]) { ?>
                                    <li><a href="./user_mass.php">Mass Edit Users</a></li>
                                    <li class="separator"></li>
                                    <li><a href="./reg_user.php">Add Registered User</a></li>
                                    <li><a href="./reg_users.php">Manage Registered Users</a></li>
                                    <li class="separator"></li>
                                    <li><a href="./credit_logs.php">Credit Logs</a></li>
                                    <li><a href="./client_logs.php">Client Logs</a></li>
                                    <li><a href="./reg_user_logs.php">Reseller Logs</a></li>
                                    <?php } ?>
                                </ul>
                            </li>
                            <?php if (($rPermissions["is_reseller"]) && ($rPermissions["create_sub_resellers"])) { ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-users"></i><span>Subresellers</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <?php if ($rPermissions["is_admin"]) { ?>
                                    <li><a href="./reg_user.php">Add Subreseller</a></li>
                                    <?php } else { ?>
                                    <li><a href="./subreseller.php">Add Subreseller</a></li>
                                    <?php } ?>
                                    <li><a href="./reg_users.php">Manage Subreseller</a></li>
                                </ul>
                            </li>
                            <?php } ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-tablet"></i><span>Devices</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <?php if ($rPermissions["is_admin"]) { ?>
                                    <li><a href="./user.php?mag">Add MAG User</a></li>
                                    <li><a href="./mag.php">Link MAG User</a></li>
                                    <li><a href="./mags.php">Manage MAG Devices</a></li>
                                    <li><a href="./mag_events.php">MAG Events</a></li>
                                    <li class="separator"></li>
                                    <li><a href="./user.php?e2">Add Enigma User</a></li>
                                    <li><a href="./enigma.php">Link Enigma User</a></li>
                                    <li class="separator"></li>
                                    <li><a href="./mag_events.php">MAG Event Logs</a></li>
                                    <?php } else { ?>
                                    <li><a href="./mags.php">Manage MAG Devices</a></li>
                                    <li><a href="./enigmas.php">Manage Enigma Devices</a></li>
                                    <?php } ?>
                                </ul>
                            </li>
                            <?php if ($rPermissions["is_admin"]) { ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-video-camera"></i><span>VOD</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./movie.php">Add Movie</a></li>
                                    <li><a href="./movies.php">Manage Movies</a></li>
                                    <li><a href="./movie_mass.php">Mass Edit Movies</a></li>
                                    <li class="separator"></li>
                                    <li><a href="#">Add TV Series <i class="la la-exclamation-triangle"></i></a></li>
                                    <li><a href="#">Manage TV Series <i class="la la-exclamation-triangle"></i></a></li>
                                    <li class="separator"></li>
                                    <li><a href="#">Add TV Episode <i class="la la-exclamation-triangle"></i></a></li>
                                    <li><a href="#">Manage TV Episodes <i class="la la-exclamation-triangle"></i></a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-play-circle-o"></i><span>Streams</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./stream.php">Add Stream</a></li>
                                    <li><a href="./stream.php?import">Import Streams</a></li>
                                    <li><a href="./streams.php">Manage Streams</a></li>
                                    <li><a href="./stream_mass.php">Mass Edit Streams</a></li>
                                    <li class="separator"></li>
                                    <li><a href="./stream_logs.php">Stream Logs</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="javascript: void(0);"><i class="mdi mdi-flower-tulip-outline"></i><span>Bouquets</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./bouquet.php">Add Bouquet</a></li>
                                    <li><a href="./bouquets.php">Manage Bouquets</a></li>
                                </ul>
                            </li>
                            <?php }
                            if (($rPermissions["is_reseller"]) && ($rPermissions["reset_stb_data"])) { ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-play-circle-o"></i><span>Content</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./streams.php">Streams</a></li>
                                    <li><a href="./movies.php">Movies</a></li>
                                    <li><a href="#">Series <i class="la la-exclamation-triangle"></i></a></li>
                                </ul>
                            </li>
                            <?php }
                            if ($rPermissions["is_reseller"]) { ?>
                            <li>
                                <a href="javascript: void(0);"><i class="la la-envelope"></i><span>Support</span><span class="menu-arrow"></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="./ticket.php">Create Ticket</a></li>
                                    <li><a href="./tickets.php">Manage Tickets</a></li>
                                </ul>
                            </li>
                            <?php }
                            if ($rPermissions["is_admin"]) { ?>
                            <li>
                                <a href="./tickets.php"><i class="la la-envelope"></i><span>Tickets</span></a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <!-- End Sidebar -->
                    <div class="clearfix"></div>
                </div>
                <!-- Sidebar -left -->
            </div>