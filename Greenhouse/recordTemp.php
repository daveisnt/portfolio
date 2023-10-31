<?php
  $t0 = -19;
  $t1 = -19;
  $t2 = -19;
  $t3 = -19;
  $t4 = -19;
  $v1 = 0;
  $v2 = 0;
  $target = 25; // Target temperature in degrees
  

  $t0_file = "/sys/bus/w1/devices/28-0120222e8e72/w1_slave";
  $t0_data = file($t0_file, FILE_IGNORE_NEW_LINES);
  if(preg_match('/YES$/', $t0_data[0])) {
    if (preg_match('/t=((|-)\d+)$/',$t0_data[1],$matches,PREG_OFFSET_CAPTURE)) {
      $t0 = $matches[1][0] / 1000;
    }  
  } 
  
  $t1_file = "/sys/bus/w1/devices/28-0120225444c2/w1_slave";
  $t1_data = file($t1_file, FILE_IGNORE_NEW_LINES);
  if(preg_match('/YES$/', $t1_data[0])) {
    if (preg_match('/t=((|-)\d+)$/',$t1_data[1],$matches,PREG_OFFSET_CAPTURE)) {
      $t1 = $matches[1][0] / 1000;
    }  
  } 

  $t2_file = "/sys/bus/w1/devices/28-012022311531/w1_slave";
  $t2_data = file($t2_file, FILE_IGNORE_NEW_LINES);
  if(preg_match('/YES$/', $t2_data[0])) {
    if (preg_match('/t=((|-)\d+)$/',$t2_data[1],$matches,PREG_OFFSET_CAPTURE)) {
      $t2 = $matches[1][0] / 1000;
    }  
  } 

  $t3_file = "/sys/bus/w1/devices/28-012022225cc4/w1_slave";
  $t3_data = file($t3_file, FILE_IGNORE_NEW_LINES);
  if(preg_match('/YES$/', $t3_data[0])) {
    if (preg_match('/t=((|-)\d+)$/',$t3_data[1],$matches,PREG_OFFSET_CAPTURE)) {
      $t3 = $matches[1][0] / 1000;
    }  
  } 
  
  // Update database with temperatures  
  $conn = new mysqli("localhost", "pisql", "neatlady", "Greenhouse");
  if ($conn->connect_error) {
    die("Greenhouse: Connection failed - " . $conn->connect_error);
  }

  // Get vent status
  $sql = "SELECT Timestamp,V1,V2 FROM Temperature ORDER BY Timestamp DESC LIMIT 1";
  $result = $conn->query($sql);
  if ($result) {
    $row = $result->fetch_assoc();
	$v1 = $row["V1"];
	$v2 = $row["V2"];
  } else {
    error_log("Greenhouse: Error reading from database - " . $sql . "<br>" . $conn->error ."",0);
  }

  // Update Temperature table
  $sql = "INSERT INTO Temperature (t0, t1, t2, t3, t4, v1, v2)
  VALUES (".$t0.", ".$t1.", ".$t2.", ".$t3.", ".$t4.", ".$v1.", ".$v2.")";

  if ($conn->query($sql) === TRUE) {
    error_log("Greenhouse: Recorded temperatures.",0);
  } else {
    error_log("Greenhouse: Error - " . $sql . "<br>" . $conn->error ."",0);
  }

  // Get Max and Min temperature for today
    // Read date from table and check date on pi -- make sure format agrees
  error_log("Greenhouse: Updating max/min temperature.",0);
  $today = date("Y-m-d");
  $min = -99;
  $max = 99;
  $sql = "SELECT Timestamp,Min,Max FROM MinMax WHERE Timestamp='".$today."'";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) { // We have previously recorded temperatures from today
    $row = $result->fetch_assoc();
	$min = $row["Min"];
	$max = $row["Max"];
    // Calculate if we have a higher temperature now
	if ($t1 > $max) { $max = $t1; }
	if ($t1 < $min) { $min = $t1; }
    // Update MinMax table
    $sql2 = "UPDATE MinMax SET Min=".$min.", Max=".$max." WHERE Timestamp='".$today."';";
    if ($conn->query($sql2) === TRUE) {
      error_log("Greenhouse: Updated MinMax.",0);
    } else {
      error_log("Greenhouse: Error writing to MinMax",0);
    }
  } else { 	// If no matching date then this is the first reading of the day
    error_log("Greenhouse: First temp measurement of the day!",0);
	$min = $t1; // Consider using t3
	$max = $t1;
	// Write new line to MinMax table
    $sql2 = "INSERT INTO MinMax (Timestamp,Min, Max) VALUES ('".$today."',".$min.", ".$max.")";
    if ($conn->query($sql2) === TRUE) {
      error_log("Greenhouse: Recorded first temp of the day.",0);
    } else {
      error_log("Greenhouse: Error recording first temp of the day: ".$today,0);
    }
  }
  $conn->close();
  
  
  // Open vents if necessary
  
  // Compare t3 (top temp sensor) to target temperature.
  $delta = $t3 - $target;
  if ($delta > 0) { // It's too hot, consider opening the hatch
    //if ($delta > 25) { $delta = 25; } // Maximum opening is 25 units
	if (($delta > 5) && ($v1 > 29)) {
	  $t = 30 - $v2; // Time to open is 30secs minus how much it's already open
	  if ($t <= 0) { 
	    $t = 0; // Do nothing
	  } else {
	    exec("php /var/www/html/vent.php 2 open ".$t.""); // Open vent #2
	  }
	} else 	if ($delta > 5) { // It's 5 degrees or more hotter than it should be
      $t = 30 - $v1;
	  if ($t <= 0) { 
	    $t = 0; // Do nothing
	  } else {
	    exec("php /var/www/html/vent.php 1 open ".$t.""); // Open the vent more
	  }
	} else if ($delta > 2) { // It's 2-5 degrees too hot
      $t = 20 - $v1;
	  if ($t <= 0) { 
	    $t = 0; // Do nothing
	  } else {
	    exec("php /var/www/html/vent.php 1 open ".$t.""); // Open the vent more
	  }
	} else if ($delta > 0) { // It's 0-2 degrees too hot
      $t = 10 - $v1;
	  if ($t <= 0) { 
	    $t = 0; // Do nothing
	  } else {
	    exec("php /var/www/html/vent.php 1 open ".$t.""); // Open the vent more
	  }
	}
  } else { // It's too cold, consider closing the hatch
    if ($v2 > 0) { // If the hatch is open, close it
      $t = $v2+3; //Extra 3 seconds because this actuator closes weirdly
      if ($t <= 0) { 
        $t = 0; // Do nothing
	  } else {
	    exec("php /var/www/html/vent.php 2 close ".$t.""); // Open the vent more
  	  }
	} else if ($v1 > 0) { // If the hatch is open, close it
      $t = $v1;
      if ($t <= 0) { 
        $t = 0; // Do nothing
	  } else {
	    exec("php /var/www/html/vent.php 1 close ".$t.""); // Open the vent more
  	  }
	}
  }
  
  

?>
