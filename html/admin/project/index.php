<?php
require_once __DIR__ . '/../common/headSecure.php';

if (!$AUTH->instancePermissionCheck(20) or !isset($_GET['id'])) die("Sorry - you can't access this page");

$DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
$DBLIB->where("projects.projects_deleted", 0);
$DBLIB->where("projects.projects_id", $_GET['id']);
$DBLIB->join("clients", "projects.clients_id=clients.clients_id", "LEFT");
$DBLIB->join("users", "projects.projects_manager=users.users_userid", "LEFT");
$PAGEDATA['project'] = $DBLIB->getone("projects", ["projects.*", "clients.clients_name", "users.users_name1", "users.users_name2", "users.users_email"]);
if (!$PAGEDATA['project']) die("404");

$DBLIB->where("auditLog.auditLog_deleted", 0);
$DBLIB->where("auditLog.projects_id", $PAGEDATA['project']['projects_id']);
$DBLIB->join("users", "auditLog.users_userid=users.users_userid", "LEFT");
$DBLIB->orderBy("auditLog.auditLog_timestamp", "DESC");
$PAGEDATA['project']['auditLog'] = $DBLIB->get("auditLog",null, ["auditLog.*", "users.users_name1", "users.users_name2", "users.users_email"]);

$PAGEDATA['pageConfig'] = ["TITLE" => $PAGEDATA["project"]['projects_name'], "BREADCRUMB" => false];

//Edit Options
if ($AUTH->instancePermissionCheck(22)) {
    $DBLIB->where("instances_id", $AUTH->data['instance']['instances_id']);
    $PAGEDATA['clients'] = $DBLIB->get("clients", null, ["clients_id", "clients_name"]);
}
if ($AUTH->instancePermissionCheck(23)) {
    $DBLIB->orderBy("users.users_name1", "ASC");
    $DBLIB->orderBy("users.users_name2", "ASC");
    $DBLIB->orderBy("users.users_created", "ASC");
    $DBLIB->where("users_deleted", 0);
    $DBLIB->join("userInstances", "users.users_userid=userInstances.users_userid","LEFT");
    $DBLIB->join("instancePositions", "userInstances.instancePositions_id=instancePositions.instancePositions_id","LEFT");
    $DBLIB->where("instances_id",  $AUTH->data['instance']['instances_id']);
    $DBLIB->where("userInstances.userInstances_deleted",  0);
    $PAGEDATA['potentialManagers'] = $DBLIB->get('users', null, ["users.users_name1", "users.users_name2", "users.users_userid"]);
}

echo $TWIG->render('project/index.twig', $PAGEDATA);
?>