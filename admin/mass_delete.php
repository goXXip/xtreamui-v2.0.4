<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }
if (!$rPermissions["is_admin"]) { exit; }

if (isset($_POST["submit_streams"])) {
    $rStreams = json_decode($_POST["streams"], True);
    foreach ($rStreams as $rStream) {
        $db->query("DELETE FROM `streams_sys` WHERE `stream_id` = ".intval($rStream).";");
        $db->query("DELETE FROM `streams` WHERE `id` = ".intval($rStream).";");
    }
    $_STATUS = 0;
}

if (isset($_POST["submit_movies"])) {
    $rMovies = json_decode($_POST["movies"], True);
    foreach ($rMovies as $rMovie) {
        $result = $db->query("SELECT `server_id` FROM `streams_sys` WHERE `stream_id` = ".intval($rMovie).";");
        if (($result) && ($result->num_rows > 0)) {
            while ($row = $result->fetch_assoc()) {
                deleteMovieFile($row["server_id"], $rStreamID);
            }
        }
        $db->query("DELETE FROM `streams_sys` WHERE `stream_id` = ".intval($rMovie).";");
        $db->query("DELETE FROM `streams` WHERE `id` = ".intval($rMovie).";");
    }
    $_STATUS = 1;
}

if (isset($_POST["submit_users"])) {
    $rUsers = json_decode($_POST["users"], True);
    foreach ($rUsers as $rUser) {
        $db->query("DELETE FROM `users` WHERE `id` = ".intval($rUser).";");
        $db->query("DELETE FROM `user_output` WHERE `user_id` = ".intval($rUser).";");
        $db->query("DELETE FROM `enigma2_devices` WHERE `user_id` = ".intval($rUser).";");
        $db->query("DELETE FROM `mag_devices` WHERE `user_id` = ".intval($rUser).";");
    }
    $_STATUS = 2;
}

if ($rSettings["sidebar"]) {
    include "header_sidebar.php";
} else {
    include "header.php";
}
        if ($rSettings["sidebar"]) { ?>
        <div class="content-page"><div class="content boxed-layout-ext"><div class="container-fluid">
        <?php } else { ?>
        <div class="wrapper boxed-layout-ext"><div class="container-fluid">
        <?php } ?>
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Mass Delete</h4>
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
                            Mass stream delete was successfully executed!
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 1)) { ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            Mass movie delete was successfully executed!
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 2)) { ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            Mass user delete was successfully executed!
                        </div>
                        <?php } ?>
                        <div class="card">
                            <div class="card-body">
                                <div id="basicwizard">
                                    <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-4">
                                        <li class="nav-item">
                                            <a href="#stream-selection" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                <i class="mdi mdi-play mr-1"></i>
                                                <span class="d-none d-sm-inline">Streams</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#movie-selection" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                <i class="mdi mdi-account-card-details-outline mr-1"></i>
                                                <span class="d-none d-sm-inline">Movies</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#user-selection" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                <i class="mdi mdi-server-network mr-1"></i>
                                                <span class="d-none d-sm-inline">Users</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content b-0 mb-0 pt-0">
                                        <div class="tab-pane" id="stream-selection">
                                            <form action="./mass_delete.php" method="POST" id="stream_form">
                                                <input type="hidden" name="streams" id="streams" value="" />
                                                <div class="row">
                                                    <div class="col-md-4 col-6">
                                                        <input type="text" class="form-control" id="stream_search" value="" placeholder="Search Streams...">
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <select id="stream_category_search" class="form-control" data-toggle="select2">
                                                            <option value="" selected>All Categories</option>
                                                            <?php foreach ($rCategories as $rCategory) { ?>
                                                            <option value="<?=$rCategory["id"]?>"<?php if ((isset($_GET["category"])) && ($_GET["category"] == $rCategory["id"])) { echo " selected"; } ?>><?=$rCategory["category_name"]?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <label class="col-md-1 col-2 col-form-label text-center" for="show_entries">Show</label>
                                                    <div class="col-md-2 col-8">
                                                        <select id="show_entries" class="form-control" data-toggle="select2">
                                                            <?php foreach (Array(10, 25, 50, 250, 500, 1000) as $rShow) { ?>
                                                            <option<?php if ($rAdminSettings["default_entries"] == $rShow) { echo " selected"; } ?> value="<?=$rShow?>"><?=$rShow?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 col-2">
                                                        <button type="button" class="btn btn-info waves-effect waves-light" onClick="toggleStreams()">
                                                            <i class="mdi mdi-selection"></i>
                                                        </button>
                                                    </div>
                                                    <table id="datatable-md1" class="table table-borderless mb-0">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th class="text-center">ID</th>
                                                                <th>Stream Name</th>
                                                                <th>Category</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <ul class="list-inline wizard mb-0" style="margin-top:20px;">
                                                    <li class="list-inline-item float-right">
                                                        <input name="submit_streams" type="submit" class="btn btn-primary" value="Delete Streams" />
                                                    </li>
                                                </ul>
                                            </form>
                                        </div>
                                        <div class="tab-pane" id="movie-selection">
                                            <form action="./mass_delete.php" method="POST" id="movie_form">
                                                <input type="hidden" name="movies" id="movies" value="" />
                                                <div class="row">
                                                    <div class="col-md-3 col-6">
                                                        <input type="text" class="form-control" id="movie_search" value="" placeholder="Search Movies...">
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <select id="movie_category_search" class="form-control" data-toggle="select2">
                                                            <option value="" selected>All Categories</option>
                                                            <?php foreach (getCategories("movie") as $rCategory) { ?>
                                                            <option value="<?=$rCategory["id"]?>"<?php if ((isset($_GET["category"])) && ($_GET["category"] == $rCategory["id"])) { echo " selected"; } ?>><?=$rCategory["category_name"]?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <select id="movie_filter" class="form-control" data-toggle="select2">
                                                            <option value="" selected>No Filter</option>
                                                            <option value="1">Encoded</option>
                                                            <option value="2">Encoding</option>
                                                            <option value="3">Down</option>
                                                            <option value="4">Ready</option>
                                                            <option value="5">Direct</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 col-8">
                                                        <select id="movie_show_entries" class="form-control" data-toggle="select2">
                                                            <?php foreach (Array(10, 25, 50, 250, 500, 1000) as $rShow) { ?>
                                                            <option<?php if ($rAdminSettings["default_entries"] == $rShow) { echo " selected"; } ?> value="<?=$rShow?>"><?=$rShow?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 col-2">
                                                        <button type="button" class="btn btn-info waves-effect waves-light" onClick="toggleMovies()">
                                                            <i class="mdi mdi-selection"></i>
                                                        </button>
                                                    </div>
                                                    <table id="datatable-md2" class="table table-borderless mb-0">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th class="text-center">ID</th>
                                                                <th>Stream Name</th>
                                                                <th>Category</th>
                                                                <th class="text-center">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <ul class="list-inline wizard mb-0" style="margin-top:20px;">
                                                    <li class="list-inline-item float-right">
                                                        <input name="submit_movies" type="submit" class="btn btn-primary" value="Delete Movies" />
                                                    </li>
                                                </ul>
                                            </form>
                                        </div>
                                        <div class="tab-pane" id="user-selection">
                                            <form action="./mass_delete.php" method="POST" id="user_form">
                                                <input type="hidden" name="users" id="users" value="" />
                                                <div class="row">
                                                    <div class="col-md-3 col-6">
                                                        <input type="text" class="form-control" id="user_search" value="" placeholder="Search Users...">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select id="reseller_search" class="form-control" data-toggle="select2">
                                                            <option value="" selected>All Resellers</option>
                                                            <?php foreach (getRegisteredUsers() as $rRegisteredUser) { ?>
                                                            <option value="<?=$rRegisteredUser["id"]?>"><?=$rRegisteredUser["username"]?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <select id="user_filter" class="form-control" data-toggle="select2">
                                                            <option value="" selected>No Filter</option>
                                                            <option value="1">Active</option>
                                                            <option value="2">Disabled</option>
                                                            <option value="3">Banned</option>
                                                            <option value="4">Expired</option>
                                                            <option value="5">Trial</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 col-8">
                                                        <select id="user_show_entries" class="form-control" data-toggle="select2">
                                                            <?php foreach (Array(10, 25, 50, 250, 500, 1000) as $rShow) { ?>
                                                            <option<?php if ($rAdminSettings["default_entries"] == $rShow) { echo " selected"; } ?> value="<?=$rShow?>"><?=$rShow?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 col-2">
                                                        <button type="button" class="btn btn-info waves-effect waves-light" onClick="toggleUsers()">
                                                            <i class="mdi mdi-selection"></i>
                                                        </button>
                                                    </div>
                                                    <table id="datatable-md3" class="table table-borderless mb-0">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th class="text-center">ID</th>
                                                                <th>Username</th>
                                                                <th></th>
                                                                <th>Reseller</th>
                                                                <th class="text-center">Status</th>
                                                                <th class="text-center">Online</th>
                                                                <th class="text-center">Trial</th>
                                                                <th class="text-center">Expiration</th>
                                                                <th></th>
                                                                <th class="text-center">Conns.</th>
                                                                <th></th>
                                                                <th></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <ul class="list-inline wizard mb-0" style="margin-top:20px;">
                                                    <li class="list-inline-item float-right">
                                                        <input name="submit_users" type="submit" class="btn btn-primary" value="Delete Users" />
                                                    </li>
                                                </ul>
                                            </form>
                                        </div>
                                    </div> <!-- tab-content -->
                                </div> <!-- end #basicwizard-->
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
        <script src="assets/libs/jquery-ui/jquery-ui.min.js"></script>
        <script src="assets/libs/jquery-nice-select/jquery.nice-select.min.js"></script>
        <script src="assets/libs/switchery/switchery.min.js"></script>
        <script src="assets/libs/select2/select2.min.js"></script>
        <script src="assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
        <script src="assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>
        <script src="assets/libs/clockpicker/bootstrap-clockpicker.min.js"></script>
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

        <!-- Plugins js-->
        <script src="assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>

        <!-- Tree view js -->
        <script src="assets/libs/treeview/jstree.min.js"></script>
        <script src="assets/js/pages/treeview.init.js"></script>
        <script src="assets/js/pages/form-wizard.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
        <script>
        var rStreams = [];
        var rMovies = [];
        var rUsers = [];
        
        function getStreamCategory() {
            return $("#stream_category_search").val();
        }
        function getMovieCategory() {
            return $("#movie_category_search").val();
        }
        function getMovieFilter() {
            return $("#movie_filter").val();
        }
        function getUserFilter() {
            return $("#user_filter").val();
        }
        function getReseller() {
            return $("#reseller_search").val();
        }
        
        function toggleStreams() {
            $("#datatable-md1 tr").each(function() {
                if ($(this).hasClass('selectedfilter')) {
                    $(this).removeClass('selectedfilter').removeClass('ui-selected').removeClass("selected");
                    if ($(this).find("td:eq(0)").html()) {
                        window.rStreams.splice($.inArray($(this).find("td:eq(0)").html(), window.rStreams), 1);
                    }
                } else {            
                    $(this).addClass('selectedfilter').addClass('ui-selected').addClass("selected");
                    if ($(this).find("td:eq(0)").html()) {
                        window.rStreams.push($(this).find("td:eq(0)").html());
                    }
                }
            });
        }
        function toggleMovies() {
            $("#datatable-md2 tr").each(function() {
                if ($(this).hasClass('selectedfilter')) {
                    $(this).removeClass('selectedfilter').removeClass('ui-selected').removeClass("selected");
                    if ($(this).find("td:eq(0)").html()) {
                        window.rMovies.splice($.inArray($(this).find("td:eq(0)").html(), window.rMovies), 1);
                    }
                } else {            
                    $(this).addClass('selectedfilter').addClass('ui-selected').addClass("selected");
                    if ($(this).find("td:eq(0)").html()) {
                        window.rMovies.push($(this).find("td:eq(0)").html());
                    }
                }
            });
        }
        function toggleUsers() {
            $("#datatable-md3 tr").each(function() {
                if ($(this).hasClass('selectedfilter')) {
                    $(this).removeClass('selectedfilter').removeClass('ui-selected').removeClass("selected");
                    if ($(this).find("td:eq(0)").html()) {
                        window.rUsers.splice($.inArray($(this).find("td:eq(0)").html(), window.rUsers), 1);
                    }
                } else {            
                    $(this).addClass('selectedfilter').addClass('ui-selected').addClass("selected");
                    if ($(this).find("td:eq(0)").html()) {
                        window.rUsers.push($(this).find("td:eq(0)").html());
                    }
                }
            });
        }
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
            $('select').select2({width: '100%'})
            $("#stream_form").submit(function(e){
                $("#streams").val(JSON.stringify(window.rStreams));
                if (window.rStreams.length == 0) {
                    e.preventDefault();
                    $.toast("Select at least one stream to delete.");
                }
            });
            $("#movie_form").submit(function(e){
                $("#movies").val(JSON.stringify(window.rMovies));
                if (window.rMovies.length == 0) {
                    e.preventDefault();
                    $.toast("Select at least one movie to delete.");
                }
            });
            $("#user_form").submit(function(e){
                $("#users").val(JSON.stringify(window.rUsers));
                if (window.rUsers.length == 0) {
                    e.preventDefault();
                    $.toast("Select at least one users to delete.");
                }
            });
            $(document).keypress(function(event){
                if (event.which == '13') {
                    event.preventDefault();
                }
            });
            $("form").attr('autocomplete', 'off');
            sTable = $("#datatable-md1").DataTable({
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "./table_search.php",
                    "data": function(d) {
                        d.id = "stream_list",
                        d.category = getStreamCategory()
                    }
                },
                columnDefs: [
                    {"className": "dt-center", "targets": [0]}
                ],
                "rowCallback": function(row, data) {
                    if ($.inArray(data[0], window.rSelected) !== -1) {
                        $(row).addClass("selected");
                    }
                },
                pageLength: <?=$rAdminSettings["default_entries"] ?: 10?>
            });
            $('#stream_search').keyup(function(){
                sTable.search($(this).val()).draw();
            })
            $('#show_entries').change(function(){
                sTable.page.len($(this).val()).draw();
            })
            $('#stream_category_search').change(function(){
                sTable.ajax.reload(null, false);
            })
            rTable = $("#datatable-md2").DataTable({
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "./table_search.php",
                    "data": function(d) {
                        d.id = "movie_list",
                        d.category = getMovieCategory(),
                        d.filter = getMovieFilter()
                    }
                },
                columnDefs: [
                    {"className": "dt-center", "targets": [0,3]}
                ],
                "rowCallback": function(row, data) {
                    if ($.inArray(data[0], window.rSelected) !== -1) {
                        $(row).addClass("selected");
                    }
                },
                pageLength: <?=$rAdminSettings["default_entries"] ?: 10?>
            });
            $('#movie_search').keyup(function(){
                rTable.search($(this).val()).draw();
            })
            $('#movie_show_entries').change(function(){
                rTable.page.len($(this).val()).draw();
            })
            $('#movie_category_search').change(function(){
                rTable.ajax.reload(null, false);
            })
            $('#movie_filter').change(function(){
                rTable.ajax.reload( null, false );
            })
            
            uTable = $("#datatable-md3").DataTable({
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "./table_search.php",
                    "data": function(d) {
                        d.id = "users",
                        d.filter = getUserFilter(),
                        d.reseller = getReseller()
                    }
                },
                columnDefs: [
                    {"className": "dt-center", "targets": [0,4,6,7,9]},
                    {"visible": false, "targets": [2,5,8,10,11]}
                ],
                "rowCallback": function(row, data) {
                    if ($.inArray(data[0], window.rSelected) !== -1) {
                        $(row).addClass("selected");
                    }
                },
                pageLength: <?=$rAdminSettings["default_entries"] ?: 10?>
            });
            $('#user_search').keyup(function(){
                uTable.search($(this).val()).draw();
            })
            $('#user_show_entries').change(function(){
                uTable.page.len($(this).val()).draw();
            })
            $('#reseller_search').change(function(){
                uTable.ajax.reload(null, false);
            })
            $('#user_filter').change(function(){
                uTable.ajax.reload( null, false );
            })
            
            $("#datatable-md1").selectable({
                filter: 'tr',
                selected: function (event, ui) {
                    if ($(ui.selected).hasClass('selectedfilter')) {
                        $(ui.selected).removeClass('selectedfilter').removeClass('ui-selected').removeClass("selected");
                        window.rStreams.splice($.inArray($(ui.selected).find("td:eq(0)").html(), window.rStreams), 1);
                    } else {            
                        $(ui.selected).addClass('selectedfilter').addClass('ui-selected').addClass("selected");
                        window.rStreams.push($(ui.selected).find("td:eq(0)").html());
                    }
                }
            });
            $("#datatable-md2").selectable({
                filter: 'tr',
                selected: function (event, ui) {
                    if ($(ui.selected).hasClass('selectedfilter')) {
                        $(ui.selected).removeClass('selectedfilter').removeClass('ui-selected').removeClass("selected");
                        window.rMovies.splice($.inArray($(ui.selected).find("td:eq(0)").html(), window.rMovies), 1);
                    } else {            
                        $(ui.selected).addClass('selectedfilter').addClass('ui-selected').addClass("selected");
                        window.rMovies.push($(ui.selected).find("td:eq(0)").html());
                    }
                }
            });
            $("#datatable-md3").selectable({
                filter: 'tr',
                selected: function (event, ui) {
                    if ($(ui.selected).hasClass('selectedfilter')) {
                        $(ui.selected).removeClass('selectedfilter').removeClass('ui-selected').removeClass("selected");
                        window.rUsers.splice($.inArray($(ui.selected).find("td:eq(0)").html(), window.rUsers), 1);
                    } else {            
                        $(ui.selected).addClass('selectedfilter').addClass('ui-selected').addClass("selected");
                        window.rUsers.push($(ui.selected).find("td:eq(0)").html());
                    }
                }
            });
        });
        </script>
    </body>
</html>