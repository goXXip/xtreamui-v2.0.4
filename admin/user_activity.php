<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }
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
                                        <?php if (!$detect->isMobile()) { ?>
                                        <a href="#" onClick="toggleAuto();" style="margin-right:10px;">
                                            <button type="button" class="btn btn-dark waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-refresh"></i> <span class="auto-text">Auto-Refresh</span>
                                            </button>
                                        </a>
                                        <?php } else { ?>
                                        <a href="javascript:location.reload();" onClick="toggleAuto();" style="margin-right:10px;">
                                            <button type="button" class="btn btn-dark waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-refresh"></i> Refresh
                                            </button>
                                        </a>
                                        <?php } ?>
                                    </li>
                                </ol>
                            </div>
                            <h4 class="page-title">Activity Logs</h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="overflow-x:auto;">
                                <div class="form-group row mb-4">
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="log_search" value="" placeholder="Search Logs...">
                                    </div>
                                    <label class="col-md-1 col-form-label text-center" for="server">Filter</label>
                                    <div class="col-md-3">
                                        <select id="server" class="form-control" data-toggle="select2">
                                            <option value="" selected>All Servers</option>
                                            <?php foreach (getStreamingServers() as $rServer) { ?>
                                            <option value="<?=$rServer["id"]?>"><?=$rServer["server_name"]?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <label class="col-md-1 col-form-label text-center" for="range">Dates</label>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control text-center date" id="range" name="range" data-toggle="date-picker" data-single-date-picker="true" autocomplete="off">
                                    </div>
                                    <label class="col-md-1 col-form-label text-center" for="show_entries">Show</label>
                                    <div class="col-md-1">
                                        <select id="show_entries" class="form-control" data-toggle="select2">
                                            <?php foreach (Array(10, 25, 50, 250, 500, 1000) as $rShow) { ?>
                                            <option<?php if ($rAdminSettings["default_entries"] == $rShow) { echo " selected"; } ?> value="<?=$rShow?>"><?=$rShow?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <table id="datatable-activity" class="table dt-responsive nowrap">
                                    <thead>
                                        <tr>
                                            <th class="text-center">ID</th>
                                            <th>Username</th>
                                            <th>Stream</th>
                                            <th>Server</th>
                                            <th class="text-center">Start</th>
                                            <th class="text-center">Stop</th>
                                            <th class="text-center">IP</th>
                                            <th class="text-center">Country</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
        <script src="assets/libs/select2/select2.min.js"></script>
        <script src="assets/libs/datatables/dataTables.responsive.min.js"></script>
        <script src="assets/libs/datatables/responsive.bootstrap4.min.js"></script>
        <script src="assets/libs/datatables/dataTables.buttons.min.js"></script>
        <script src="assets/libs/datatables/buttons.bootstrap4.min.js"></script>
        <script src="assets/libs/datatables/buttons.html5.min.js"></script>
        <script src="assets/libs/datatables/buttons.flash.min.js"></script>
        <script src="assets/libs/datatables/buttons.print.min.js"></script>
        <script src="assets/libs/datatables/dataTables.keyTable.min.js"></script>
        <script src="assets/libs/datatables/dataTables.select.min.js"></script>
        <script src="assets/libs/moment/moment.min.js"></script>
        <script src="assets/libs/daterangepicker/daterangepicker.js"></script>
        <!-- third party js ends -->

        <!-- Datatables init -->
        <script>
        var autoRefresh = true;
        
        function toggleAuto() {
            if (autoRefresh == true) {
                autoRefresh = false;
                $(".auto-text").html("Manual Mode");
            } else {
                autoRefresh = true;
                $(".auto-text").html("Auto-Refresh");
            }
        }
        
        function reloadUsers() {
            if (autoRefresh == true) {
                $("#datatable-activity").DataTable().ajax.reload(null, false);
            }
            setTimeout(reloadUsers, 2000);
        }
        
        function getServer() {
            return $("#server").val();
        }
        function getRange() {
            return $("#range").val();
        }

        $(document).ready(function() {
            $('select').select2({width: '100%'});
            $('#range').daterangepicker({
                singleDatePicker: false,
                showDropdowns: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                autoUpdateInput: false
            }).val("");
            $('#range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $("#datatable-activity").DataTable().ajax.reload( null, false );
            });
            $('#range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $("#datatable-activity").DataTable().ajax.reload( null, false );
            });
            $('#range').on('change', function() {
                $("#datatable-activity").DataTable().ajax.reload( null, false );
            });
            $("#datatable-activity").DataTable({
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    },
                    infoFiltered: ""
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                    $('[data-toggle="tooltip"]').tooltip();
                },
                responsive: false,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "./table_search.php",
                    "data": function(d) {
                        d.id = "user_activity",
                        d.range = getRange(),
                        d.server = getServer()
                    }
                },
                columnDefs: [
                    {"className": "dt-center", "targets": [0,4,5,6,7]}
                ],
                "order": [[ 0, "desc" ]],
                pageLength: <?=$rAdminSettings["default_entries"] ?: 10?>
            });
            $('#log_search').keyup(function(){
                $('#datatable-activity').DataTable().search($(this).val()).draw();
            })
            $('#show_entries').change(function(){
                $('#datatable-activity').DataTable().page.len($(this).val()).draw();
            })
            $('#server').change(function(){
                $("#datatable-activity").DataTable().ajax.reload( null, false );
            })
            <?php if (!$detect->isMobile()) { ?>
            setTimeout(reloadUsers, 5000);
            <?php } ?>
        });
        </script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
    </body>
</html>