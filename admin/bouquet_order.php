<?php
include "functions.php";
if (!isset($_SESSION['hash'])) { header("Location: ./login.php"); exit; }
if (!$rPermissions["is_admin"]) { exit; }

if (isset($_POST["reorder"])) {
    $rOrder = json_decode($_POST["stream_order_array"], True);
    if (is_array($rOrder)) {
        $rStreamOrder = $rOrder["stream"];
        foreach ($rOrder["movie"] as $rID) {
            $rStreamOrder[] = $rID;
        }
        $db->query("UPDATE `bouquets` SET `bouquet_channels` = '".$db->real_escape_string(json_encode($rStreamOrder))."', `bouquet_series` = '".$db->real_escape_string(json_encode($rOrder["series"]))."' WHERE `id` = ".intval($_POST["reorder"]).";");
    }
}

if (!isset($_GET["id"])) { exit; }
$rBouquet = getBouquet($_GET["id"]);
if (!$rBouquet) { exit; }

$rListings = Array("stream" => Array(), "movie" => Array(), "series" => Array());
$rOrdered = Array("stream" => Array(), "movie" => Array(), "series" => Array());
$rChannels = json_decode($rBouquet["bouquet_channels"], True);
$rSeries = json_decode($rBouquet["bouquet_series"], True);

if (is_array($rChannels)) {
    $result = $db->query("SELECT `streams`.`id`, `streams`.`type`, `streams`.`category_id`, `streams`.`stream_display_name`, `stream_categories`.`category_name` FROM `streams`, `stream_categories` WHERE `streams`.`category_id` = `stream_categories`.`id` AND `streams`.`id` IN (".$db->real_escape_string(join(",", $rChannels)).");");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            if ($row["type"] == 2) {
                $rListings["movie"][intval($row["id"])] = $row;
            } else {
                $rListings["stream"][intval($row["id"])] = $row;
            }
        }
    }
}
if (is_array($rSeries)) {
    $result = $db->query("SELECT `series`.`id`, `series`.`category_id`, `series`.`title`, `stream_categories`.`category_name` FROM `series`, `stream_categories` WHERE `series`.`category_id` = `stream_categories`.`id` AND `series`.`id` IN (".$db->real_escape_string(join(",", $rSeries)).");");
    if (($result) && ($result->num_rows > 0)) {
        while ($row = $result->fetch_assoc()) {
            $rListings["series"][intval($row["id"])] = $row;
        }
    }
}

foreach ($rChannels as $rChannel) {
    if (isset($rListings["stream"][intval($rChannel)])) {
        $rOrdered["stream"][] = $rListings["stream"][intval($rChannel)];
    } else if (isset($rListings["movie"][intval($rChannel)])) {
        $rOrdered["movie"][] = $rListings["movie"][intval($rChannel)];
    }
}

foreach ($rSeries as $rItem) {
    if (isset($rListings["series"][intval($rItem)])) {
        $rOrdered["series"][] = $rListings["series"][intval($rItem)];
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
                                    <li>
                                        <a href="bouquet.php?id=<?=$_GET["id"]?>">
                                            <button type="button" class="btn btn-success waves-effect waves-light btn-sm">
                                                <i class="mdi mdi-pencil-outline"></i> Edit Bouquet
                                            </button>
                                        </a>
                                    </li>
                                </ol>
                            </div>
                            <h4 class="page-title"><?=$rBouquet["bouquet_name"]?></h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="./bouquet_order.php?id=<?=$_GET["id"]?>" method="POST" id="bouquet_order_form">
                                    <input type="hidden" id="stream_order_array" name="stream_order_array" value="" />
                                    <input type="hidden" name="reorder" value="<?=$_GET["id"]?>" />
                                    <div id="basicwizard">
                                        <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-4">
                                            <li class="nav-item">
                                                <a href="#bouquet-stream" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="la la-play-circle-o mr-1"></i>
                                                    <span class="d-none d-sm-inline">Streams</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#bouquet-movie" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="la la-video-camera mr-1"></i>
                                                    <span class="d-none d-sm-inline">Movies</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#bouquet-series" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="la la-tv mr-1"></i>
                                                    <span class="d-none d-sm-inline">Series</span>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content b-0 mb-0 pt-0">
                                            <div class="tab-pane" id="bouquet-stream">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p class="sub-header">
                                                            To re-order a stream, drag it up or down the list using the <i class="mdi mdi-view-sequential"></i> icon. Click Save Changes at the bottom once finished.
                                                        </p>
                                                        <div class="custom-dd dd" id="stream_order">
                                                            <ol class="dd-list">
                                                                <?php foreach ($rOrdered["stream"] as $rStream) { ?>
                                                                <li class="dd-item dd3-item stream-<?=$rStream["id"]?>" data-id="<?=$rStream["id"]?>">
                                                                    <div class="dd-handle dd3-handle"></div>
                                                                    <div class="dd3-content"><?=$rStream["stream_display_name"]?>
                                                                        <span style="float:right;">
                                                                            <?=$rStream["category_name"]?>
                                                                        </span>
                                                                    </div>
                                                                </li>
                                                                <?php } ?>
                                                            </ol>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0 add-margin-top-20">
                                                    <li class="list-inline-item">
                                                       <a href="javascript: void(0);" onClick="AtoZ('stream')" class="btn btn-info">Sort All A to Z</a>
                                                    </li>
                                                    <li class="next list-inline-item float-right">
                                                        <button type="submit" class="btn btn-primary waves-effect waves-light">Save Changes</button>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="tab-pane" id="bouquet-movie">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p class="sub-header">
                                                            To re-order a movie, drag it up or down the list using the <i class="mdi mdi-view-sequential"></i> icon. Click Save Changes at the bottom once finished.
                                                        </p>
                                                        <div class="custom-dd dd" id="stream_order_movie">
                                                            <ol class="dd-list">
                                                                <?php foreach ($rOrdered["movie"] as $rStream) { ?>
                                                                <li class="dd-item dd3-item stream-<?=$rStream["id"]?>" data-id="<?=$rStream["id"]?>">
                                                                    <div class="dd-handle dd3-handle"></div>
                                                                    <div class="dd3-content"><?=$rStream["stream_display_name"]?>
                                                                        <span style="float:right;">
                                                                            <?=$rStream["category_name"]?>
                                                                        </span>
                                                                    </div>
                                                                </li>
                                                                <?php } ?>
                                                            </ol>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0 add-margin-top-20">
                                                    <li class="list-inline-item">
                                                       <a href="javascript: void(0);" onClick="AtoZ('movie')" class="btn btn-info">Sort All A to Z</a>
                                                    </li>
                                                    <li class="next list-inline-item float-right">
                                                        <button type="submit" class="btn btn-primary waves-effect waves-light">Save Changes</button>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="tab-pane" id="bouquet-series">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p class="sub-header">
                                                            To re-order a series, drag it up or down the list using the <i class="mdi mdi-view-sequential"></i> icon. Click Save Changes at the bottom once finished.
                                                        </p>
                                                        <div class="custom-dd dd" id="stream_order_series">
                                                            <ol class="dd-list">
                                                                <?php foreach ($rOrdered["series"] as $rStream) { ?>
                                                                <li class="dd-item dd3-item stream-<?=$rStream["id"]?>" data-id="<?=$rStream["id"]?>">
                                                                    <div class="dd-handle dd3-handle"></div>
                                                                    <div class="dd3-content"><?=$rStream["title"]?>
                                                                        <span style="float:right;">
                                                                            <?=$rStream["category_name"]?>
                                                                        </span>
                                                                    </div>
                                                                </li>
                                                                <?php } ?>
                                                            </ol>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0 add-margin-top-20">
                                                    <li class="list-inline-item">
                                                       <a href="javascript: void(0);" onClick="AtoZ('series')" class="btn btn-info">Sort All A to Z</a>
                                                    </li>
                                                    <li class="list-inline-item float-right">
                                                        <button type="submit" class="btn btn-primary waves-effect waves-light">Save Changes</button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
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
        <script src="assets/libs/nestable2/jquery.nestable.min.js"></script>

        <!-- Plugins js-->
        <script src="assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>

        <!-- Tree view js -->
        <script src="assets/libs/treeview/jstree.min.js"></script>
        <script src="assets/js/pages/treeview.init.js"></script>
        <script src="assets/js/pages/form-wizard.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
        <script>
        function AtoZ(rType) {
            $.getJSON("./api.php?action=sort_bouquet&bouquet_id=<?=$rBouquet["id"]?>&type=" + rType, function(rData) {
                if (rData.result === true) {
                    location.reload();
                }
            });
        }
        
        $(document).ready(function() {
            $("#stream_order").nestable({maxDepth: 1});
            $("#stream_order_movie").nestable({maxDepth: 1});
            $("#stream_order_series").nestable({maxDepth: 1});
            $("#bouquet_order_form").submit(function(e){
                var rOrder = {"stream": [], "movie": [], "series": []};
                $("#stream_order li").each(function() {
                    rOrder["stream"].push($(this).data("id"));
                });
                $("#stream_order_movie li").each(function() {
                    rOrder["movie"].push($(this).data("id"));
                });
                $("#stream_order_series li").each(function() {
                    rOrder["series"].push($(this).data("id"));
                });
                $("#stream_order_array").val(JSON.stringify(rOrder));
            });
            
        });
        </script>
    </body>
</html>