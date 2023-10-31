<?php
  if (isset($argc)) {
	$vent = $argv[1];
	$direction = $argv[2];
	$time = $argv[3];
  } else {
    $vent = $_GET["vent"];
    if ($vent > 2) { $vent = 1; }
    $direction = $_GET["direction"];
    $time = $_GET["time"];
    if ($time > 5) { $time = 5; }
  }
?>

<head>
  <title>Vent Control</title>
</head>
<body>
  <html>
    <h1>Opening Vent #<?php echo $vent; ?> <br><br>
    <button type="button" onclick="javascript:window.close()">Done</button>
	</h1>
  </html>
<body>

<?php
  $v1 = 0;
  $v2 = 0;
  $timestamp = "";

  // Connect to database
  $conn = new mysqli("localhost", "pisql", "neatlady", "Greenhouse");
  if ($conn->connect_error) {
    die("Greenhouse: Connection failed - " . $conn->connect_error);
  }
  // Get current vent state from SQL
  $sql = "SELECT Timestamp,V1,V2 FROM Temperature ORDER BY Timestamp DESC LIMIT 1";
  $result = $conn->query($sql);
  if ($result) {
    $row = $result->fetch_assoc();
	$v1 = $row["V1"];
	$v2 = $row["V2"];
	$timestamp = $row["Timestamp"];
  } else {
    echo "Greenhouse: Error reading from database - " . $sql . "<br>" . $conn->error ."\n";
  }

  // Send commands to IO pins
  if ($vent == 1) { 
    exec("gpio write 3 1"); // No power to motor
    usleep(200000);
	if ($direction == "open") {
	  if ($v1 < 30) {
	    exec("gpio write 2 1"); // Set Vent #1 to Open
        $v1 = $v1 + $time;
	  }
	} else {
	  if ($v1 > 0) {
	    exec("gpio write 2 0"); // Set Vent #1 to Close
        $v1 = $v1 - $time;
	    if ($v1 < 0) { $v1 = 0; }
	  }
	}
    exec("gpio write 3 0"); // Power the motor
    sleep($time);
    exec("gpio write 3 1"); // No power to motor
  }

  if ($vent == 2) { 
    exec("gpio write 5 1"); // No power to motor
    usleep(200000);
	if ($direction == "open") {
	  if ($v2 < 30) {
	    exec("gpio write 4 1"); // Set Vent #2 to Open
        $v2 = $v2 + $time;
	  }
	} else {
	  if ($v2 > 0) {
	    exec("gpio write 4 0"); // Set Vent #2 to Close
        $v2 = $v2 - $time;
	    if ($v2 < 0) { $v2 = 0; }
	  }
	}
    exec("gpio write 5 0"); // Power the motor
    sleep($time);
    exec("gpio write 5 1"); // No power to motor
  }
  
  

  // Update vent states
  $sql = "UPDATE Temperature SET V1=".$v1.", V2=".$v2." WHERE Timestamp='".$timestamp."';";
  if ($conn->query($sql) === TRUE) {
    echo "Greenhouse: Recorded vent state.\n";
  } else {
    echo "Greenhouse: Error writing to databse - " . $sql . "<br>" . $conn->error ."\n";
  }

  $conn->close();

  
  //echo "<script>window.close()</script>";
?>