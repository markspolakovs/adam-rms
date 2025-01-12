<?php
/**
 * API
 * \projects\followParentStatus.php
 * Set whether a subproject follows the status of its parent project
 *
 * Arguments:
 *  - projects_id: a project
 *  - follow: boolean to follow or not
 */

require_once __DIR__ . '/../apiHeadSecure.php';

if (!$AUTH->instancePermissionCheck("PROJECTS:CREATE") or !isset($_POST['projects_id']) or !isset($_POST['follow'])) finish(false);



if ($_POST['follow'] === 'true') {
    // Get project
    $DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("projects.projects_deleted", 0);
    $DBLIB->where("projects.projects_id", $_POST['projects_id']);
    $DBLIB->where("projects.projects_parent_project_id", NULL, 'IS NOT');
    $project = $DBLIB->getone("projects", ["projects_parent_project_id"]);
    if (!$project) finish(false);

    //get parent project status
    $DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("projects.projects_deleted", 0);
    $DBLIB->where("projects.projects_id", $project['projects_parent_project_id']);
    $parentProject = $DBLIB->getone("projects", ["projectsStatuses_id"]);
    if (!$parentProject) finish(false);

    //set subproject status to parent project status
    $DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("projects.projects_deleted", 0);
    $DBLIB->where("projects.projects_id", $_POST['projects_id']);
    $DBLIB->update("projects", ["projectsStatuses_id" => $parentProject['projectsStatuses_id'], "projects_status_follow_parent" => 1]);
    
    finish(true, null, ["changed" => true]);
} else {
    //stop following parent status
    $DBLIB->where("projects.instances_id", $AUTH->data['instance']['instances_id']);
    $DBLIB->where("projects.projects_deleted", 0);
    $DBLIB->where("projects.projects_id", $_POST['projects_id']);
    $DBLIB->update("projects", ["projects_status_follow_parent" => 0]);

    finish(true, null, ["changed" => true]);
}