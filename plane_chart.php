<?php
/*
   Copyright 2014-2019 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/require "dbi.php" ;
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
// Engine hours from pilots logbooks
$result = mysqli_query($mysqli_link, "select l_end_hour as data, date(l_audit_time) as date from $table_logbook where l_plane = '$id' and l_end_hour is not null and l_end_hour > 0 order by date asc")
	or die("Cannot access logbook for $id: " . mysqli_error($mysqli_link)) ;
while ($line = mysqli_fetch_array($result)) {
	$this_date = $line['date'] ;
        $this_data = $line['data'] ;
        $dates[$this_date] = true ;
        $data[0][$this_date] = $this_data ;
}

// Engine hours from mechanics logbooks
$result = mysqli_query($mysqli_link, "select compteur as data, compteur_date as date from $table_planes_history where plane = '$id' order by date asc")
	or die("Cannot access engine clock history for $id: " . mysqli_error($mysqli_link)) ;
while ($line = mysqli_fetch_array($result)) {
	$this_date = $line['date'] ;
        $this_data = $line['data'] ;
        $dates[$this_date] = true ;
        $data[1][$this_date] = $this_data ;
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

?>

        ]);

//	data.addColumn('number', '<?=$plane['type_entretien']?>') ;
        var options = {
          title: 'Engine clock',
	  numberFormats: '##,##0',
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
<title>Engine clocks for <?=$id?></title>
</head>
<body>
<h2>Engine clocks for <?=$id?></h2>
    <div id="curve_chart" style="width: 1000px; height: 600px"></div>
</body>
</html>
