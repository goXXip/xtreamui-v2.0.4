<?php
include "functions.php";

if (isset($_SESSION['hash'])) {
    header("Location: ./dashboard.php");
} else {
    header("Location: ./login.php");
}
?>