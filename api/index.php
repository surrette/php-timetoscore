<?
// $type = 'blurt';
// $subtype = 'PPG';
// $tagArray = [21,47,29];
// $highlightId = 1;
// if ($type == 'Goal') {
//     $types = array("G", "A1", "A2");
// } else {
//     $types = array("$subtype");
// }
// echo '<pre>';
// print_r($types);
// echo '<br>';
// $playerTagsValues = [];
// foreach ($tagArray as $tag) {
//     if (count($types) > 0) {
//         $type = array_shift($types);
//     } else {
//         $type = "";
//     }
//     print_r($type);
//     echo '<br>';
//     // insert function is expecting an ordered array. duped highlightId
//     // is necessary because we use it twice in the prepared statement
//     $playerTagsValues[] = [$highlightId, $tag, $type, $highlightId];
// }
// die(print_r($playerTagsValues));

require('../includes/db.php');

// this is the JSON that'll be returned
$response = [
    'status' => 'ok'
];

function insertNewHighlight($gameId, $team, $hName, $start, $end, $type, $subtype) {
    $id = insertHighlight($gameId, $team, $hName, $start, $end, $type, $subtype);
    $result = getHighlight($id);
    return $result;
}

function updateExistingHighlight() {}

function insertNewPlayerTags($tags, $type, $subtype, $highlightId) {
    $tagArray = explode(",", $tags);
    if ($type == 'Goal') {
        $types = array("G", "A1", "A2");
    } else {
        $types = array("$subtype");
    }

    $playerTagsValues = [];
    foreach ($tagArray as $tag) {
        if (count($types) > 0) {
            $type = array_shift($types);
        } else {
            $type = "";
        }

        // insert function is expecting an ordered array. duped highlightId
        // is necessary because we use it twice in the prepared statement
        $playerTagsValues[] = [$highlightId, $tag, $type, $highlightId];
    }

    $result = insertPlayerTags($playerTagsValues);
    if ($result) {
        $result = getPlayerTags($highlightId);
    }
    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // extract all the form fields into individual variables
    extract($_POST);
    $mode = isset($mode) ? $mode : '';

    switch ($mode) {
        // this is a new highlight
        case 'new':
            $response = insertNewHighlight($gameId, $team, $hName, $start, $end, $type, $subtype);
            // make sure highlight inserted successfully
            // TODO: handle failure
            if (!$response) {
                break;
            }

            // now insert player tags
            if (isset($tags)) {
                $tags = insertNewPlayerTags($tags, $type, $subtype, $response['HighlightId']);
                $response['tags'] = $tags;
            }

            // need these vars set for the included response stub
            $highlight = $response;
            $id = $highlight['HighlightId'];

            // reads in the template and subs in all our variables
            ob_start();
            include("../stubs/_highlight-row.php");

            // gets the contents of the template
            $markup = ob_get_clean();

            $response['markup'] = $markup;
            break;
        // updating an existing highlight
        case 'update':
            break;
    }

}
/**
 * TODO:
 *  look for xhr header and encode response, otherwise use PRG to go back to index with gameId=??
 */
?>
<?=json_encode($response)?>