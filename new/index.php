<?
require('./../includes/db.php');

function calcStartTime($period, $time, $diffMin)
{
	//calculate the estimated video start time for highlight based on period and game clock
	//game start time vs video start in seconds
	$startTime=$diffMin * 60; 
	//add time for periods (first period will add 0)
	$startTime += ($period - 1) * 22 * 60; //20 minutes for period and 2 minutes for intermision and stoppage time.
	//add game time
	sscanf($time, "%d:%d", $minutes, $seconds);
	if($seconds=="") //last minute of the period is different format. 36.1 
	{
		$seconds = $minutes;
		$minutes = 0;
	}
	//echo "M=$minutes S=$seconds <br>\n";
	$startTime += (20*60 - ($minutes * 60 + $seconds)); //invert the game clock to be seconds of video. 19:47 becomes 13 seconds.
	return $startTime;
}

if(!empty($_POST['auth'])) //NEW Game
{
	$auth = $_POST['auth'];		
}
else
{
	$auth = "";
}
		
if(!empty($_POST['gameId'])) 
{
	$gameId = $_POST['gameId'];
	// Create database connection
	$servername = "DBSERVER";
	$username = "DBNAME";
	$password = "DBPASS";
	$dbname = "DBNAME";
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	// get DOM from URL or file
	include('./simple_html_dom.php');
	$html = file_get_html("https://stats.sharksice.timetoscore.com/oss-scoresheet?game_id=$gameId&mode=display");
	//$html = file_get_html('oss-scoresheet.htm');

	//GAME INFO
	$gameTable = $html->find('table', 1); 
	//<tbody><tr><td>Date:08-06-19</td><td>Time:08:00 PM</td><td></td></tr><tr><td>League:SIAHL@SJ</td></tr><tr><td>Level:Adult Division 6A</td></tr><tr><td>Location:San Jose North</td></tr></tbody>
	$gameDate = ltrim($gameTable->find('td',0)->innertext, "Date:"); //Trim "Date:" from left, leaving only gameDate.
	//08-06-19 
	$gameTime = ltrim($gameTable->find('td',1)->innertext, "Time:");
	//08:00 PM
	$gameSurace = ltrim($gameTable->find('td',5)->innertext, "Location:");
	//San Jose North

	$gameStartString = "$gameDate $gameTime";
	$gameStartDateTime = date_create_from_format("m-d-y h:i a", $gameStartString);
	print_r($gameStartDateTime);

	echo "<br>\n";
	$videoStartDateTime = clone $gameStartDateTime;
	//gets the remander of the minutes part divided by 30. This will let us find the video start - round down to nearest 30 minutes. 12:45 becomes 12:30.
	$diffMin = $gameStartDateTime->format('i') % 30;
	$videoStartDateTime = date_sub($videoStartDateTime,date_interval_create_from_date_string("$diffMin minutes"));

	echo $diffMin . "<br>\n";
	print_r($videoStartDateTime);
	$gameStartTimeSQL = $videoStartDateTime->format("y-m-d H:i");

	//PLAYERS, SCORING, PENALTIES - identify by header instead of table #
	$t=0;
	$playersTableFlag = false; //the way the players tables are embedded causes the same content to be interpreted as different playerTables. Only take the first one containing players on both teams.
	$playersTableIdentifier = " Players in ";
	$sqlInsertHighlightValues = "";
	$sqlPlayerTagsValues = "";
	$teamNum=0;
	$teams = array();
	foreach($html->find('table') as $table)
	{
		if($table->find('th',0)) //if table contains a header column (Scoring and Penalty do)
		{
			$tableHeader = $table->find('th',0)->innertext;
			//PLAYERS
			if(strpos($tableHeader, $playersTableIdentifier) !== false && $playersTableFlag == false)
			{
				$playersTableFlag=true;
				$sqlInsertValues="";
				echo "<hr>\nPlayers:<br>\n";
				foreach($table->find('table') as $playersTable)
				{
					if($playersTable->find('th',0))
					{
						$playersTableHeader = $playersTable->find('th',0)->innertext;
						if(strpos($playersTableHeader, $playersTableIdentifier) !== false) //identifies the player table of each team (filters out formatting tables)
						{
							//print_r($playersTable->innertext);
							echo "<br>\n";
							$teamName = TRIM(explode($playersTableIdentifier, $playersTableHeader)[0]); //split the string at the identifier and capture the left part, trimming white space.
							if(!in_array($teamName, $teams))
							{
								echo "<br>\nTABLE:-$teamName-<br>\n";
								$teams[] = $teamName; //add teamName to teams array
							}
							$r=0;
							foreach($playersTable->find('tr') as $player)
							{
								if($r>=3) //skip header rows
								{
									echo "<br>\nPlayer:";
									//print_r($player->innertext);
									$p1Num = $player->find('td', 0)->innertext;
									if($p1Num == "")
										$p1Num = 0;
									$p1Pos = $player->find('td', 1)->innertext;
									$p1Name = $player->find('td', 2)->innertext;
									
									if($sqlInsertValues<>"") //add comma except for first rows
									{
										$sqlInsertValues .= ", ";
									}
									$sqlInsertValues .= "($gameId, $p1Num, '" . str_replace("'", "''", $p1Name) . "', '" . str_replace("'", "''", $teamName) . "', '$p1Pos')";
									
									$p2Num = $player->find('td', 3)->innertext;
									if($p2Num == "")
										$p2Num = 0;
									//if p2# is not empty, grab the player in the 2nd column
									if($p2Num <> "&nbsp;")
									{
										$p2Pos = $player->find('td', 4)->innertext;
										$p2Name = $player->find('td', 5)->innertext;
										$sqlInsertValues .= ", ($gameId, $p2Num, '" . str_replace("'", "''", $p2Name) . "', '" . str_replace("'", "''", $teamName) . "', '$p2Pos')";
									}
									//print_r($penalty->innertext);
									echo "$p1Num|$p1Pos|$p1Name|$p2Num|$p2Pos|$p2Name";
									echo "<br>\n";
								}
								$r++;
							}
							//print_r($playersTable->innertext);
						}
					}
				}
				
				$sqlInsert="INSERT INTO players (GameId, Number, Name, Team, Pos) Values $sqlInsertValues ON DUPLICATE KEY UPDATE Number=Number;";
				if ($conn->query($sqlInsert) === TRUE) {
					echo "New record created successfully";
				} else {
					echo "Error: " . $sqlInsert . "<br>" . $conn->error;
				}
			}
			//GOALS
			elseif($tableHeader == "Scoring") //first header column is the "Scoring" header
			{
				echo "<hr>\nGoals:<br>\n";
				$r=0;
				$goalNum=0;
				$team = $teams[$teamNum];
				foreach($table->find('tr') as $goal)
				{
					//<td align="center">1</td><td align="center">19:39 </td><td align="center">&nbsp;</td><td align="center">21</td><td align="center">&nbsp;</td><td align="center">&nbsp;</td>
					if($r>=3) //skip header rows
					{
						$goalNum++; 
						$goalPeriod = $goal->find('td', 0)->innertext;
						$goalTime = $goal->find('td', 1)->innertext;
						$goalType = str_replace('&nbsp;', '', $goal->find('td', 2)->innertext);
						$gNum = $goal->find('td', 3)->innertext;
						$a1Num = str_replace('&nbsp;', '', $goal->find('td', 4)->innertext);
						$a2Num = str_replace('&nbsp;', '', $goal->find('td', 5)->innertext);
						echo "$goalPeriod|$goalTime|$goalType|$gNum|$a1Num|$a2Num";
						
						//Highlight name
						$hName = "$goalNum - $team Goal ";
						if($goalType <> "")
						{
							$hName .= "($goalType)";
						}
						$hName .= "#$gNum P$goalPeriod $goalTime";
						
						$startTime = calcStartTime($goalPeriod, $goalTime, $diffMin);
										
						$sqlInsert="INSERT INTO highlights(`GameId`,`Team`,`Name`,`Type`,`SubType`,`StartTime`,`Period`,`GameTime`,`GoalNum`, `Verified`)
						VALUES ($gameId,'" . str_replace("'", "''", $team) . "' , '" . str_replace("'", "''", $hName) . "','Goal','$goalType','$startTime',$goalPeriod,'$goalTime', $goalNum, 0) 
						ON DUPLICATE KEY UPDATE GameId=GameId;";
						if ($conn->query($sqlInsert) === TRUE) {
							$highlightId = $conn->insert_id;
							echo "New record created successfully";
						} else {
							echo "Error: " . $sqlInsert . "<br>" . $conn->error;
						}
						
						$sqlPlayerTagsValues = "($highlightId, $gNum, 'G')";
						if($a1Num <> "")
						{
							$sqlPlayerTagsValues .= ", ($highlightId,'$a1Num', 'A1')";
						}
						if($a2Num <> "")
						{
							$sqlPlayerTagsValues .= ", ($highlightId,'$a2Num', 'A2')";
						}

						$sqlInsert="INSERT INTO playerTags(`HighlightId`,`PlayerNum`,`Type`)
						VALUES $sqlPlayerTagsValues ON DUPLICATE KEY UPDATE HighlightId=HighlightId;";
						if ($conn->query($sqlInsert) === TRUE) {
							echo "New record created successfully";
						} else {
							echo "Error: " . $sqlInsert . "<br>" . $conn->error;
						}

						//print_r($goal->innertext);
						echo "<br>\n";
					}
					$r++;
				}
				$teamNum++;
			}
			//PENALTIES
			elseif($tableHeader == "Penalties") //first header column is the "Scoring" header
			{
				$r=0;
				echo "<hr>\nPentalties:<br>\n";
				foreach($table->find('tr') as $penalty)
				{
					//<td align="center">2</td><td align="center">14</td><td align="center">Hooking</td><td align="center">2</td><td align="center">15:27 </td><td align="center">14:56 </td><td align="center">11:56 </td><td align="center">11:56 </td>
					if($r>=2) //skip header rows
					{
						$penaltyPeriod = $penalty->find('td', 0)->innertext;
						$penaltyNum = $penalty->find('td', 1)->innertext;
						$penaltyType = $penalty->find('td', 2)->innertext;
						$penaltyMin = $penalty->find('td', 3)->innertext;
						$penaltyTime = $penalty->find('td', 4)->innertext;
						echo "$penaltyPeriod|$penaltyNum|$penaltyType|$penaltyMin|$penaltyTime";
						
						//Highlight name
						$hName = "Penalty - $team #$penaltyNum $penaltyType ($penaltyMin min) P$penaltyPeriod $penaltyTime";
						
						//calculate the estimated video start time for highlight based on period and game clock
						$startTime = calcStartTime($penaltyPeriod, $penaltyTime, $diffMin);
										
						$sqlInsert="INSERT INTO highlights(`GameId`,`Team`,`Name`,`Type`,`SubType`,`StartTime`,`Period`,`GameTime`,`GoalNum`, `Verified`)
						VALUES ($gameId,'" . str_replace("'", "''", $team) . "' , '" . str_replace("'", "''", $hName) . "','Penalty','$penaltyType','$startTime',$penaltyPeriod,'$penaltyTime', 0, 0) 
						ON DUPLICATE KEY UPDATE GameId=GameId;";
						if ($conn->query($sqlInsert) === TRUE) {
							$highlightId = $conn->insert_id;
							echo "New penalty highlight created successfully";
						} else {
							echo "Error: " . $sqlInsert . "<br>" . $conn->error;
						}

						$sqlInsert="INSERT INTO playerTags(`HighlightId`,`PlayerNum`,`Type`)
						VALUES ($highlightId,$penaltyNum, '$penaltyType') ON DUPLICATE KEY UPDATE HighlightId=HighlightId;";
						if ($conn->query($sqlInsert) === TRUE) {
							echo "New penalty tag created successfully";
						} else {
							echo "Error: " . $sqlInsert . "<br>" . $conn->error;
						}
						
						
						//print_r($penalty->innertext);
						echo "<br>\n";
					}
					$r++;
				}
			}
		}
		$t++;
	}

	//INSERT game to database 
	$homeTeam = $teams[1];
	$awayTeam = $teams[0];
	$name = "$homeTeam vs $awayTeam - $gameTime"; 
	$sql = "INSERT INTO games (GameId, HomeTeam, AwayTeam, Name, StartTime, Surface, Auth)
	VALUES ('$gameId', '$homeTeam', '$awayTeam', '" . str_replace("'", "''", $name) . "', '$gameStartTimeSQL', '$gameSurace', '$auth')
	ON DUPLICATE KEY UPDATE GameId=GameId;";
	if ($conn->query($sql) === TRUE) {
		echo "New game created successfully<br>";
	} else {
		echo "Error: " . $sql . "<br>" . $conn->error;
	}
	print_r($sql);
	$conn->close();
}
else
{?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Hockey Videos - New Game</title>
  </head> 
  <body>
	<table width="100%" class="text-center">
	<tr>
	<th class="text-center">
		<h1 class="text-center">New Game</h1>
		<div class="row">
		  <div class="col-2">
				&nbsp;
		  </div>
		  <div class="col-8 text-left">

			<form action="newGame.php" method="POST">
			  <div class="form-group">
				<label for="name">Game Id</label>
				<input type="text" class="form-control" id="gameId" name="gameId" value="">
				<small id="authHelp" class="form-text text-muted">From TimeToScore URL: https://stats.sharksice.timetoscore.com/generate-scorecard.php?game_id=213857</small>
			  </div>
			  <div class="form-group">
				<label for="auth">Auth Token</label>
				<input type="text" class="form-control" id="auth" name="auth" aria-describedby="authHelp" value="<?php echo $auth;?>">
				<small id="authHelp" class="form-text text-muted">F12 - Application token.access_token</small>
			  </div> 
			  
			  <button type="submit" class="btn btn-primary">New Game</button>
			</form>
		  </div>
		</div>
	</th></tr></table>
</div>
	
<?php
}?>

