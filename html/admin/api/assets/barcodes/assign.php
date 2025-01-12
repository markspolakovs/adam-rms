<?php
require_once __DIR__ . '/../../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck("ASSETS:ASSET_BARCODES:EDIT:ASSOCIATE_UNNASOCIATED_BARCODES_WITH_ASSETS")) die("Sorry - you can't access this page");

if (!isset($_POST['tag'])) finish(false, ["code" => "PARAM-ERROR", "message"=> "No data for action"]);

$DBLIB->where("assets.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("assets.assets_tag", "%" . $_POST['tag'], "like");
$asset = $DBLIB->getone("assets",["assets_id","assetTypes_id"]);
if (!$asset) finish(false, ["message"=> "Asset not found"]);
if ($_POST['barcodeid'] === "false") {
    $barcode = $DBLIB->insert("assetsBarcodes",[
        "assetsBarcodes_value" => $_POST['text'],
        "assetsBarcodes_type" => $_POST['type'],
        "users_userid" => $AUTH->data['users_userid'],
        "assetsBarcodes_added" => date("Y-m-d H:i:s")
    ]);
    if (!$barcode) finish(false, ["message"=> "Barcode insert error"]);
    else $_POST['barcodeid'] = $barcode;
}

$DBLIB->where("assetsBarcodes_id", $_POST['barcodeid']);
$DBLIB->where("(assets_id IS NULL)");
$barcode = $DBLIB->getone("assetsBarcodes", ["assetsBarcodes_id"]);
if (!$barcode) finish(false, ["code" => "UPDATE-FAIL", "message"=> "Could not update barcode"]);

$DBLIB->where("assetsBarcodes_id", $barcode['assetsBarcodes_id']);
$result = $DBLIB->update("assetsBarcodes", ["assets_id" => $asset['assets_id'],"assetsBarcodes_deleted"=>0],1);
if (!$result) finish(false, ["code" => "UPDATE-FAIL", "message"=> "Could not update barcode"]);
else {
    $bCMS->auditLog("ASSOCIATE", "assetsBarcodes", $_POST['barcodeid'] . " set to " . $asset['assets_id'], $AUTH->data['users_userid'],null);
    finish(true,null,$asset);
}