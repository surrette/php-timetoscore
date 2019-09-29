<?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
TODO validation:
    1: end is later than start
    2: both start and end exist
    3: check for same timestamp?
    4: times are integer only
    5:
*/

// Create database connection
$servername = '127.0.0.1';
$username = 'dbuser';
$password = 'dbpass';
$dbname = 'hockeydb';
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn = new mysqli($servername, $username, $password, $dbname);

// used to map highlight types to a theme color for easy and consistent output
$highlightTypes = [
    'Goal' => 'success',
    'Scoring Chance' => 'info',
    'Dangles' => 'primary',
    'Penalty' => 'danger',
    'Goal Against' => 'danger',
    'Screw Up (Learning Opportunity)' => 'dark',
    'Other' => 'light'
];

function getGames($home, $away) {
    global $pdo;
    $statement = $pdo->prepare('SELECT *
                                FROM games
                                WHERE HomeTeam=:home_team OR AwayTeam=:away_team
                                ORDER BY StartTime DESC');
    $statement->bindValue(':home_team', $home, PDO::PARAM_STR);
    $statement->bindValue(':away_team', $away, PDO::PARAM_STR);
    $statement->execute();
    $games = $statement->fetchAll(PDO::FETCH_ASSOC);
    return $games;
}

function getHighlights($id) {
    global $pdo;

    $statement = $pdo->prepare('SELECT *
                                FROM highlights
                                WHERE GameId=:id
                                ORDER BY StartTime ASC');
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $highlights = $statement->fetchAll(PDO::FETCH_ASSOC);
    return $highlights;
}

function getHighlight($id) {
    global $pdo;

    $statement = $pdo->prepare('SELECT *
                                FROM highlights
                                WHERE HighlightId=:id');
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $highlight = $statement->fetch(PDO::FETCH_ASSOC);
    return $highlight;
}

function updateYoutubeStatus($id) {
    global $pdo;

    $statement = $pdo->prepare('UPDATE highlights
                                SET YouTubeLink="PENDING"
                                WHERE HighlightId=:id');
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $update = $statement->execute();
    return $update;
}


function getPlayerTags($id) {
    global $pdo;

    $statement = $pdo->prepare('SELECT pt.PlayerTagId, pt.Type, p.*
                                FROM playerTags pt
                                JOIN highlights h ON pt.HighlightId = h.HighlightId
                                LEFT JOIN players p ON p.Number=pt.PlayerNum AND p.Team=h.Team AND p.GameId=h.GameId
                                WHERE h.HighlightId=:id
                                ORDER BY h.startTime ASC, pt.PlayerTagId ASC');
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $tags = $statement->fetchAll(PDO::FETCH_ASSOC);
    return $tags;
}

function updateHighlight($id, $name, $start, $end, $type, $subtype) {
    global $pdo;

    $statement = $pdo->prepare('UPDATE highlights
                                SET Name=:name, StartTime=:start, EndTime=:end, Type=:type, SubType=:subtype, Verified=1
                                WHERE HighlightId=:id');
    $statement->bindValue(':name', $name, PDO::PARAM_STR);
    $statement->bindValue(':start', $start, PDO::PARAM_INT);
    $statement->bindValue(':end', $end, PDO::PARAM_INT);
    $statement->bindValue(':type', $type, PDO::PARAM_STR);
    $statement->bindValue(':subtype', $subtype, PDO::PARAM_STR);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $update = $statement->execute();
    return $update;
}

function deletePlayerTags($id) {
    global $pdo;

    $statement = $pdo->prepare('DELETE from playerTags
                                WHERE HighlightId=:id');
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $delete = $statement->execute();
    return $delete;
}

function insertHighlight($id, $team, $name, $start, $end, $type, $subtype) {
    global $pdo;

    $statement = $pdo->prepare('INSERT INTO highlights (GameId, Team, Name, StartTime, EndTime, Type, SubType, Verified)
                                VALUES (:id, :team, :name, :start, :end, :type, :subtype, 1)');
    $statement->bindValue(':team', $team, PDO::PARAM_STR);
    $statement->bindValue(':name', $name, PDO::PARAM_STR);
    $statement->bindValue(':start', $start, PDO::PARAM_INT);
    $statement->bindValue(':end', $end, PDO::PARAM_INT);
    $statement->bindValue(':type', $type, PDO::PARAM_STR);
    $statement->bindValue(':subtype', $subtype, PDO::PARAM_STR);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $highlight = $statement->execute();

    // if the insert was successful, return the new ID, otherwise the boolean
    return $highlight ? $pdo->lastInsertId() : $highlight;
}

function insertPlayerTags($values) {
    global $pdo;

    $statement = $pdo->prepare('INSERT INTO playerTags (HighlightId, PlayerNum, Type)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE HighlightId=?');
    // start transaction
    $pdo->beginTransaction();

    foreach($values as &$row) {
        $statement->execute($row);
    }

    // end transaction
    $result = $pdo->commit();

    return $result;
}
?>
