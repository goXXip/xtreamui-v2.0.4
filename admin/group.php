<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }
if (!$rPermissions["is_admin"]) { exit; }

if (isset($_POST["submit_group"])) {
    if (isset($_POST["edit"])) {
        $rArray = getMemberGroup($_POST["edit"]);
        unset($rArray["group_id"]);
    } else {
        $rArray = Array("group_name" => "", "group_color" => "", "is_banned" => 0, "is_admin" => 0, "is_reseller" => 0, "total_allowed_gen_in" => "day", "total_allowed_gen_trials" => 0, "can_delete" => 1, "delete_users" => 0, "allowed_pages" => "", "reseller_force_server" => "", "create_sub_resellers_price" => 0, "create_sub_resellers" => 0, "alter_packages_ids" => 0, "alter_packages_prices" => 0, "reseller_client_connection_logs" => 0, "reseller_assign_pass" => 0, "allow_change_pass" => 0, "allow_import" => 0, "allow_export" => 0, "reseller_trial_credit_allow" => 0, "edit_mac" => 0, "edit_isplock" => 0, "reset_stb_data" => 0, "reseller_bonus_package_inc" => 0, "allow_download" => 1);
    }
    if (strlen($_POST["group_name"]) == 0) {
        $_STATUS = 1;
    }
    foreach (Array("is_admin", "is_reseller", "is_banned", "delete_users", "create_sub_resellers", "allow_change_pass", "allow_download", "reseller_client_connection_logs", "reset_stb_data") as $rSelection) {
        if (isset($_POST[$rSelection])) {
            $rArray[$rSelection] = 1;
            unset($_POST[$rSelection]);
        } else {
            $rArray[$rSelection] = 0;
        }
    }
    if (!isset($_STATUS)) {
        foreach($_POST as $rKey => $rValue) {
            if (isset($rArray[$rKey])) {
                $rArray[$rKey] = $rValue;
            }
        }
        $rCols = "`".implode('`,`', array_keys($rArray))."`";
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
        if (isset($_POST["edit"])) {
            $rCols = "`group_id`,".$rCols;
            $rValues = $_POST["edit"].",".$rValues;
        }
        $rQuery = "REPLACE INTO `member_groups`(".$rCols.") VALUES(".$rValues.");";
        if ($db->query($rQuery)) {
            if (isset($_POST["edit"])) {
                $rInsertID = intval($_POST["edit"]);
            } else {
                $rInsertID = $db->insert_id;
            }
            $_STATUS = 0;
        } else {
            $_STATUS = 2;
        }
        if (!isset($_GET["id"])) {
            $_GET["id"] = $rInsertID;
        }
    }
}

if (isset($_GET["id"])) {
    $rGroup = getMemberGroup($_GET["id"]);
    if (!$rGroup) {
        exit;
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
                                    <a href="./groups.php"><li class="breadcrumb-item"><i class="mdi mdi-backspace"></i> Back to Groups</li></a>
                                </ol>
                            </div>
                            <h4 class="page-title"><?php if (isset($rGroup)) { echo "Edit"; } else { echo "Add"; } ?> Group</h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 
                <div class="row">
                    <div class="col-xl-12">
                        <?php if ((isset($_STATUS)) && ($_STATUS == 0)) { ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            Group operation was completed successfully.
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS > 0)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            There was an error performing this operation! Please check the form entry and try again.
                        </div>
                        <?php } ?>
                        <div class="card">
                            <div class="card-body">
                                <form action="./group.php<?php if (isset($_GET["id"])) { echo "?id=".$_GET["id"]; } ?>" method="POST" id="user_form">
                                    <?php if (isset($rGroup)) { ?>
                                    <input type="hidden" name="edit" value="<?=$rGroup["group_id"]?>" />
                                    <?php } ?>
                                    <div id="basicwizard">
                                        <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-4">
                                            <li class="nav-item">
                                                <a href="#group-details" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="mdi mdi-account-card-details-outline mr-1"></i>
                                                    <span class="d-none d-sm-inline">Details</span>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content b-0 mb-0 pt-0">
                                            <div class="tab-pane" id="group-details">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="group_name">Group Name</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="group_name" name="group_name" value="<?php if (isset($rGroup)) { echo $rGroup["group_name"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="is_admin">Is Admin</label>
                                                            <div class="col-md-2">
                                                                <input name="is_admin" id="is_admin" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["is_admin"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                            <label class="col-md-4 col-form-label" for="is_reseller">Is Reseller</label>
                                                            <div class="col-md-2">
                                                                <input name="is_reseller" id="is_reseller" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["is_reseller"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="is_banned">Is Banned</label>
                                                            <div class="col-md-2">
                                                                <input name="is_banned" id="is_banned" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["is_banned"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                            <label class="col-md-4 col-form-label" for="delete_users">Can Delete Users</label>
                                                            <div class="col-md-2">
                                                                <input name="delete_users" id="delete_users" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["delete_users"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="create_sub_resellers">Can Create Subresellers</label>
                                                            <div class="col-md-2">
                                                                <input name="create_sub_resellers" id="create_sub_resellers" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["create_sub_resellers"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                            <label class="col-md-4 col-form-label" for="create_sub_resellers_price">Subreseller Price</label>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control" id="create_sub_resellers_price" name="create_sub_resellers_price" value="<?php if (isset($rGroup)) { echo $rGroup["create_sub_resellers_price"]; } else { echo "0"; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="allow_change_pass">Can Change Password</label>
                                                            <div class="col-md-2">
                                                                <input name="allow_change_pass" id="allow_change_pass" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["allow_change_pass"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                            <label class="col-md-4 col-form-label" for="allow_download">Can Download Playlist</label>
                                                            <div class="col-md-2">
                                                                <input name="allow_download" id="allow_download" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["allow_download"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="reset_stb_data">Can View VOD & Streams</label>
                                                            <div class="col-md-2">
                                                                <input name="reset_stb_data" id="reset_stb_data" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["reset_stb_data"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                            <label class="col-md-4 col-form-label" for="reseller_client_connection_logs">Can View Live Connections</label>
                                                            <div class="col-md-2">
                                                                <input name="reseller_client_connection_logs" id="reseller_client_connection_logs" type="checkbox" <?php if (isset($rGroup)) { if ($rGroup["reseller_client_connection_logs"] == 1) { echo "checked "; } } ?>data-plugin="switchery" class="js-switch" data-color="#039cfd"/>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="total_allowed_gen_trials">Allowed Trials</label>
                                                            <div class="col-md-2">
                                                                <input type="text" class="form-control" id="total_allowed_gen_trials" name="total_allowed_gen_trials" value="<?php if (isset($rGroup)) { echo $rGroup["total_allowed_gen_trials"]; } else { echo "0"; } ?>">
                                                            </div>
                                                            <label class="col-md-4 col-form-label" for="total_allowed_gen_in">Allowed Trials In</label>
                                                            <div class="col-md-2">
                                                                <select name="total_allowed_gen_in" id="total_allowed_gen_in" class="form-control select2" data-toggle="select2">
                                                                    <?php foreach (Array("Day", "Month") as $rOption) { ?>
                                                                    <option <?php if (isset($rGroup)) { if ($rGroup["total_allowed_gen_in"] == strtolower($rOption)) { echo "selected "; } } ?>value="<?=strtolower($rOption)?>"><?=$rOption?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0">
                                                    <li class="next list-inline-item float-right">
                                                        <input name="submit_group" type="submit" class="btn btn-primary" value="<?php if (isset($rGroup)) { echo "Edit"; } else { echo "Add"; } ?>" />
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
        
        function selectAll() {
            $(".group-checkbox").each(function() {
                $(this).prop('checked', true);
            });
        }
        
        function selectNone() {
            $(".group-checkbox").each(function() {
                $(this).prop('checked', false);
            });
        }
        
        function selectAllBq() {
            $(".bouquet-checkbox").each(function() {
                $(this).prop('checked', true);
            });
        }
        
        function selectNoneBq() {
            $(".bouquet-checkbox").each(function() {
                $(this).prop('checked', false);
            });
        }
        
        $(document).ready(function() {
            $('select.select2').select2({width: '100%'})
            $(".js-switch").each(function (index, element) {
                var init = new Switchery(element);
            });
            
            $(document).keypress(function(event){
                if (event.which == '13') {
                    event.preventDefault();
                }
            });

            $("#max_connections").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("#trial_credits").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("#trial_duration").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("#official_credits").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("#official_duration").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("form").attr('autocomplete', 'off');
        });
        </script>
    </body>
</html>