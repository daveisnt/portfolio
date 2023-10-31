<?php

  /*
  Next steps:
   - A page that records max/min temps for each day.
   - Set the GPIO pins to OUTPUT mode on reboot
   - Wire up vent #2 on GPIO 4/5
  
  
  */


  $t0 = -20;
  $t1 = -20;
  $t2 = -20;
  $t3 = -20;
  $chart_type = $_GET["chart"];

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
  
  $conn = new mysqli("localhost", "pisql", "neatlady", "Greenhouse");
  
?>

<head>
  <title>Control Panel</title>
  
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['TimeStamp', 'Bottom', 'Top', 'Soil', 'Outside', 'Vent 1', 'Vent 2'],
<?php
  $sql = "SELECT Timestamp,T0,T1,T2,T3,V1,V2 FROM Temperature ORDER BY Timestamp ASC";
  if ($chart_type == "3d") {
    $sql = "SELECT * FROM (SELECT * FROM Temperature ORDER BY Timestamp DESC LIMIT 864) Var1 ORDER BY Timestamp ASC";
  } else if ($chart_type == "1w") {
    $sql = "SELECT * FROM (SELECT * FROM Temperature ORDER BY Timestamp DESC LIMIT 2016) Var1 ORDER BY Timestamp ASC";
  } else if ($chart_type == "24h") {
    $sql = "SELECT * FROM (SELECT * FROM Temperature ORDER BY Timestamp DESC LIMIT 288) Var1 ORDER BY Timestamp ASC";
  } else if ($chart_type == "1h") {
    $sql = "SELECT * FROM (SELECT * FROM Temperature ORDER BY Timestamp DESC LIMIT 12) Var1 ORDER BY Timestamp ASC";
  } else if ($chart_type == "6h") {
    $sql = "SELECT * FROM (SELECT * FROM Temperature ORDER BY Timestamp DESC LIMIT 72) Var1 ORDER BY Timestamp ASC";
  }
$result = $conn->query($sql);
  $nr = $result->num_rows;
  if ($nr>0) {
	$row = [];
	for ($i = 0; $i < $nr; $i++) {
	  $row = $result->fetch_assoc();
	  $ts = substr(explode(" ",$row["Timestamp"])[1],0,5);
	  if ($i > 0 and $i < ($nr-1)) {
	    echo "['".$ts."',".$row["T1"].",".$row["T3"].",".$row["T2"].",".$row["T0"].",".($row["V1"]/5).",".(($row["V2"]/5)+0.1)."]";
	  } else {
	    echo "['".$ts."',".$row["T1"].",".$row["T3"].",".$row["T2"].",".$row["T0"].",".($row["V1"]/5).",".(($row["V2"]/5)+0.1)."]";
	  }
	  if ($i < ($nr-1)) { 
	    echo ",\n"; 
	  } else {
		echo "\n";
	  }
	}
	$v1 = $row["V1"];
	$v2 = $row["V2"];
  } else { echo "nr = ".$nr; }
?>		  
        ]);

        var options = {
          title: 'Historic Temperature (degrees Celsius)',
		  curveType: 'function',
          legend: { position: 'top' },
		  chartArea: { width: '90%', top: '5%', bottom: '5%' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>  
  
  
</head>
<body>
  <html>
  <div name="wrapper" style="width: 1000px;">
    <p align=right><button type="button" onclick="location.href='MinMax.php'"><h1> Temp. Ranges </h1></button>

	<div name="toppart">
	<center><h1>Greenhouse Control Panel</h1></center>
    
<!--    <img src="GreenhouseSchematic.png" width=100%>  -->
    <br><br>
    </div>
    <div name="thetable">

    <table>
	  <tr>
	    <td>
	      <table>
            <tr>
              <td align="right"><h1>Outside = </h1></td>
              <td><h1> <?php echo $t0; ?> deg C</h1></td>
            </tr>
            <tr>
              <td align="right"><h1>Back Bottom = </h1></td>
              <td><h1> <?php echo $t1; ?> deg C</h1></td>
            </tr>
            <tr>
              <td align="right"><h1>Back Top = </h1></td>
              <td><h1> <?php echo $t3; ?> deg C</h1></td>
            </tr>
            <tr>
              <td align="right"><h1>Soil = </h1></td>
              <td><h1> <?php echo $t2; ?> deg C</h1></td>
            </tr>
	      </table>
        </td>
		<td><h1>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>
	      <table>
            <tr>
              <td>
		        <h1>Vent #1 (<?php echo ($v1 * 2.5); ?>%)&nbsp;&nbsp;&nbsp;&nbsp;
		        <button type="button" onclick="window.open('vent.php?vent=1&direction=open&time=5','emptyFrame')"><h2>Open</button>&nbsp;&nbsp;&nbsp;&nbsp;
		        <button type="button" onclick="window.open('vent.php?vent=1&direction=close&time=5','emptyFrame')"><h2>Close</button>
		      </td>
            </tr>
            <tr>
              <td>
		        <h1>Vent #2 (<?php echo ($v2 * 2.5); ?>%)&nbsp;&nbsp;&nbsp;&nbsp;
		        <button type="button" onclick="window.open('vent.php?vent=2&direction=open&time=5','emptyFrame')"><h2>Open</button>&nbsp;&nbsp;&nbsp;&nbsp;
		        <button type="button" onclick="window.open('vent.php?vent=2&direction=close&time=5','emptyFrame')"><h2>Close</button>
  			  </td>
            </tr>
          </table>	
	    </td>
	  </tr>
    </table>
	</div>
	<div id="curve_chart" style="width: 1000px; height: 1200px; left: 0px;">
	</div>
	<div>
	  <button type="button" onclick="location.replace('?chart=1h')"><h1> Last hour </h1></button>
	  &nbsp;&nbsp;&nbsp;
	  <button type="button" onclick="location.replace('?chart=6h')"><h1> Last 6 hours </h1></button>
	  &nbsp;&nbsp;&nbsp;
	  <button type="button" onclick="location.replace('?chart=24h')"><h1> Last 24 hours </h1></button>
	  &nbsp;&nbsp;&nbsp;
	  <button type="button" onclick="location.replace('?chart=3d')"><h1> Last 3 days </h1></button>
	  &nbsp;&nbsp;&nbsp;
	  <button type="button" onclick="location.replace('?chart=1w')"><h1> Last week </h1></button>
	  &nbsp;&nbsp;&nbsp;
	  <button type="button" onclick="location.replace('?chart=All')"><h1> All Data </h1></button>
	</div>
  </html>
  <iframe width="0" height="0" frameborder="0" scrolling="no" vspace="0" hspace="0" marginheight="0" marginwidth="0" src="about:blank" name="emptyFrame"></iframe>
</body>


<?php
  $conn->close();
?>

