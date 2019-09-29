<?
/*
TODO:
  * find diffs between newer prod index and this
  * icon library for form buttons and video controls
  * highlight edit form should hide/show stuff based on selected type
  * secure the api
  * make forms work with/without fetch?
  * verify highlight
  * update highlight
  * DB stuff into class
  * form validation
  * DB normalization
  * video sizes
  * desktop UI
  * player page
  * new game page

*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('./includes/db.php');

$gameId = !empty($_GET['gameId']) ? $_GET['gameId'] : null;
$my_team = 'Beerbears on Ice';

// setup some vars we'll need further down the page
$games = getGames($my_team, $my_team);
$viewingGame = null;
foreach ($games as $row => $game) {
    // if no gameId was passed in, default to the first in the list
    if (is_null($gameId)) {
        $gameId = $game['GameId'];
    }

    // if this game matches the one we're interested in viewing, save it for later
    if ($gameId == $game['GameId']) {
        $viewingGame = $game;
    }

    // if we've found everything we're looking for, get outta the loop
    if (!is_null($gameId) && !is_null($viewingGame)) {
        break;
    }
}

// TODO?
if (!empty($_GET['highlightId'])) {
    $highlightId = $_GET['highlightId'];
    updateYoutubeStatus($highlightId);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['hName'])) {
        $name = $_POST['hName'];
    }

    // NEW or UPDATED Highlight
    if (!empty($_POST['start'])) {
        $start = $_POST['start'];
        if (!empty($_POST['startPre'])) {
            $startPre = $_POST['startPre'];
        } else {
            $startPre = $start;
        }

        if (!empty($_POST['end'])) {
            $end = $_POST['end'];
        }

        if (!empty($_POST['type'])) {
            $type = $_POST['type'];
        }

        if (!empty($_POST['subtype'])) {
            $subtype = $_POST['subtype'];
        } else {
            $subtype = "";
        }

        if (!empty($_POST['tags'])) {
            $tags = $_POST['tags'];
        }

        // update existing highlight
        if (!empty($_POST['update'])){
            $highlightId = $_POST['update'];
            // $sql = "UPDATE highlights set Name='" . str_replace("'", "''", $name) . "', StartTime='$start', EndTime='$end', Type='$type', SubType='$subtype', Verified=1 WHERE HighlightId=$highlightId ;";
            $diff = $start - $startPre;
            if ($diff <> 0) {
                //UPDATE unverified StartTimes by the amound of the shift
                // TODO??
                // if ($conn->query("UPDATE highlights set StartTime = StartTime + $diff where Verified=0;") === TRUE) {
                // 	//echo "<br>Unverified StartTime update successful<br>";
                // } else {
                // 	echo "Error: " . $sql . "<br>" . $conn->error;
                // }
            }

            //DELETE old player tags to be recreated with updated ones
            // if ($conn->query("DELETE from playerTags WHERE HighlightId=$highlightId;") === TRUE) {
            // 	//echo "Player tag delete successful<br>";
            // } else {
            // 	echo "Error: " . $sql . "<br>" . $conn->error;
            // }
            $result = deletePlayerTags($highlightId);
            if (!$result) {
                // do something, mang
            }
        } else {
            // insert new highlight
            $highlightId = 0;
            $result = insertHighlight($gameId, $team, $name, $start, $end, $type, $subtype);

            if (!$result) {
                // do something
            } else {
                // get new inserted id
                $highlightId = $result;
            }

            //INSERT highlight to database
            // $team = "Beerbears on Ice";
            // $sql = "INSERT INTO highlights (GameId, Team, Name, StartTime, EndTime, Type, SubType, Verified)
            // VALUES ('$gameId', '$team', '" . str_replace("'", "''", $name) . "', '$start', '$end', '$type', '$subtype', 1)";
        }

        // if ($conn->query($sql) === TRUE) {
        // 	//echo "Highlight created/updated successfully<br>";
        // 	if($highlightId==0) //if insert, not update
        // 		$highlightId = $conn->insert_id;
        // } else {
        // 	echo "Error: " . $sql . "<br>" . $conn->error;
        // }

        //create player tags
        $tagArray = explode(",", $tags);
        if ($type == 'Goal') {
            $types = array("G", "A1", "A2");
        } else {
            $types = array("$subtype");
        }

        // $sqlPlayerTagsValues = "";
        $playerTagsValues = [];
        foreach ($tagArray as $tag) {
            if (count($types) > 0) {
                $type = array_shift($types);
            } else {
                $type = "";
            }
            // $sqlPlayerTagsValues .= "($highlightId, $tag, '$type'),";

            // insert function is expecting an ordered array. duped highlightId
            // is necessary because we use it twice in the prepared statement
            $playerTagsValues[] = [$highlightId, $tag, $type, $highlightId];
        }

        $insertedPlayerTags = insertPlayerTags($playerTagsValues);
        if (!$insertedPlayerTags) {
            // do something
        }
        // $sqlPlayerTagsValues = RTRIM($sqlPlayerTagsValues, ",");
        // $sqlInsert="INSERT INTO playerTags(`HighlightId`,`PlayerNum`,`Type`)
        // VALUES $sqlPlayerTagsValues ON DUPLICATE KEY UPDATE HighlightId=HighlightId;";

        // if ($conn->query($sqlInsert) === TRUE) {
        // 	//echo "New player tags created successfully";
        // 	//print_r($sqlInsert);
        // } else {
        // 	echo "Error: " . $sqlInsert . "<br>" . $conn->error;
        // }
    }
} else {
    $team="";
    $name="Beerbears on Ice vs ";
    $startTime="2019-08-03T22:00";
    $surface="";
    $auth="";
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/css/app.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
    <title><?=$my_team?> Hockey Videos</title>
</head>

<body>
<main class="container">

    <section class="row">
        <header class="col-12">
            <h1 class="text-center"><?=$my_team?></h1>
        </header>
    </section>

    <section class="row game-viewer no-gutter">
        <header class="col-12">
            <h2 class="text-center"><?=$viewingGame['Name']?></h2>
        </header>
        <div class="playlist-container">
            <button type="button" class="backward nav-btn material-icons">arrow_left</button>
            <ul class="playlist col-12-xs">
            <?
            foreach ($games as $row => $game) {
                $date = substr($game["StartTime"], 0, 10);
                // used to highlight the game we're watching in the list
                $className = isset($viewingGame) && $viewingGame == $game ? 'active' : '';
            ?>
                <li class="playlist__item border text-break">
                    <a href="/?gameId=<?=$game["GameId"]?>" class="<?=$className?> d-block p-2">
                        <!-- <img src="https://i.ytimg.com/vi/<?=$game['YouTubeLink']?>/default.jpg" height="90" width="120" alt="" /> -->
                        <span class="date"><?=$date?></span> - <?=$game['Name']?>
                    </a>
                </li>
            <?
            }
            ?>
            </ul>
            <button type="button" class="forward nav-btn material-icons">arrow_right</button>
        </div>

        <div id="video-player" class="col-12-xs">
            <iframe id="ytplayer" type="text/html" <?/* width="854" height="480"*/?> src="https://www.youtube.com/embed/<?=$viewingGame['YouTubeLink']?>" class=""></iframe>
            <div class="video-controls text-center">
                <button type="button" class="btn btn-info btn-sm d-none d-sm-inline" data-skip="-300">-5 min</button>
                <button type="button" class="btn btn-info btn-sm d-none d-sm-inline" data-skip="-60">-1 min</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="-30">-30s</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="-15">-15s</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="-5">-5s</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="0">0</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="5">+5s</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="15">+15s</button>
                <button type="button" class="btn btn-info btn-sm" data-skip="30">+30s</button>
                <button type="button" class="btn btn-info btn-sm d-none d-sm-inline" data-skip="60">+1 min</button>
                <button type="button" class="btn btn-info btn-sm d-none d-sm-inline" data-skip="300">+5 min</button>
            </div>
        </div>
    </section>

    <section class="highlight-form">
        <!--
            TODO:
            when edit form is open, make video play within viewport instead of fullscreen to
            allow use of controls
         -->
        <form action="/?gameId=<?=$gameId;?>" method="POST" autocomplete="off">
            <header class="col-12">
                <h3 class="text-center">
                    <span class="new">New Highlight</span>
                    <span class="update" hidden>Update Highlight</span>
                    <button class="material-icons md-18" type="button" data-toggle="collapse" data-target="#edit-form" aria-expanded="true" aria-controls="edit-form">unfold_more</button>
                </h3>
            </header>
            <div id="edit-form" class="collapse show form-group form-row row">
                <input type="hidden" name="team" value="<?=$my_team?>" />
                <input type="hidden" name="gameId" value="<?=$gameId?>" />
                <input type="hidden" class="" id="startPre" name="startPre" value="">
                <? /* lets the backend know what we're doing with this highlight. new/update */ ?>
                <input type="hidden" class="new" name="mode" value="new" />
                <input type="hidden" class="update" name="mode" value="update" disabled />

                <div class="col-4">
                    <input type="number" autocomplete="off" class="form-control form-control-sm" id="start" name="start" placeholder="Start (secs)" value="" min="0" required>
                </div>

                <div class="col-4">
                    <button type="button" class="form-control btn-sm btn btn-secondary" onclick="setStart()">Set Start</button>
                </div>

                <div class="col-4">
                    <button type="button" class="form-control btn-sm btn btn-secondary" onclick="seekStart()">Go to Start</button>
                </div>

                <div class="col-4">
                    <input type="number" autocomplete="off" class="form-control form-control-sm" id="end" name="end" placeholder="End (secs)" value=""  min="10" required>
                </div>

                <div class="col-4">
                    <button type="button" class="form-control btn-sm btn btn-secondary" onclick="setEnd()">Set End</button>
                </div>

                <div class="col-4">
                    <button type="button" class="form-control btn-sm btn btn-secondary" onclick="seekEnd()">Go to End</button>
                </div>

                <div class="col-12">
                    <label for="type">Type</label>
                    <select id="type" name="type" class="form-control custom-select" required>
                        <option disabled selected value="">-- select an option --</option>
                        <?
                        foreach ($highlightTypes as $highlightType => $class) {
                        ?>
                            <option><?=$highlightType?></option>
                        <?
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 d-none" id="subtype-field">
                    <label for="subtype">SubType</label>
                    <input type="text" autocomplete="off" class="form-control" id="subtype" name="subtype" required>
                    <small class="form-text text-muted">PP, PK, penalty called, etc.</small>
                </div>

                <div class="col-12 d-none" id="tag-field">
                    <label for="tags">Tags</label>
                    <input type="text" autocomplete="off" class="form-control" id="tags" name="tags" aria-describedby="tagsHelp" placeholder="6,29,47" disabled>
                    <small id="tagsHelp" class="form-text text-muted">Comma separated player numbers. For goals, list in order: G,A1,A2</small>
                </div>

                <div class="col-12 d-none" id="goal-tag-field">
                    <label for="goal-scorer">Tags</label>
                    <input type="text" autocomplete="off" class="form-control" id="goal-scorer" name="tags" aria-describedby="tagsHelp" placeholder="Goal scorer">
                    <input type="text" autocomplete="off" class="form-control" name="tags" aria-describedby="tagsHelp" placeholder="1st assist">
                    <input type="text" autocomplete="off" class="form-control" name="tags" aria-describedby="tagsHelp" placeholder="2nd assist">
                    <small id="" class="form-text text-muted">List the goal-scorer and 1st and 2nd assists</small>
                </div>

                <div class="col-12 d-none" id="name-field">
                    <label for="hName">Name</label>
                    <input type="text" autocomplete="off" class="form-control" id="hName" name="hName" value="" required>
                    <small class="form-text text-muted">Name of the highlight, e.g. "Dummy fell down from bench"</small>
                </div>

                <div class="col-12">
                    <button hidden="hidden" type="submit" value="update" name="update", id="update" class="form-control btn-sm btn btn-success edit">Update Highlight</button>
                    <button type="submit" value="new" name="new" id="new" class="form-control btn-sm btn btn-primary new">Save</button>
                </div>
            </div>

        </form>
    </section>

    <section class="highlights mb-5">
        <h3 class="text-center">Highlights</h3>
        <div class="row">
            <select id="highlight-filter" name="highlightFilter" class="form-control custom-select">
                <option value="" selected>-- Filter highlights --</option>
                <?
                foreach ($highlightTypes as $highlightType => $class) {
                ?>
                    <option value="<?=$highlightType?>" disabled><?=$highlightType?></option>
                <?
                }
                ?>
            </select>
        </div>
        <div class="row">
        <table class="table highlight-table table-sm">
            <thead class="thead-light">
                <tr>
                    <th scope="col" class="pr-1">Type</th>
                    <th scope="col" class="pl-0 pr-0">Time</th>
                    <th scope="col" class="pr-0">Players</th>
                    <!-- <th scope="col">Video</th> -->
                </tr>
            </thead>
            <tbody class="thead-light">
            <?
            $highlights = getHighlights($gameId);
            foreach ($highlights as $highlight) {
                $id = $highlight['HighlightId'];
                $tags = getPlayerTags($id);
                include('./stubs/_highlight-row.php');
            }
            ?>
            </tbody>
        </table>
        </div>
    </section>
</main>
    <script>
    <? /*
    sets some JS vars used by the youtube script
    TODO: remove this?
    */ ?>
    (function(window) {
        'use strict';
        window.videoId = '<?=$viewingGame['YouTubeLink']?>';
        window.videoHeight = 720;
        window.videoWidth = 1200;
    })(window);
    </script>
    <script src="/assets/js/youtube.js"></script>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <!-- <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script> -->

    <script src="/assets/js/jquery-3.3.1.slim.min.js"></script>
    <script src="/assets/js/popper.min.js"></script>
    <script src="/assets/js/bootstrap.min.js"></script>
  </body>
</html>
<?php
$conn->close();
?>