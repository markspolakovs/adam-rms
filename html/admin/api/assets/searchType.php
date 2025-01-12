<?php
require_once __DIR__ . '/../apiHeadSecure.php';

if (isset($_POST['other_instances_id'])) {
    //For Asset Migration, we want the assetTypes that are in the instance we are migrating to, or are not in any instance
    //Check user has permission to transfer assets in this instance 
    if (!$AUTH->instancePermissionCheck("ASSETS:TRANSFER")) die("403");
    //Check other instance exists for this user
    if (array_search($_POST['other_instances_id'], array_column($AUTH->data['instances'], 'instances_id')) === false) die("404");
    //check user has permission in other instance 
    if (!in_array("ASSETS:TRANSFER", $AUTH->data['instances'][array_search($_POST['other_instances_id'], array_column($AUTH->data['instances'], 'instances_id'))]['permissions'])) die("403");

    $DBLIB->where("(assetTypes.instances_id IS NULL OR assetTypes.instances_id = '" . $_POST['other_instances_id'] . "')");
} else {
    //We want the assetTypes that are in the current instance, or are not in any instance
    $DBLIB->where("(assetTypes.instances_id IS NULL OR assetTypes.instances_id = '" . $AUTH->data['instance']['instances_id'] . "')");
}

if (isset($_POST['manufacturer'])) $DBLIB->where("assetTypes.manufacturers_id", $_POST['manufacturer']);

$DBLIB->join("manufacturers", "manufacturers.manufacturers_id=assetTypes.manufacturers_id", "LEFT");
$DBLIB->join("assetCategories", "assetCategories.assetCategories_id=assetTypes.assetCategories_id", "LEFT");
$DBLIB->join("assetCategoriesGroups", "assetCategoriesGroups.assetCategoriesGroups_id=assetCategories.assetCategoriesGroups_id", "LEFT");
if (isset($_POST['term'])) {
    $DBLIB->where("(
        assetTypes_description LIKE '%" . $bCMS->sanitizeStringMYSQL($_POST['term']) . "%' OR
        assetTypes_name LIKE '%" . $bCMS->sanitizeStringMYSQL($_POST['term']) . "%'
    )");
} else $DBLIB->orderBy("assetTypes_name", "ASC");
$assets = $DBLIB->get("assetTypes", 15, ["assetTypes_name", "assetTypes_id", "assetCategories_name", "assetCategoriesGroups_name", "manufacturers.manufacturers_name"]);
if (!$assets) finish(false, ["code" => "LIST-ASSETTYPES-FAIL", "message"=> "Could not search"]);
else finish(true, null, $assets);
