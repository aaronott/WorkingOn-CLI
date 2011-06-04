#!/usr/bin/env php
<?php
define('DBDIR', $_SERVER['HOME'] . '/.workingon');

$db_file = DBDIR . '/' . date('Ymd') . '_workingon.log';
$tmpfile = DBDIR . '/.tmpwo';

if (!isset($argv[1])) {
  help();
  exit;
}

// check to see if the dbdir exists, if not, create it
if (file_exists(DBDIR)) {
  if (!is_dir(DBDIR) || !is_writable(DBDIR)) {
    msg(DBDIR . " must be a writeable directory.", 'ERROR');
    exit;
  }
}
else {
  if (!mkdir(DBDIR)) {
    echo "Unable to make the data directory at " . DBDIR . " please create
      this directory and run this again.";
    exit;
  }
}

// make sure we can write to the db_file
if (!touch($db_file)) {
    echo "Unable to write to the db file: " . $db_file;
    exit;
}

switch ($argv[1]) {
  case 'start':
    if (!isset($argv[2])) {
      help();
      exit;
    }
    startActivity($argv[2]);
    break;

  case 'end':
    endActivity();
    break;

  case 'report':
    if (!isset($argv[3])) {
      help();
      exit;
    }
    report($argv[2], $argv[3]);
    break;

  case 'current':
    currentActivity();
    break;

  default:
    help();
    break;
}

/**
 * Start an activity
 *
 * Check to see if an activity has started already and end it so we are
 * only doing one thing at at time.
 *
 * @param
 *   The activity string to be stored.
 *     This should be something that is descriptive and could contain anything
 *     such as "Project: Task"
 */
function startActivity($activity) {
  global $tmpfile;
  $activity = trim($activity);

  if (file_exists($tmpfile)) {
    endActivity();
  }

  $line = mktime() . '|' . $activity;

  if (!file_put_contents($tmpfile, $line)) {
    msg("Unable to write to the tempfile. Cannot start activity.", 'ERROR');
  }
  msg("Start: $activity", 'status');
}

/**
 * end an activity and clean up the tmpfile
 *
 * Prepare the data to be written to the db and clean up the tmp file
 * prints a status message to the screen.
 *
 * @see writetodb()
 * @see calculateTime()
 *
 * @return bool
 */
function endActivity() {
  global $tmpfile;

  if (!file_exists($tmpfile)) {
    return TRUE;
  }

  $line = file_get_contents($tmpfile);
  // tell expload to limit to two outputs so we can allow for | in the activity
  // description text
  list($start, $activity) = explode('|', $line, 2);
  $end = mktime();
  if (writetodb($start, $end, $activity)) {
    $t = calculateTime($start, $end);
    msg("Ended: $activity after " . $t['hours'] . 'h' . $t['minutes'] . 'm' .$t['seconds'] .'s', 'status');
    unlink($tmpfile);
    return TRUE;
  }
  return FALSE;
}

/**
 * Return the current task in a nice format
 */
function currentActivity() {
  global $tmpfile;

  if(!file_exists($tmpfile)) {
    echo "Nothing";
    exit;
  }
  $line = file_get_contents($tmpfile);

  list($time, $activity) = explode('|', $line, 2);

  $date = date("H:i:s",$time);

  printf("%s since %s", $activity, $date);
}

/**
 * Write the activity to the db
 *
 * Currently we are writing everything to flat files but this could be adapted
 * to write to a db
 *
 * @param
 *   Start time as a unix timestamp
 *
 * @param
 *   End time as a unix timestamp
 *
 * @param
 *   String containing the activity data
 *
 * @return bool
 *   True on successful write otherwise FALSE
 */
function writetodb($start, $end, $activity) {
  global $db_file;

  $line = "$start|$end|$activity\n";
  return file_put_contents($db_file, $line, FILE_APPEND);
}

/**
 * Taken two timestamps return the ellapsed time
 *
 * @param
 *   Unix timestamp of the start time
 *
 * @param
 *   Unix timestamp of the end time
 *
 * @return
 *   An array containing the keys hours, minutes, seconds
 */
function calculateTime($start, $end) {
  $delta = $end - $start;

  $hours = 0;
  $minutes = 0;
  $seconds = 0;

  if ($delta > 0) {
    $hours = floor($delta/60/60);
    $minutes = floor($delta/60%60);
    $seconds = floor($delta%60);
  }
  return array(
    'hours' => $hours,
    'minutes' => $minutes,
    'seconds' => $seconds
  );
}

/**
 * Simple help message
 */
function help() {
  echo "Track the activities you are working on\n\n";
  echo "Examples:\n";
  echo "\tworkingon start 'WorkingOn: Debugging'\n";
  echo "\tworkingon end\n";
  echo "\tworkingon report 01/12/2011 02/12/2011\n";
  echo "\tworkingon current\n";
}

function report($start = NULL, $end = NULL) {
  msg('This functionality has not been built yet.', 'warning');
  exit();
}

/**
 * A standardized messaging output.
 *
 * @param
 *   Message string to be printed
 *
 * @param
 *   Type of message, defaults to success
 */
function msg($msg, $type = 'success') {
  printf("%-60s [%s]\n", $msg, $type);
}
