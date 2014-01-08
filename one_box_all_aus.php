<?php

/**
 * PHP code illustrating how to query the LOCKSS SOAP API for
 * a list all AUs, and retrieve information about each one. The
 * API is available at http://lockss.box.org:8081/ws.
 *
 * In addition to telling the SOAP client the username and password
 * of the LOCKSS user, you will need to add the IP address of the
 * computer running this script to the "Allow Access" list under
 * "Admin Access Control" in your box's admin UI. The LOCKSS user
 * need only have "View debug info" permissions.
 *
 * Since this file just prints to STDOUT, the easiest way of writing
 * the data to a file is to redirect STDOUT to a file using (on *nix
 * systems) php one_box_all_aus.php > output.txt.
 */

$client = new SoapClient("http://lockssbox.example.org:8081/ws/DaemonStatusService?wsdl",
  array('login' => "user", 'password' => "passwd"));

// Check to see if the SOAP API says the daemon is ready.
$ready = $client->isDaemonReady();
if (!$ready) {
  die("LOCKSS daemon is not responding\n");
}

// Get a list of all the AU IDs.
$aus = $client->getAuIds();

if (count($aus) === 0) {
  die("No AUs found\n");
}

// Loop through the list, retrieve info about each AU,
// and print it to STDOUT.
foreach ($aus->return as $au) {
  if (is_object($au)) {
    if (strlen($au->id)) {
      try {
        $status = $client->getAuStatus(array('auId' => $au->id));
        print "\n\n";
        print "auId: " . $au->id . "\n";
        print "Properties:\n";
        foreach ($status->return as $key => $value) {
           print "  $key => $value\n";
        }
      }
      catch (SoapFault $e) {
        print_r($e);
      }
    }
  }
}

?>
