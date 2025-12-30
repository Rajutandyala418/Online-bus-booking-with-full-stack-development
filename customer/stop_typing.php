<?php
include('../include/db_connect.php');
$username=$_POST['username'];
$conn->query("DELETE FROM support_chat WHERE username='$username' AND message='typing...'");
