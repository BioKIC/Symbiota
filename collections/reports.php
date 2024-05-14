<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ReportsManager.php');

$rm = new ReportsManager();

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <title>SCAN Reports</title>
    <style>
        tr:nth-child(even) {background: #CCC}
        tr:nth-child(odd) {background: #FFF}
    </style>
</head>
<body>
    <h1 class="page-heading">Reports</h1>
<h3>New identifications to the species level (by determiner)</h3>
<table>
    <thead><tr><th>Full Name</th><th>Number of Determinations</th></tr></thead>
    <?php
    $report = $rm->getNewIdentByDeterminerReport();

    foreach($report as $row) {
        echo "<tr><td>".$row['identifiedby']."</td><td>".$row['numberOfDet']."</td></tr>";
    }

    ?>
</table>
<h3>New identifications to the species level (by specialists with taxonomic interests)</h3>
<table>
    <thead><tr><th>Full Name</th><th>Family</th><th>Number of Determinations</th></tr></thead>
    <?php
    $report = $rm->getNewIdentBySpecialistReport();

    foreach($report as $row) {
        echo "<tr><td>".$row['fullname']."</td><td>".$row['family']."</td><td>".$row['numberOfDet']."</td></tr>";
    }

    ?>
</table>
<h3>Families with new identifications to the species level</h3>
<table>
    <thead><tr><th>Family</th><th>Number of Determinations</th></tr></thead>
    <?php
    $report = $rm->getNewIdentByFamilyReport();

    foreach($report as $row) {
        echo "<tr><td>".$row['family']."</td><td>".$row['numberOfDet']."</td></tr>";
    }

    ?>
</table>
</body>
</html>