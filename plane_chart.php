<?php
require "dbi.php" ;
$id = mysqli_real_escape_string($mysqli_link, $_REQUEST['id']) ;

// General information from plane table
$result = mysqli_query($mysqli_link, "select * from $table_planes where id='$id' and ressource = 0")
	or die("Cannot access plane $id information: " . mysqli_error($mysqli_link)) ;
$plane = mysqli_fetch_array($result) ;
if (!$plane) die("No such plane: $id") ;
?>
<html>
<head>
<link href="<?=$favicon?>" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<!-- script type="text/javascript" src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['annotatedtimeline']}]}"></script-->
<script type="text/javascript" src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['annotationchart']}]}"></script>

<script type="text/javascript">
google.load('visualization', '1', {'packages':['annotationchart']});
google.setOnLoadCallback(drawChart);

function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('date', 'Date');
        data.addColumn('number', 'Pilots');
        data.addColumn('number', 'Mechanics');
	data.addColumn('number', '<?=$plane['type_entretien']?>') ;
	data.addRows([
<?php

// Engine hours from mechanics logbooks
$result = mysqli_query($mysqli_link, "SELECT compteur AS data, compteur_date AS date
		FROM $table_planes_history 
		WHERE plane = '$id' 
		ORDER BY date ASC")
	or journalise($userId, "E", "Cannot access engine clock history for $id: " . mysqli_error($mysqli_link)) ;
while ($line = mysqli_fetch_array($result)) {
	$this_date = $line['date'] ;
    $this_data = $line['data'] ;
    $dates[$this_date] = true ;
    $data[1][$this_date] = $this_data ;
}

// Engine hours from pilots logbooks
$data_field = ($plane['compteur_vol']) ? 'l_flight_end_hour' : 'l_end_hour' ;
$result = mysqli_query($mysqli_link, "SELECT $data_field AS data, DATE(l_audit_time) AS date
		FROM $table_logbook
		WHERE l_plane = '$id' AND $data_field IS NOT NULL AND $data_field > 0 
		ORDER BY date ASC")
	or journalise($userId, "E", "Cannot access logbook for $id: " . mysqli_error($mysqli_link)) ;
while ($line = mysqli_fetch_array($result)) {
	$this_date = $line['date'] ;
    $this_data = $line['data'] ;
    $dates[$this_date] = true ;
    $data[0][$this_date] = $this_data ;
}

$first = true ;
ksort($dates) ;
foreach ($dates as $this_date=>$foo) {
        list($year, $month, $day) = explode('-', $this_date) ;
        if (! $first) print(",\n") ;
        $first = false ;
        print("[new Date('$this_date')") ;
        for ($i = 0; $i <= 1; $i++) {
                if (isset($data[$i][$this_date]) and $data[$i][$this_date] > 0) {
                        $this_data = $data[$i][$this_date] ;
                        print(", $this_data") ;
                } else
                        print(", null") ;
        }
	// Always add the next maintenance as an horizontal line
	print(", $plane[entretien]") ;
    print("]") ;
}

// TODO add 100 hour to plane[entretien] as the max and 1 year default time width

?>

        ]);

//	data.addColumn('number', '<?=$plane['type_entretien']?>') ;
        var options = {
      	title: 'Engine clock',
	  	numberFormats: '####0',
	  	XnumberFormats: '##,##0',
	  	displayRangeSelector: false,
	  	interpolateNulls: true,
      	chart: {
			interpolateNulls: true
	  	},
	  	scaleType: 'allmaximized'
      };

//        var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('curve_chart'));
        var chart = new google.visualization.AnnotationChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
</script>
<title><?=($plane['compteur_vol']) ? 'Flight' : 'Engine'?> indexes for <?=$id?></title>
</head>
<body>
<h2><?=($plane['compteur_vol']) ? 'Flight' : 'Engine'?> indexes for <?=$id?></h2>
    <div id="curve_chart" style="width: 80%; height: 80%"></div>
</body>
</html>
