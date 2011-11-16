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
      this directory and run this again.\n";
    exit;
  }
}

// make sure we can write to the db_file
if (!touch($db_file)) {
    echo "Unable to write to the db file: " . $db_file . "\n";
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
    // pass all args
    // shift off the name of the command and report
    // before we pass the arguments to the function
    array_shift($argv);
    array_shift($argv);
    call_user_func_array('report', $argv);
    break;

  case 'current':
    currentActivity();
    break;

  default:
    help();
    break;
}

/**
 * Start an activity.
 *
 * Check to see if an activity has started already and end it so we are
 * only doing one thing at at time.
 *
 * @see endActivity()
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
 * End an activity and clean up the tmpfile.
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
    echo "Nothing\n";
    exit;
  }
  $line = file_get_contents($tmpfile);

  list($time, $activity) = explode('|', $line, 2);

  $date = date("H:i:s",$time);

  printf("%s since %s\n", $activity, $date);
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

/**
 * Build a report from the db files in the dbdir directory and prints it out
 * to the screen.
 *
 * @param
 *   Start date in the format MM/DD/YYYY or null if you would like to start
 *   from the very first files
 *
 * @param
 *   End date in the format MM/DD/YYYY or null if you would like everything
 *   up until the latest date (including today)
 *
 */
function report() {
  $args = func_get_args();
  $report = array();

  if($dh = opendir(DBDIR)) {
    while (FALSE !== ($file = readdir($dh))) {
      // skip dot files
      if (preg_match('/^\./', $file)) {
        continue;
      }

      /**
       * We aren't limiting the output currently.
       */
      $tasks = file(DBDIR . "/" . $file);

      foreach($tasks as $task) {
        list($start, $end, $activity) = explode('|', $task, 3);

        $report[$start] = format_report_line($start, $end, $activity);
      }
    }
  }

  // let's sort the report to make things easier to find
  ksort($report);

  if (sizeof($report > 0)) {
    printf("\n\n%-10s %-60s %-10s\n", 'Date', 'Activity', 'Duration');
    echo hr();
    foreach($report as $line) {
      echo $line . "\n";
    }
    echo hr();
  }
  else {
    echo "No activities found for this timeframe\n";
  }


  //msg('This functionality has not been built yet.', 'warning');
  //exit();
}

function hr() {
  $line = str_repeat('-', 10);
  $line .= '+';
  $line .= str_repeat('-', 60);
  $line .= '+';
  $line .= str_repeat('-', 10);
  $line .= "\n";

  return $line;
}

/**
 * Format the report line for easy layout and printing to the screen
 *
 * @param
 *   Start time of the activity in Unix Time
 *
 * @param
 *   End time of the activity in Unix Time
 *
 * @param
 *   Activity
 */
function format_report_line($start, $end, $activity) {
  $duration = calculateTime($start, $end);

  $hour = sprintf("%02d", $duration['hours']);
  $minute = sprintf("%02d", $duration['minutes']);
  $second = sprintf("%02d", $duration['seconds']);

  $duration_string = $hour . 'h' . $minute . 'm' . $second . 's';

  return sprintf("%-10s|%-60s|%-10s", date("m/d/Y", $start), trim($activity), $duration_string);
}

/**
 * Simply checks the date that is passed to it, if it's valid
 * return the date without slashes
 *
 * @param
 *   Date in the format MM/DD/YYYY
 */
function filedate_from_date($date) {

  if($date) {
    list($month, $day, $year) = explode('/', $date);
    if(checkdate($month, $day, $year)) {
      return $year . $month . $day;
    }
  }

  return FALSE;
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
