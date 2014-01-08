<?php

/**
 * PHP code illustrating how to check the status of a specific AU in
 * a set of LOCKSS boxes.
 *
 * The IP address of the machine running this script will need to be
 * whitelisted in the "Allow Access" list (under "Admin Access Control")
 * on each of the boxes you are querying. Also, each box will need a user
 * with "View debug info" permissions.
 */

$boxes = array(
  'Box 1' => array('url' => 'http://lockssbox1.example.com:8081', 'user' => 'user1', 'password' => 'passwd1'),
  'Box 2' => array('url' => 'http://lockssbox2.example.com:8081', 'user' => 'user2', 'password' => 'passwd2'),
  'Box 3' => array('url' => 'http://lockssbox3.example.com:8081', 'user' => 'user3', 'password' => 'passwd3'),
  'Box 4' => array('url' => 'http://lockssbox4.example.com:8081', 'user' => 'user4', 'password' => 'passwd4'),
);

// AU IDs are available at http://yourbox:8081/DaemonStatus?table=AllAuids,
// or by querying the SOAP API using a script such as one_box_all_aus.php.
$au_id = "ca|sfu|lib|plugin|cartoons|SFUCartoonsPlugin&base_url~http%3A%2F%2Fedocs%2Elib%2Esfu%2Eca%2Fprojects%2FCartoons%2Flockss%2F&cartoonist~1&year~2000";

print "Checking status of AU $au_id\n";

foreach ($boxes as $box => $settings) {
  // Set up the SOAP client.
  $client = new SoapClient($settings['url'] . ":8081/ws/DaemonStatusService?wsdl",
    array('login' => $settings['user'], 'password' => $settings['password']));

  // Check to see if the SOAP API says the daemon is ready.
  $ready = $client->isDaemonReady();
  if (!$ready) {
    die("LOCKSS daemon on $box is not responding\n");
  }

  // Get the status, lastPollResult, and lastCrawlResult propertied
  // for the AU and assemble a nice summary message for the user.
  try {
    $status = $client->getAuStatus(array('auId' => $au_id));
    foreach ($status->return as $key => $value) {
      if ($key == 'status') {
        $au_status = $value;
      }
      if ($key == 'lastPollResult') {
        $au_last_poll_result = $value;
      }
      if ($key == 'lastCrawlResult') {
        $au_last_crawl_result = $value;
      }
    }
    print "$box reports status: $au_status, lastPollResult: $au_last_poll_result, lastCrawlResult: $au_last_crawl_result\n";
  }
  catch (SoapFault $e) {
    // If the AU is not found, LOCKSS returns a null pointer exception.
    if ($e->faultstring == 'java.lang.NullPointerException') {
      print "$box reports AU can't be found\n";
    }
    else {
      // Catch all other errors.
      print "$box has a problem: " . $e->faultstring . "\n";
    }
    continue;
  }
  unset($status);
}

?>
