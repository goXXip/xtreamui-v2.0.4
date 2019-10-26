<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }

$rCategories = getCategories("movie");

if (isset($_GET["category"])) {
    if (!isset($rCategories[$_GET["category"]])) {
        exit;
    } else {
        $rCategory = $rCategories[$_GET["category"]];
    }
} else {
    $rCategory = null;
}

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
                                        <a href="#" onClick="changeZoom();">
                                            <button type="button" class="btn btn-info waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-magnify"></i>
                                            </button>
                                        </a>
                                        <?php if (!$detect->isMobile()) { ?>
                                        <a href="#" onClick="toggleAuto();">
                                            <button type="button" class="btn btn-dark waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-refresh"></i> <span class="auto-text">Auto-Refresh</span>
                                            </button>
                                        </a>
                                        <?php } else { ?>
                                        <a href="javascript:location.reload();" onClick="toggleAuto();">
                                            <button type="button" class="btn btn-dark waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-refresh"></i> Refresh
                                            </button>
                                        </a>
                                        <?php }
                                        if ($rPermissions["is_admin"]) { ?>
                                        <a href="movie.php<?php if($rCategory) { echo "?category=".$rCategory["id"]; }?>">
                                            <button type="button" class="btn btn-success waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-plus"></i> Add Movie
                                            </button>
                                        </a>
                                        <?php } ?>
                                    </li>
                                </ol>
                            </div>
                            <h4 class="page-title">Movies<?php if ($rCategory) { echo " - ".$rCategory["category_name"]; } ?></h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="overflow-x:auto;">
                                <div class="form-group row mb-4">
                                    <?php if ($rPermissions["is_reseller"]) { ?>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="stream_search" value="" placeholder="Search Movies...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="category_id" class="form-control" data-toggle="select2">
                                            <option value="" selected>All Categories</option>
                                            <?php foreach ($rCategories as $rCategory) { ?>
                                            <option value="<?=$rCategory["id"]?>"><?=$rCategory["category_name"]?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filter" class="form-control" data-toggle="select2">
                                            <option value="" selected>No Filter</option>
                                            <option value="1">Encoded</option>
                                            <option value="2">Encoding</option>
                                            <option value="3">Down</option>
                                            <option value="4">Ready</option>
                                            <option value="5">Direct</option>
                                        </select>
                                    </div>
                                    <label class="col-md-1 col-form-label text-center" for="show_entries">Show</label>
                                    <div class="col-md-2">
                                        <select id="show_entries" class="form-control" data-toggle="select2">
                                            <?php foreach (Array(10, 25, 50, 250, 500, 1000) as $rShow) { ?>
                                            <option<?php if ($rAdminSettings["default_entries"] == $rShow) { echo " selected"; } ?> value="<?=$rShow?>"><?=$rShow?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <?php } else { ?>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="stream_search" value="" placeholder="Search Movies...">
                                    </div>
                                    <label class="col-md-2 col-form-label text-center" for="category_name">Category Name</label>
                                    <div class="col-md-3">
                                        <select id="category_id" class="form-control" data-toggle="select2">
                                            <option value="" selected>All Categories</option>
                                            <?php foreach ($rCategories as $rCategory) { ?>
                                            <option value="<?=$rCategory["id"]?>"><?=$rCategory["category_name"]?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select id="filter" class="form-control" data-toggle="select2">
                                            <option value="" selected>No Filter</option>
                                            <option value="1">Encoded</option>
                                            <option value="2">Encoding</option>
                                            <option value="3">Down</option>
                                            <option value="4">Ready</option>
                                            <option value="5">Direct</option>
                                        </select>
                                    </div>
                                    <label class="col-md-1 col-form-label text-center" for="show_entries">Show</label>
                                    <div class="col-md-1">
                                        <select id="show_entries" class="form-control" data-toggle="select2">
                                            <?php foreach (Array(10, 25, 50, 250, 500, 1000) as $rShow) { ?>
                                            <option<?php if ($rAdminSettings["default_entries"] == $rShow) { echo " selected"; } ?> value="<?=$rShow?>"><?=$rShow?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <?php } ?>
                                </div>
                                <table id="datatable-streampage" class="table dt-responsive nowrap font-normal">
                                    <thead>
                                        <tr style="<?php echo $background ?>">
                                            <th class="text-center">ID</th>
                                            <th>Name</th>
                                            <th>Server</th>
                                            <th class="text-center">Clients</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Actions</th>
                                            <th class="text-center">Player</th>
                                            <th class="text-center">Stream Info</th>
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
        
        function api(rID, rServerID, rType) {
            if (rType == "delete") {
                if (confirm('Are you sure you want to delete this movie?') == false) {
                    return;
                }
            }
            $.getJSON("./api.php?action=movie&sub=" + rType + "&stream_id=" + rID + "&server_id=" + rServerID, function(data) {
                if (data.result == true) {
                    if (rType == "start") {
                        $.toast("Movie encoding has started. It will take some time before this movie is available.");
                    } else if (rType == "stop") {
                        $.toast("Movie encoding has been stopped.");
                    } else if (rType == "delete") {
                        $.toast("Movie successfully deleted.");
                    }
                    $.each($('.tooltip'), function (index, element) {
                        $(this).remove();
                    });
                    $("#datatable-streampage").DataTable().ajax.reload( null, false );
                } else {
                    $.toast("An error occured while processing your request.");
                }
            }).fail(function() {
                $.toast("An error occured while processing your request.");
            });
        }
        function player(rID, rContainer) {
            $.magnificPopup.open({
                items: {
                    src: "./player.php?type=movie&id=" + rID + "&container=" + rContainer,
                    type: 'iframe'
                }
            });
        }
        function reloadStreams() {
            if (autoRefresh == true) {
                $("#datatable-streampage").DataTable().ajax.reload( null, false );
            }
            setTimeout(reloadStreams, 5000);
        }

        function getCategory() {
            return $("#category_id").val();
        }
        function getFilter() {
            return $("#filter").val();
        }
        function changeZoom() {
            if ($("#datatable-streampage").hasClass("font-large")) {
                $("#datatable-streampage").removeClass("font-large");
                $("#datatable-streampage").addClass("font-normal");
            } else if ($("#datatable-streampage").hasClass("font-normal")) {
                $("#datatable-streampage").removeClass("font-normal");
                $("#datatable-streampage").addClass("font-small");
            } else {
                $("#datatable-streampage").removeClass("font-small");
                $("#datatable-streampage").addClass("font-large");
            }
            $("#datatable-streampage").draw();
        }
        $(document).ready(function() {
            $('select').select2({width: '100%'});
            $("#datatable-streampage").DataTable({
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                    $('[data-toggle="tooltip"]').tooltip();
                },
                createdRow: function(row, data, index) {
                    $(row).addClass('stream-' + data[0]);
                },
                responsive: false,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "./table_search.php",
                    "data": function(d) {
                        d.id = "movies",
                        d.category = getCategory(),
                        d.filter = getFilter()
                    }
                },
                columnDefs: [
                    {"className": "dt-center", "targets": [0,3,4,5,6,7]},
                    {"orderable": false, "targets": [5,6]},
                    <?php if ($rPermissions["is_reseller"]) { ?>
                    {"visible": false, "targets": [5,6]}
                    <?php } ?>
                ],
                order: [[ 0, "desc" ]],
                pageLength: <?=$rAdminSettings["default_entries"] ?: 10?>
            });
            $('#stream_search').keyup(function(){
                $('#datatable-streampage').DataTable().search($(this).val()).draw();
            })
            $('#show_entries').change(function(){
                $('#datatable-streampage').DataTable().page.len($(this).val()).draw();
            })
            $('#category_id').change(function(){
                $("#datatable-streampage").DataTable().ajax.reload( null, false );
            })
            $('#filter').change(function(){
                $("#datatable-streampage").DataTable().ajax.reload( null, false );
            })
            <?php if (!$detect->isMobile()) { ?>
            setTimeout(reloadStreams, 5000);
            <?php } ?>
        });
        </script>

        <!-- third party js -->
        <script src="assets/libs/select2/select2.min.js"></script>
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
        <script src="assets/libs/magnific-popup/jquery.magnific-popup.min.js"></script>
        <!-- third party js ends -->

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
    </body>
</html>