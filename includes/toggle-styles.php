<?php
$data = json_decode(file_get_contents('php://input'), true);
$CSS_BASE_PATH = $data['path'];
$currentlyEnabledStylesheet = $data['currentEnabledStylesheet'];
session_start();
$accessiblePath = $CSS_BASE_PATH . "/symbiota/condensed.css?ver=6.css";
$condensedPath = $CSS_BASE_PATH . "/symbiota/accessibility-compliant.css?ver=6.css";
if($currentlyEnabledStylesheet === $condensedPath){
    $_SESSION['active_stylesheet'] = $accessiblePath;
} else{
    $_SESSION['active_stylesheet'] = $condensedPath;
}
echo $_SESSION['active_stylesheet'];
?>