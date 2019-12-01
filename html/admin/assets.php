<?php
require_once __DIR__ . '/common/headSecure.php';

$PAGEDATA['pageConfig'] = ["TITLE" => "Assets", "BREADCRUMB" => false];

if (isset($_GET['q'])) $PAGEDATA['search'] = $bCMS->sanitizeString($_GET['q']);
else $PAGEDATA['search'] = null;

if (isset($_GET['page'])) $page = $bCMS->sanitizeString($_GET['page']);
else $page = 1;
$DBLIB->pageLimit = 20; //Users per page

if (isset($_GET['category'])) $DBLIB->where("assetTypes.assetCategories_id", $_GET['category']);
if (isset($_GET['manufacturer'])) $DBLIB->where("manufacturers.manufacturers_id", $_GET['manufacturer']);
$DBLIB->orderBy("assetCategories.assetCategories_id", "ASC");
$DBLIB->orderBy("assetTypes.assetTypes_name", "ASC");
$DBLIB->join("manufacturers", "manufacturers.manufacturers_id=assetTypes.manufacturers_id", "LEFT");
$DBLIB->where("((SELECT COUNT(*) FROM assets WHERE assetTypes.assetTypes_id=assets.assetTypes_id AND assets.instances_id = '" . $AUTH->data['instance']['instances_id'] . "' AND assets_deleted = 0) > 0)");
$DBLIB->join("assetCategories", "assetCategories.assetCategories_id=assetTypes.assetCategories_id", "LEFT");
if (strlen($PAGEDATA['search']) > 0) {
	//Search
	$DBLIB->where("(
		manufacturers_name LIKE '%" . $bCMS->sanitizeString($PAGEDATA['search']) . "%' OR
		assetTypes_description LIKE '%" . $bCMS->sanitizeString($PAGEDATA['search']) . "%' OR
		assetTypes_name LIKE '%" . $bCMS->sanitizeString($PAGEDATA['search']) . "%' 
    )");
}
$assets = $DBLIB->arraybuilder()->paginate('assetTypes', $page, ["assetTypes.*", "manufacturers.*", "assetCategories.*"]);
$PAGEDATA['assets'] = [];
foreach ($assets as $asset) {
	$DBLIB->where("assets.instances_id", $AUTH->data['instance']['instances_id']);
	$DBLIB->where("assets.assetTypes_id", $asset['assetTypes_id']);
	$asset['count'] = $DBLIB->getValue("assets", "COUNT(*)");
	$PAGEDATA['assets'][] = $asset;
}
$PAGEDATA['pagination'] = ["page" => $page, "total" => $DBLIB->totalPages];

echo $TWIG->render('assets.twig', $PAGEDATA);
?>