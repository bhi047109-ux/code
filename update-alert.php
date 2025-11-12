<?php
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents('alert.json', json_encode(["alert" => $data["alert"]]));
?>
