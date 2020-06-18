<?php
require_once __DIR__ . '/../apiHeadSecure.php';

if (isset($_POST['term'])) $PAGEDATA['search'] = $bCMS->sanitizeString($_POST['term']);
else $PAGEDATA['search'] = null;

if (isset($_POST['page'])) $page = $bCMS->sanitizeString($_POST['page']);
else $page = 1;
$DBLIB->pageLimit = (isset($_POST['pageLimit']) ? $_POST['pageLimit'] : 20); //Users per page
if (isset($_POST['category'])) $DBLIB->where("assetTypes.assetCategories_id", $_POST['category']);
if (isset($_POST['manufacturer'])) $DBLIB->where("manufacturers.manufacturers_id", $_POST['manufacturer']);
$DBLIB->orderBy("assetCategories.assetCategories_id", "ASC");
$DBLIB->orderBy("assetTypes.assetTypes_name", "ASC");
$DBLIB->join("manufacturers", "manufacturers.manufacturers_id=assetTypes.manufacturers_id", "LEFT");
$DBLIB->where("((SELECT COUNT(*) FROM assets WHERE assetTypes.assetTypes_id=assets.assetTypes_id AND assets.instances_id = '" . $AUTH->data['instance']['instances_id'] . "' AND assets_deleted = 0" . (!isset($_POST['all']) ? ' AND assets.assets_linkedTo IS NULL' : '') .") > 0)");
$DBLIB->join("assetCategories", "assetCategories.assetCategories_id=assetTypes.assetCategories_id", "LEFT");
$DBLIB->join("assetCategoriesGroups", "assetCategoriesGroups.assetCategoriesGroups_id=assetCategories.assetCategoriesGroups_id", "LEFT");
if (strlen($PAGEDATA['search']) > 0) {
    //Search
    $DBLIB->where("(
		manufacturers_name LIKE '%" . $bCMS->sanitizeString($PAGEDATA['search']) . "%' OR
		assetTypes_description LIKE '%" . $bCMS->sanitizeString($PAGEDATA['search']) . "%' OR
		assetTypes_name LIKE '%" . $bCMS->sanitizeString($PAGEDATA['search']) . "%' 
    )");
}
$assets = $DBLIB->arraybuilder()->paginate('assetTypes', $page, ["assetTypes.*", "manufacturers.*", "assetCategories.*", "assetCategoriesGroups_name"]);
$PAGEDATA['pagination'] = ["page" => $page, "total" => $DBLIB->totalPages];

$PAGEDATA['assets'] = [];
foreach ($assets as $asset) {
    $DBLIB->where("assets.instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("assets.assetTypes_id", $asset['assetTypes_id']);
    $DBLIB->where("assets_deleted", 0);
    if (!isset($_POST['all'])) $DBLIB->where("(assets.assets_linkedTo IS NULL)");
    $DBLIB->orderBy("assets.assets_tag", "ASC");
    $assetTags = $DBLIB->get("assets", null, ["assets_id", "assets_notes","assets_tag","asset_definableFields_1","asset_definableFields_2","asset_definableFields_3","asset_definableFields_4","asset_definableFields_5","asset_definableFields_6","asset_definableFields_7","asset_definableFields_8","asset_definableFields_9","asset_definableFields_10","assets_dayRate","assets_weekRate","assets_value","assets_mass"]);
    $asset['count'] = count($assetTags);
    $asset['fields'] = explode(",", $asset['assetTypes_definableFields']);
    $asset['thumbnail'] = $bCMS->s3List(2, $asset['assetTypes_id']);
    $asset['tags'] = [];
    foreach ($assetTags as $tag) {
        if ($AUTH->data['users_selectedProjectID'] != null and $AUTH->instancePermissionCheck(31)) {
            //Check availability
            $DBLIB->where("assets_id", $tag['assets_id']);
            $DBLIB->where("assetsAssignments.assetsAssignments_deleted", 0);
            $DBLIB->where("(projects.projects_id = '" . $PAGEDATA['thisProject']['projects_id'] . "' OR projects.projects_status NOT IN (" . implode(",", $GLOBALS['STATUSES-AVAILABLE']) . "))");
            $DBLIB->join("projects", "assetsAssignments.projects_id=projects.projects_id", "LEFT");
            $DBLIB->where("projects.projects_deleted", 0);
            $DBLIB->where("((projects_dates_deliver_start >= '" . $PAGEDATA['thisProject']["projects_dates_deliver_start"]  . "' AND projects_dates_deliver_start <= '" . $PAGEDATA['thisProject']["projects_dates_deliver_end"] . "') OR (projects_dates_deliver_end >= '" . $PAGEDATA['thisProject']["projects_dates_deliver_start"] . "' AND projects_dates_deliver_end <= '" . $PAGEDATA['thisProject']["projects_dates_deliver_end"] . "') OR (projects_dates_deliver_end >= '" . $PAGEDATA['thisProject']["projects_dates_deliver_end"] . "' AND projects_dates_deliver_start <= '" . $PAGEDATA['thisProject']["projects_dates_deliver_start"] . "'))");
            $tag['assignment'] = $DBLIB->get("assetsAssignments", null, ["assetsAssignments.projects_id", "projects.projects_name"]);
        }
        $tag['flagsblocks'] = assetFlagsAndBlocks($tag['assets_id']);
        $asset['tags'][] = $tag;
    }

    $PAGEDATA['assets'][] = $asset;
}
finish(true, null, $PAGEDATA['assets']);
?>
