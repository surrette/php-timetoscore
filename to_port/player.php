<?php 

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);




if(!empty($_GET['gameId'])) 

{

	$gameId = $_GET['gameId'];

}

else

{

	$gameId=0;

}



if(!empty($_GET['playerId'])) 

{

	$playerId = $_GET['playerId'];

	//select player info

	$result = $conn->query("select Number, Name, Team from players where PlayerId='$playerId';");

	if ($result->num_rows > 0) {

		// loop over each result (game)

		while($row = $result->fetch_assoc()) {

			$Number = $row["Number"];

			$Name = $row["Name"];

			$Team = $row["Team"];

		}

	}

	

	//select highlights for game

	$sql = "select h.HighlightId, h.GameId, h.Name, pt.Type, h.SubType, h.StartTime, h.Verified, h.YouTubeLink, g.Name as GameName from highlights h

	join games g on h.GameId=g.GameId

	join playerTags pt on h.HighlightId=pt.HighlightId

	left join players p on h.GameId=p.GameId and pt.PlayerNum=p.Number and h.Team=p.Team

	where pt.PlayerNum=$Number and h.Team='" . str_replace("'", "''", $Team) . "' and p.Name='" . str_replace("'", "''", $Name) . "' 

	order by g.StartTime desc;";

	$result = $conn->query($sql);

	//print_r($sql);



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

					$playerTagsHTML .= "<li class='list-group-item list-group-item-success'>$tagType#". $tagRow["Number"] . " ". $tagRow["Name"];

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

				$videoEmbed = "<a href='index.php?gameId=$gameId&highlightId=$highlightId' class=''>Verify Highlight</a>";

			}

			else

			{

				$videoEmbed = "<a href='index.php?gameId=$gameId&highlightId=$highlightId' class=''>Update Highlight</a>";

				if($row["YouTubeLink"] == "")

				{

					$videoEmbed .= "<br><a type='button' class='btn btn-success btn-sm' href='./index.php?gameId=$gameId&highlightId=$highlightId'>Send to YouTube</button><br>";

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

			

			$highlightsTable .= "<tr><th scope='row'><h5><a href='index.php?gameId=$gameId&highlightId=$highlightId' class=''>" . $row["GameName"] . "<br>" . gmdate("H:i:s", $startTime) . "</a></h5></th><td>" . $row["Type"] . "</td><td>" . $nameCol . "</td><td>" . $videoEmbed . "</td></tr>";

		}

		$highlightsTable .= "</tbody></table>";

	}

}

else

{

	$playerId=0;

	$Team="Beerbears on Ice";

	$Name = "";

	$Number = "";

	$highlightsTable = "";

}



if ($conn->query("INSERT INTO activity (PlayerId, IP) VALUES ('$playerId', '" . $_SERVER['REMOTE_ADDR']. "')") === TRUE) {

	//echo "<br>Highlight update successful<br>";

} else {

	echo "Error activity insert: " . $sql . "<br>" . $conn->error;

}



//get all players for dropdown

$result = $conn->query("select Number, Name, Team, MIN(PlayerId) as PlayerId from players where Team='Beerbears on Ice' group by Number, Name, Team ORDER BY Number;");

if ($result->num_rows > 0) {

	// loop over each result (game)

	$selectOptionsHTML = "";

	while($row = $result->fetch_assoc()) {

		$rowNumber = $row["Number"];

		$rowName = $row["Name"];

		$rowTeam = $row["Team"];

		$rowPlayerId = $row["PlayerId"];

		$playerDisplay = "#$rowNumber - $rowName";

		$selectedVar = "";

		if($Number == $rowNumber && $Team == $rowTeam && $Name == $rowName )

		{

			$selectedVar = "selected";

		}

		$selectOptionsHTML .= "<option $selectedVar value='$rowPlayerId'>$playerDisplay</option>";

	}

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

  <body>

	<table width="100%" class="text-center">

	<tr>

	<th class="text-center">

		<h1 class="text-center"><a href="./index.php"><?php echo $Team;?></a></h1>

		<h2 class="text-center">

			<select id="playerId" onchange="window.location.href='player.php?playerId=' + this.value;">

				<?php echo $selectOptionsHTML; ?>

			</select>

		</h2>	  

		<?php echo $highlightsTable; ?>

	</th>

	</tr></table><!-- /.container -->

	<script>

	</script>



    <!-- Optional JavaScript -->

    <!-- jQuery first, then Popper.js, then Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

  </body>

</html>

<?php 

$conn->close();

?>