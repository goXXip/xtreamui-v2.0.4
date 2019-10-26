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
        <div class="content-page"><div class="content"><div class="container-fluid">
        <?php } else { ?>
        <div class="wrapper"><div class="container-fluid">
        <?php } ?>
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li>
                                        <a href="javascript:location.reload();">
                                            <button type="button" class="btn btn-dark waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-refresh"></i> Refresh
                                            </button>
                                        </a>
                                        <a href="server.php">
                                            <button type="button" class="btn btn-success waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-plus"></i> Add Server
                                            </button>
                                        </a>
                                        <a href="install_server.php">
                                            <button type="button" class="btn btn-info waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-creation"></i> Install LB
                                            </button>
                                        </a>
                                    </li>
                                </ol>
                            </div>
                            <h4 class="page-title">Servers</h4>
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
                                            <th>Server Name</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Latency</th>
                                            <th>Domain Name</th>
                                            <th>Server IP</th>
                                            <th class="text-center">Client Slots</th>
                                            <th class="text-center">CPU %</th>
                                            <th class="text-center">MEM %</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rServers as $rServer) {
                                        if (((time() - $rServer["last_check_ago"]) > 360) AND ($rServer["can_delete"] == 1) AND ($rServer["status"] <> 3)) { $rServer["status"] = 2; } // Server Timeout
                                        if (in_array($rServer["status"], Array(0,1))) {
                                            $rServerText = Array(0 => "Disabled", 1 => "Online")[$rServer["status"]];
                                        } else if ($rServer["status"] == 2) {
                                            if ($rServer["last_check_ago"] > 0) {
                                                $rServerText = "Offline for ".intval((time() - $rServer["last_check_ago"])/60)." minutes";
                                            } else {
                                                $rServerText = "Offline";
                                            }
                                        } else if ($rServer["status"] == 3) {
                                            $rServerText = "Installing...";
                                        }
                                        $rWatchDog = json_decode($rServer["watchdog_data"], True);
                                        if (!is_array($rWatchDog)) {
                                            $rWatchDog = Array("total_mem_used_percent" => "N/A ", "cpu_avg" => "N/A ");
                                        }
										$rLatency = $rServer["latency"] * 1000;
										if ($rLatency > 0) {
											$rLatency = $rLatency." ms";
										} else {
											$rLatency = "--";
										}
                                        ?>
                                        <tr id="server-<?=$rServer["id"]?>">
                                            <td class="text-center"><?=$rServer["id"]?></td>
                                            <td><?=$rServer["server_name"]?></td>
                                            <td class="text-center" data-toggle="tooltip" data-placement="top" title="" data-original-title="<?=$rServerText?>" ><i class="<?php if ($rServer["status"] == 1) { echo "btn-outline-success"; } else if ($rServer["status"] == "3") { echo "btn-outline-info"; } else { echo "btn-outline-danger"; } ?> mdi mdi-<?=Array(0 => "alarm-light-outline", 1 => "check-network", 2 => "alarm-light-outline", 3 => "creation")[$rServer["status"]]?>"></i></td>
                                            <td class="text-center"><?=$rLatency?></td>
                                            <td><?=$rServer["domain_name"]?></td>
                                            <td><?=$rServer["server_ip"]?></td>
                                            <td class="text-center"><a href="./live_connections.php?server_id=<?=$rServer["id"]?>"><?=count(getConnections($rServer["id"]))?> / <?=$rServer["total_clients"]?></a></td>
                                            <td class="text-center"><?=intval($rWatchDog["cpu_avg"])?>%</td>
                                            <td class="text-center"><?=intval($rWatchDog["total_mem_used_percent"])?>%</td>
                                            <td class="text-center">
                                                <a href="./server.php?id=<?=$rServer["id"]?>"><button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit Server" class="btn btn-outline-info waves-effect waves-light btn-xs"><i class="mdi mdi-pencil-outline"></i></button></a>
                                                <button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Kill All Connections" class="btn btn-outline-warning waves-effect waves-light btn-xs" onClick="api(<?=$rServer["id"]?>, 'kill');""><i class="fas fa-hammer"></i></button>
                                                <?php if ($rServer["can_delete"] == 1) { ?>
                                                <button type="button" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete Server" class="btn btn-outline-danger waves-effect waves-light btn-xs" onClick="api(<?=$rServer["id"]?>, 'delete');""><i class="mdi mdi-close"></i></button>
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
        <script src="assets/libs/pdfmake/pdfmake.min.js"></script>
        <script src="assets/libs/pdfmake/vfs_fonts.js"></script>
        <!-- third party js ends -->

        <script>
        function api(rID, rType) {
            if (rType == "delete") {
                if (confirm('Are you sure you want to delete this server and it\'s accompanying streams? This cannot be undone!') == false) {
                    return;
                }
            } else if (rType == "kill") {
                if (confirm('Are you sure you want to kill all connections to this server?') == false) {
                    return;
                }
            }
            $.getJSON("./api.php?action=server&sub=" + rType + "&server_id=" + rID, function(data) {
                if (data.result === true) {
                    if (rType == "delete") {
                        $("#server-" + rID).remove();
                        $.toast("Server successfully deleted.");
                    } else if (rType == "kill") {
                        $.toast("All server connections have been killed.");
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
            });
        });
        </script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
    </body>
</html>