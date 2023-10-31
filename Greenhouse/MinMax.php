<?php
  $conn = new mysqli("localhost", "pisql", "neatlady", "Greenhouse");

  echo "<head><title>Control Panel</title></head>\n";
  echo "<body><html><div name=\"wrapper\" style=\"width: 1000px;\">\n";
  echo "<p align=right><button type=\"button\" onclick=\"location.href='ControlPanel.php?chart=6h'\"><h1> Control Panel </h1></button>";
  echo "<div name=\"toppart\"><center><h1>Daily Temperature Ranges</h1></center><br><br></div>\n";
  echo "<div name=\"thetable\">\n";

  $sql = "SELECT Timestamp,Min,Max FROM MinMax ORDER BY Timestamp DESC LIMIT 90";
  $result = $conn->query($sql);
  echo "<center><table border=1 cellpadding=\"2\"><tr><td style=\"width:200px\"><h1>Date</td><td style=\"width:200px\"><h1>Min</td><td style=\"width:200px\"><h1>Max</td></tr>\n";
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      echo "<tr><td><h2>".$row["Timestamp"]."</td><td style=\"text-align:right\"><h2><right>".$row["Min"]."</td><td style=\"text-align:right\"><h2>".$row["Max"]."</td></tr>\n";
	}	
  }
  echo "</table></div></html></body>";
  
?>