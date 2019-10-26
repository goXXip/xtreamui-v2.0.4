<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }
if (!$rPermissions["is_admin"]) { exit; }

if (isset($_POST["submit_server"])) {
    $rArray = Array("server_name" => "", "domain_name" => "", "server_ip" => "", "vpn_ip" => "", "diff_time_main" => 0, "http_broadcast_port" => 25461, "total_clients" => 1000, "system_os" => "", "network_interface" => "eth0", "status" => 3, "enable_geoip" => 0, "can_delete" => 1, "rtmp_port" => 25462, "enable_isp" => 0, "boost_fpm" => 0, "network_guaranteed_speed" => 1000, "https_broadcast_port" => 25463, "whitelist_ips" => Array(), "timeshift_only" => 0);
    if ((strlen($_POST["server_name"]) == 0) OR (strlen($_POST["server_ip"]) == 0) OR (strlen($_POST["ssh_port"]) == 0) OR (strlen($_POST["root_password"]) == 0)) {
        $_STATUS = 1;
    }
    if (!isset($_STATUS)) {
        $rArray["server_ip"] = $_POST["server_ip"];
        $rArray["server_name"] = $_POST["server_name"];
        $rCols = implode(',', array_keys($rArray));
        foreach (array_values($rArray) as $rValue) {
            isset($rValues) ? $rValues .= ',' : $rValues = '';
            if (is_array($rValue)) {
                $rValue = json_encode($rValue);
            }
            if (is_null($rValue)) {
                $rValues .= 'NULL';
            } else {
                $rValues .= '\''.$db->real_escape_string($rValue).'\'';
            }
        }
        $rQuery = "INSERT INTO `streaming_servers`(".$rCols.") VALUES(".$rValues.");";
        if ($db->query($rQuery)) {
            $rServerID = intval($db->insert_id);
            $rJSON = Array("status" => 0, "port" => intval($_POST["ssh_port"]), "host" => $_POST["server_ip"], "password" => $_POST["root_password"], "time" => intval(time()), "id" => $rServerID);
            file_put_contents("/home/xtreamcodes/iptv_xtream_codes/adtools/balancer/".$rServerID.".json", json_encode($rJSON));
            header("Location: ./servers.php");
        } else {
            $_STATUS = 2;
        }
    }
}

if ($rSettings["sidebar"]) {
    include "header_sidebar.php";
} else {
    include "header.php";
}
        if ($rSettings["sidebar"]) { ?>
        <div class="content-page"><div class="content boxed-layout"><div class="container-fluid">
        <?php } else { ?>
        <div class="wrapper boxed-layout"><div class="container-fluid">
        <?php } ?>
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <a href="./servers.php"><li class="breadcrumb-item"><i class="mdi mdi-backspace"></i> Back to Servers</li></a>
                                </ol>
                            </div>
                            <h4 class="page-title">Load Balancer Installation</h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 
                <div class="row">
                    <div class="col-xl-12">
                        <?php if ((isset($_STATUS)) && ($_STATUS > 0)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            There was an error performing this operation! Please check the form entry and try again.
                        </div>
                        <?php } ?>
                        <div class="card">
                            <div class="card-body">
                                <form action="./install_server.php" method="POST" id="server_form">
                                    <div id="basicwizard">
                                        <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-4">
                                            <li class="nav-item">
                                                <a href="#server-details" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="mdi mdi-creation mr-1"></i>
                                                    <span class="d-none d-sm-inline">Details</span>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content b-0 mb-0 pt-0">
                                            <div class="tab-pane" id="server-details">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="server_name">Server Name</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="server_name" name="server_name" value="">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="server_ip">Server IP</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="server_ip" name="server_ip" value="">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="root_password">Root Password</label>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" id="root_password" name="root_password" value="">
                                                            </div>
                                                            <label class="col-md-2 col-form-label" for="ssh_port">SSH Port</label>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control" id="ssh_port" name="ssh_port" value="22">
                                                            </div>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0">
                                                    <li class="next list-inline-item float-right">
                                                        <input name="submit_server" type="submit" class="btn btn-primary" value="Install Server" />
                                                    </li>
                                                </ul>
                                            </div>
                                        </div> <!-- tab-content -->
                                    </div> <!-- end #basicwizard-->
                                </form>

                            </div> <!-- end card-body -->
                        </div> <!-- end card-->
                    </div> <!-- end col -->
                </div>
            </div> <!-- end container -->
        </div>
        <!-- end wrapper -->
        <?php if ($rSettings["sidebar"]) { echo "</div>"; } ?>
        <!-- Footer Start -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 copyright text-center"><?=getFooter()?></div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/libs/jquery-toast/jquery.toast.min.js"></script>
        <script src="assets/libs/jquery-nice-select/jquery.nice-select.min.js"></script>
        <script src="assets/libs/switchery/switchery.min.js"></script>
        <script src="assets/libs/select2/select2.min.js"></script>
        <script src="assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
        <script src="assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>
        <script src="assets/libs/clockpicker/bootstrap-clockpicker.min.js"></script>
        <script src="assets/libs/moment/moment.min.js"></script>
        <script src="assets/libs/daterangepicker/daterangepicker.js"></script>

        <!-- Plugins js-->
        <script src="assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>

        <!-- Tree view js -->
        <script src="assets/libs/treeview/jstree.min.js"></script>
        <script src="assets/js/pages/treeview.init.js"></script>
        <script src="assets/js/pages/form-wizard.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
        <script>
        (function($) {
          $.fn.inputFilter = function(inputFilter) {
            return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
              if (inputFilter(this.value)) {
                this.oldValue = this.value;
                this.oldSelectionStart = this.selectionStart;
                this.oldSelectionEnd = this.selectionEnd;
              } else if (this.hasOwnProperty("oldValue")) {
                this.value = this.oldValue;
                this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
              }
            });
          };
        }(jQuery));
        
        $(document).ready(function() {
            $(document).keypress(function(event){
                if (event.which == '13') {
                    event.preventDefault();
                }
            });
            $("#ssh_port").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("form").attr('autocomplete', 'off');
        });
        </script>
    </body>
</html>