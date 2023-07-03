<?php
/**
 * testClasses.php
 *
 * Pre-deployment test harness to identify files that are marked as having
 * dependencies on particular Symbiota schema versions, for which the
 * current database schema version does not meet those dependencies.
 *
 * Use this file on a test installation after applying a schema update
 * to check to see if the developers have updated all of the classes
 * that declare schema version dependencies to support the new schema version.
 *
 * @author Paul J. Morris
 */

/*
 * To add a class to this test:
 * (1) Add an implementation of the checkSchema() method to that class.
 * (2) Add the class name and path/filename to the $classes array.
 * (3) Add the class to the switch block below.
 */

// Prepare environment:
include_once("config/symbini.php");
$failure = false;

// List of classes that contain a checkSchema() method, along with their files.
$classes = Array();
$classes['OmOccurrences'] = 'classes/OmOccurrences.php';
$classes['OmOccurDeterminations'] = 'classes/OmOccurDeterminations.php';
$classes['ImageShared'] = 'classes/ImageShared.php';
//$classes['AgentManager'] = 'classes/AgentManager.php';
//$classes['Agent'] = 'classes/AgentManager.php';

foreach($classes as $class => $file) {
   include_once("$SERVER_ROOT/$file");

   $t = null;

   // Instantiate an instance of the class to test.
   switch ($class) {
      case "OmOccurrences":
       $t = new OmOccurrences();
       break;
      case "OmOccurDeterminations":
       $t = new OmOccurDeterminations();
       break;
      case "ImageShared":
       $t = new ImageShared();
       break;
   }
   if ($t!=null) {
     if (!$t->checkSchema()) {
        echo "[Warning: $class in $file does not match the Symbiota schema version.]\n";
        $failure = true;
     } else {
        echo "$class: Pass\n";
     }
  }
}

if (!$failure) {
   echo "Done. All listed classes support the current schema.\n";
} else {
   echo "Done. There were errors.\n";
}
?>