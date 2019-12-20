<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Create database connection
$servername = "";
$username = "";
$password = "";
$dbname = "";
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
if(!empty($_GET['gameId']))
{
	$gameId = $_GET['gameId'];
}
else
{
	$gameId=0;
}
$onload = "";
if(!empty($_GET['highlightId']))
{
	$selectedHighlightId = $_GET['highlightId'];
	if(!empty($_GET['action']))
	{
		$action = $_GET['action'];
		if($action == 'upload')
		{
			if ($conn->query("UPDATE highlights set YouTubeLink='PENDING' where HighlightId='$selectedHighlightId'") === TRUE) {
				//echo "<br>Highlight update successful<br>";
			} else {
				echo "Error Highlight update: " . $sql . "<br>" . $conn->error;
			}
		}
	}
}
else
{
	$selectedHighlightId = 0;
}
if ($conn->query("INSERT INTO activity (GameId, HighlightId, IP) VALUES ('$gameId', '$selectedHighlightId', '" . $_SERVER['REMOTE_ADDR']. "')") === TRUE) {
	//echo "<br>Highlight update successful<br>";
} else {
	echo "Error activity insert: " . $sql . "<br>" . $conn->error;
}
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	//SET VARIABLES
	//print_r($_POST);
	if(!empty($_POST['hName']))
	{
		$name = $_POST['hName'];
	}

	if(!empty($_POST['start']))// NEW or UPDATED Highlight
	{
		$start = $_POST['start'];
		if(!empty($_POST['startPre']))
		{
			$startPre = $_POST['startPre'];
		}
		else
		{
			$startPre = $start;
		}
		if(!empty($_POST['end']))
		{
			$end = $_POST['end'];
		}
		if(!empty($_POST['type']))
		{
			$type = $_POST['type'];
		}
		if(!empty($_POST['subtype']))
		{
			$subtype = $_POST['subtype'];
		}
		else
		{
			$subtype = "";
		}
		if(!empty($_POST['tags']))
		{
			$tags = $_POST['tags'];
		}
		if(!empty($_POST['update']))
		{
			$highlightId = $_POST['update'];
			$sql = "UPDATE highlights set Name='" . str_replace("'", "''", $name) . "', StartTime='$start', EndTime='$end', Type='$type', SubType='$subtype', Verified=1 WHERE HighlightId=$highlightId ;";

			$diff = $start - $startPre;
			if($diff <> 0)
			{
				//UPDATE unverified StartTimes by the amound of the shift
				if ($conn->query("UPDATE highlights set StartTime = StartTime + $diff where Verified=0;") === TRUE) {
					//echo "<br>Unverified StartTime update successful<br>";
				} else {
					echo "Error: " . $sql . "<br>" . $conn->error;
				}
			}
			//DELETE old player tags to be recreated with updated ones
			if ($conn->query("DELETE from playerTags WHERE HighlightId=$highlightId;") === TRUE) {
				//echo "Player tag delete successful<br>";
			} else {
				echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}
		else
		{
			$highlightId = 0;
			//INSERT highlight to database
			$team = "Beerbears on Ice";
			$sql = "INSERT INTO highlights (GameId, Team, Name, StartTime, EndTime, Type, SubType, Verified)
			VALUES ('$gameId', '$team', '" . str_replace("'", "''", $name) . "', '$start', '$end', '$type', '$subtype', 1)";
		}

		if ($conn->query($sql) === TRUE) {
			//echo "Highlight created/updated successfully<br>";
			if($highlightId==0) //if insert, not update
				$highlightId = $conn->insert_id;
		} else {
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
		//create player tags
		$tagArray = explode(",", $tags);
		if($type == 'Goal')
		{
			$types = array("G", "A1", "A2");
		}
		else
		{
			$types = array("$subtype");
		}

		$sqlPlayerTagsValues = "";
		foreach($tagArray as $tag)
		{
			if(count($types) > 0)
			{
				$type = array_shift($types);
			}
			else
			{
				$type = "";
			}
			$sqlPlayerTagsValues .= "($highlightId, $tag, '$type'),";
		}
		$sqlPlayerTagsValues = RTRIM($sqlPlayerTagsValues, ",");
		$sqlInsert="INSERT INTO playerTags(`HighlightId`,`PlayerNum`,`Type`)
		VALUES $sqlPlayerTagsValues ON DUPLICATE KEY UPDATE HighlightId=HighlightId;";
		if ($conn->query($sqlInsert) === TRUE) {
			//echo "New player tags created successfully";
			//print_r($sqlInsert);
		} else {
			echo "Error: " . $sqlInsert . "<br>" . $conn->error;
		}
	}
}
else
{
	$team="";
	$name="Beerbears on Ice vs ";
	$startTime="2019-08-03T22:00";
	$surface="";
	$auth="";
}
//select all games
$gameTeam = "Beerbears on Ice";
$gameTeamEscaped = str_replace("'", "''", $gameTeam);
$sql = "SELECT * FROM games where HomeTeam='$gameTeamEscaped' or AwayTeam='$gameTeamEscaped' order by StartTime desc";
$result = $conn->query($sql);
$gameList = "";
if ($result->num_rows > 0) {
	// loop over each result (game)
	while($row = $result->fetch_assoc()) {
		$boldVarStart="";
		$boldVarEnd="";
		$title = substr($row["StartTime"],0,10) . " - " . $row["Name"];
		if($gameId == 0)
		{
			$gameId=$row["GameId"];
		}
		if($gameId == $row["GameId"])
		{
			$gameRow = $row;
			$gameTitle = $title;
			$boldVarStart="<b>";
			$boldVarEnd="</b>";
		}
		$gameList .= "<h5>$boldVarStart<a href='index.php?gameId=" . $row["GameId"] . "'>$title</a>$boldVarEnd</h5>";
	}
}
//select highlights for game
$sql = "SELECT * FROM highlights where GameId='$gameId' order by StartTime asc";
$result = $conn->query($sql);
$highlightsTable = "<table class='table text-left'><thead><tr><th scope='col'>Time</th><th scope='col'>Type</th><th scope='col'>Name</th><th scope='col'>Video</th></tr></thead><tbody>";
if ($result->num_rows > 0) {
	// loop over each result (highlight)
	while($row = $result->fetch_assoc())
	{
		$highlightId = $row["HighlightId"];
		$gameId = $row["GameId"];
		$highlightName = $row["Name"];
		$highlightType = $row["Type"];
		$highlightSubType = $row["SubType"];
		$startTime = $row["StartTime"];
		$endTime = $startTime+30;
		$playerTagsHTML = "<ul class='list-group'>";
		$goalTags = "";

		//SELECT Player Tags
		$sql = "SELECT pt.PlayerTagId, pt.Type, p.* FROM playerTags pt
				join highlights h on pt.highlightid = h.highlightid
				left join players p on p.Number=pt.PlayerNum and p.Team=h.Team and p.GameId=h.GameId
				where h.highlightid='$highlightId'
				order by h.startTime asc, pt.PlayerTagId asc";
		$resultTags = $conn->query($sql);
		//print_r($sql);
		if ($resultTags->num_rows > 0)
		{
			// loop over each result (highlight)
			while($tagRow = $resultTags->fetch_assoc())
			{
				$tagType = $tagRow["Type"];
				if($tagType <> "")
				{
					$tagType .= " - ";
				}
				$playerTagsHTML .= "<li class='list-group-item list-group-item-success'>$tagType<a href='player.php?playerId=" . $tagRow["PlayerId"] . "'>#". $tagRow["Number"] . " ". $tagRow["Name"] . "</a>";
				if($tagRow["Pos"] <> "")
				{
					$playerTagsHTML .= " [" . $tagRow["Pos"] . "]";
				}
				$playerTagsHTML .= "</li>";
				$goalTags .= $tagRow["Number"] . ",";
			}
			$goalTags = RTRIM($goalTags, ",");
		}
		$playerTagsHTML .= "</ul>";
		$nameCol = "$highlightName<br><small>$playerTagsHTML</small>";
		$videoEmbed = "";
		if($row["Verified"] == 0)
		{
			$videoEmbed = "<a href='#player' onclick='verifyHighlight($highlightId, $gameId, \"$highlightName\", $startTime, $endTime, \"$highlightType\", \"$highlightSubType\", \"$goalTags\")' class=''>Verify Highlight</a>";
		}
		else
		{
			$videoEmbed = "<a href='#player' onclick='verifyHighlight($highlightId, $gameId, \"$highlightName\", $startTime, $endTime, \"$highlightType\", \"$highlightSubType\", \"$goalTags\")' class=''>Update Highlight</a>";
			if($row["YouTubeLink"] == "")
			{
				$videoEmbed .= "<br><a type='button' class='btn btn-success btn-sm' href='./index.php?gameId=$gameId&highlightId=$highlightId&action=upload'>Send to YouTube</button><br>";
			}
			elseif($row["YouTubeLink"] == "PENDING")
			{
				$videoEmbed .= "<br>YouTube upload pending...<br>";
			}
			else
			{
				$videoEmbed = "<iframe width='640' height='360' id='h$highlightId' src='https://www.youtube.com/embed/" . $row["YouTubeLink"] . "' frameborder='0' allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>";
			}
		}

		$highlightsTable .= "<tr><th scope='row'><h5><a href='#' onclick='seek($startTime);' class=''>" . gmdate("H:i:s", $startTime) . "</a></h5></th><td>" . $row["Type"] . "</td><td>" . $nameCol . "</td><td>" . $videoEmbed . "</td></tr>";
		if($highlightId == $selectedHighlightId)
		{
			$onload = "verifyHighlight($highlightId, $gameId, \"$highlightName\", $startTime, $endTime, \"$highlightType\", \"$highlightSubType\", \"$goalTags\")";
		}
	}
	$highlightsTable .= "</tbody></table>";
}

?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="icon" type="image/ico" href="./favicon.ico">
    <title>Hockey Videos</title>
  </head>
  <body onload='<?php echo $onload; ?>'>
	<table width="100%" class="text-center">
	<tr>
	<th class="text-center">
		<h1 class="text-center"><?php echo $gameTeam;?></h1>
		<div id="accordionGamesList">
		  <div class="card">
			<div class="card-header" id="headingTwo">
			  <h5 class="mb-0">
				<button class="btn btn-link" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
				  <h4>Games</h4>
				</button>
			  </h5>
			</div>
			<div id="collapseTwo" class="collapse show text-left" aria-labelledby="headingTwo" data-parent="#accordionGamesList" width='50%'>
			  <div class="card-body container">
				 <div class="row">
				  <div class="col-2">
						&nbsp;
				  </div>
				  <div class="col-8 text-left">
					<?php echo $gameList;?>
				  </div>
				 </div>
			  </div>
			</div>
		</div>
		<h2 class="text-center"><?php echo $gameTitle;?></h2>
		<!-- 1. The <iframe> (and video player) will replace this <div> tag. -->
		<div id="player"></div>
		<br>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(-300)">-300</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(-60)">-60</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(-30)">-30</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(-15)">-15</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(-5)">-5</button>
		<button type="button" class="btn btn-info btn-sm" onclick="pausePlay()">0</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(5)">+5</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(15)">+15</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(30)">+30</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(60)">+60</button>
		<button type="button" class="btn btn-info btn-sm" onclick="jump(300)">+300</button>
		<br><br>
		<form action="index.php?gameId=<?php echo $gameId; ?>" method="POST">
		  <div class="form-group row">
			<div class="col-sm-1"></div>
			<div class="col-sm-2">
			  <input type="text" class="form-control" id="start" name="start" placeholder="Highlight Start (seconds)" value="">
			  <input type="hidden" class="form-control" id="startPre" name="startPre" value="">
			</div>
			<div class="col-sm-2 text-right">
			  <button type="button" class="btn btn-secondary" onclick="setStart()">Set Start [s]</button>
			  <button type="button" class="btn btn-secondary" onclick="seekStart()">Jump To [d]</button>
			</div>
			<div class="col-sm-2 text-center">
				<button hidden="hidden" type="submit" value="update" name="update", id="update" class="btn btn-success">Update Highlight [h]</button>
				<button type="submit" value="new" name="new", id="new" class="btn btn-primary">New Highlight</button>
			</div>
			<div class="col-sm-2 text-left">
			  <button type="button" class="btn btn-secondary" onclick="setEnd()">Set End [e]</button>
			  <button type="button" class="btn btn-secondary" onclick="seekEnd()">Jump To [r]</button>
			</div>
			<div class="col-sm-2">
			  <input type="text" class="form-control" id="end" name="end" placeholder="Highlight End (seconds)" value="">
			</div>
		  </div>
		  <div class="form-group row">
			<div class="col-sm-1"></div>
			<div class="col-sm-2">
				<div class="form-group">
					<label for="type">Type</label>
					<select id="type" name="type" class="form-control">
						<option>Goal</option>
						<option>Scoring Chance</option>
						<option>Dangles</option>
						<option>Penalty</option>
						<option>Goal Against</option>
						<option>Screw  Up (Learning Opportunity)</option>
						<option>Other</option>
					</select>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group">
					<label for="subtype">SubType</label>
					<input type="text" class="form-control" id="subtype" name="subtype">
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group">
					<label for="name">Tags</label>
					<input type="text" class="form-control" id="tags" name="tags" aria-describedby="tagsHelp" placeholder="6,29,47">
					<small id="tagsHelp" class="form-text text-muted">Comma separated #'s. G,A1,A2</small>
				</div>
			</div>
			<div class="col-sm-4">
				<label for="hName">Name</label>
				<input type="text" class="form-control" id="hName" name="hName" value="">
			</div>
		  </div>
		</form>

		<h3 class="text-center">Highlights</h3>
		<?php echo $highlightsTable ?>
	</tr></table><!-- /.container -->
	<script>
     //***YOUTUBE iFrame API CODE - https://developers.google.com/youtube/iframe_api_reference
	 // 2. This code loads the IFrame Player API code asynchronously.
	  var tag = document.createElement('script');

	  tag.src = "https://www.youtube.com/iframe_api";
	  var firstScriptTag = document.getElementsByTagName('script')[0];
	  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

	  // 3. This function creates an <iframe> (and YouTube player)
	  //    after the API code downloads.
	  var player;
	  function onYouTubeIframeAPIReady() {
		  <?php if($gameRow["Surface"] == "San Jose Center" || $gameRow["Surface"] == "San Jose East" )
			{?>
				var h = '1024';
				var w = '2560';
			<?php
			} else
			{ ?>
				var h = '1440';
				var w = '2560';
			<?php
			}?>
		 player = new YT.Player('player', {
		 height: h,
		  width: w,
		  videoId: <?php echo "'" . $gameRow["YouTubeLink"] . "'";?>,
		  events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		  }
		});
	  }

	  // 4. The API will call this function when the video player is ready.
	  function onPlayerReady(event) {
		//event.target.playVideo();
	  }

	  // 5. The API calls this function when the player's state changes.
	  //    The function indicates that when playing a video (state=1),
	  //    the player should play for six seconds and then stop.
	  var done = false;
	  function onPlayerStateChange(event) {

	  }
	  function pausePlay() {
		if(player.getPlayerState() == 1)
		{
			player.pauseVideo();
		}
		else
		{
			player.playVideo();
		}
	  }

	  //***Auto fill Highlight Start and End
	  function setStart(){
		  document.getElementById("start").value = Math.floor(player.getCurrentTime())
	  }

	  function setEnd(){
		  document.getElementById("end").value = Math.ceil(player.getCurrentTime())
	  }

	  function seekStart(){
		//player.seekTo(document.getElementById("start").value,true)
		seek(document.getElementById("start").value)
	  }

	  function seekEnd(value){
		seek(document.getElementById("end").value)
	  }

	  function seek(value){
		  player.seekTo(value,true)
		  window.location.hash = '#player'
		  player.playVideo();
	  }

	  function jump(seconds){
		  player.seekTo(player.getCurrentTime() + seconds,true)
	  }

	  function verifyHighlight(highlightId, gameId, hName, start, end, type, subtype, tags){
		  document.getElementById("hName").value = hName
		  document.getElementById("start").value = start
		  document.getElementById("startPre").value = start
		  document.getElementById("end").value = end
		  document.getElementById("type").value = type
		  document.getElementById("subtype").value = subtype
		  document.getElementById("tags").value = tags

		  document.getElementById("update").value = highlightId
		  document.getElementById("update").innerHTML = ("Update Highlight [h]: " + highlightId)
		  document.getElementById("new").hidden = "hidden"
		  document.getElementById("update").hidden = ""
		  seek(start)
	  }
	</script>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script>
	$(document).keypress(function(e) {
		//hotkeys except inputs (excluding start and end which should be numbers anywaye)
		if(document.activeElement.tagName != "INPUT" || document.activeElement.id == "start" || document.activeElement.id == "end")
		{
			console.log(e.which);
			if(e.which == 115) { //s
				setStart();
			}
			else if(e.which == 100) { //d
				seekStart();
			}
			else if(e.which == 101) { //e
				setEnd();
			}
			else if(e.which == 114) { //r
				seekEnd();
			}else if(e.which == 104) { //h
				document.getElementById("update").click();
			}
		}
	});
	</script>
  </body>
</html>
<?php
$conn->close();
?>