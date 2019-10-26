<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }
if (!$rPermissions["is_admin"]) { exit; }

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
                                    <li>
                                        <a href="group.php" style="margin-right:10px;">
                                            <button type="button" class="btn btn-success waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-plus"></i> Add Group
                                            </button>
                                        </a>
                                    </li>
                                </ol>
                            </div>
                            <h4 class="page-title">Groups</h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="overflow-x:auto;">
                                <table id="datatable" class="table dt-responsive nowrap">
                                    <thead>
                                        <tr>
                                            <th class="text-center">ID</th>
                                            <th>Group Name</th>
                                            <th class="text-center">Admin UI</th>
                                            <th class="text-center">Reseller UI</th>
                                            <th class="text-center">Ban Access</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (getMemberGroups() as $rGroup) { ?>
                                        <tr id="group-<?=$rGroup["group_id"]?>">
                                            <td class="text-center"><?=$rGroup["group_id"]?></td>
                                            <td><?=$rGroup["group_name"]?></td>
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox mt-1">
                                                    <input data-id="<?=$rGroup["group_id"]?>" data-name="is_admin" type="checkbox" class="custom-control-input" id="is_admin_<?=$rGroup["group_id"]?>" name="is_admin"<?php if ($rGroup["is_admin"]) { echo " checked"; } ?>>
                                                    <label class="custom-control-label" for="is_admin_<?=$rGroup["group_id"]?>"></label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox mt-1">
                                                    <input data-id="<?=$rGroup["group_id"]?>" data-name="is_reseller" type="checkbox" class="custom-control-input" id="is_reseller_<?=$rGroup["group_id"]?>" name="is_reseller"<?php if ($rGroup["is_reseller"]) { echo " checked"; } ?>>
                                                    <label class="custom-control-label" for="is_reseller_<?=$rGroup["group_id"]?>"></label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="custom-control custom-checkbox mt-1">
                                                    <input data-id="<?=$rGroup["group_id"]?>" data-name="is_banned" type="checkbox" class="custom-control-input" id="is_banned_<?=$rGroup["group_id"]?>" name="is_banned"<?php if ($rGroup["is_banned"]) { echo " checked"; } ?>>
                                                    <label class="custom-control-label" for="is_banned_<?=$rGroup["group_id"]?>"></label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="./group.php?id=<?=$rGroup["group_id"]?>"><button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit Group" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                                                <?php if ($rGroup["can_delete"]) { ?>
                                                <button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete Group" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api(<?=$rGroup["group_id"]?>, 'delete');""><i class="mdi mdi-close"></i></button>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div> <!-- end card body-->
                        </div> <!-- end card -->
                    </div><!-- end col-->
                </div>
                <!-- end row-->
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
        
        <!-- third party js -->
        <script src="assets/libs/datatables/jquery.dataTables.min.js"></script>
        <script src="assets/libs/datatables/dataTables.bootstrap4.js"></script>
        <script src="assets/libs/datatables/dataTables.responsive.min.js"></script>
        <script src="assets/libs/datatables/responsive.bootstrap4.min.js"></script>
        <script src="assets/libs/datatables/dataTables.buttons.min.js"></script>
        <script src="assets/libs/datatables/buttons.bootstrap4.min.js"></script>
        <script src="assets/libs/datatables/buttons.html5.min.js"></script>
        <script src="assets/libs/datatables/buttons.flash.min.js"></script>
        <script src="assets/libs/datatables/buttons.print.min.js"></script>
        <script src="assets/libs/datatables/dataTables.keyTable.min.js"></script>
        <script src="assets/libs/datatables/dataTables.select.min.js"></script>
        
        <!-- third party js ends -->

        <script>
        function api(rID, rType) {
            if (rType == "delete") {
                if (confirm('Are you sure you want to delete this group? This cannot be undone!') == false) {
                    return;
                }
            }
            $.getJSON("./api.php?action=group&sub=" + rType + "&group_id=" + rID, function(data) {
                if (data.result === true) {
                    if (rType == "delete") {
                        $("#package-" + rID).remove();
                        $.toast("Package successfully deleted.");
                    }
                    $.each($('.tooltip'), function (index, element) {
                        $(this).remove();
                    });
                    $('[data-toggle="tooltip"]').tooltip();
                } else {
                    $.toast("An error occured while processing your request.");
                }
            });
        }
        $('input:checkbox').change(function() {
            $.getJSON("./api.php?action=group&sub=" + $(this).data("name") + "&group_id=" + $(this).data("id") + "&value=" + ($(this).is(":checked") ? 1 : 0), function(data) {
                $.toast("Package has been modified.");
            });
        });
        $(document).ready(function() {
            $("#datatable").DataTable({
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                },
                responsive: false,
                paging: false,
                bInfo: false
            });
        });
        </script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
    </body>
</html>