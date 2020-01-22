<tr id="highlight-<?=$id?>" data-highlight-type="<?=$highlight['Type']?>">
    <th scope="row" class="pr-1"><?=$highlight['Type']?></th>
    <td class="pl-0 pr-0"><button class="btn btn-link btn-sm" data-seek="<?=$highlight['StartTime']?>"><?=gmdate("H:i:s", $highlight['StartTime'])?></button></td>
    <td class="pr-0">
    <span class="highlight-name"><?=$highlight['Name']?></span>
    <? if ($tags) { ?>
        <ul class="list-group list-group-flush">
            <? foreach ($tags as $tag) { ?>
            <? $tagType = $tag['Type'] ? $tag['Type'] . ' - ' : ''; ?>
                <li class="p-2 list-group-item list-group-item-<?=$highlightTypes[$highlight['Type']]?>">
                    <span class="tag-type"><?=$tagType?></span>
                    <a href="/player/?playerId=<?=$tag['PlayerId']?>" class="player">#<?=$tag['Number']?> <?=$tag['Name']?></a>
                </li>
            <? } ?>
        </ul>
    <? } ?>

    <? if ($highlight["Verified"] == 1 && $highlight["YouTubeLink"] <> 'PENDING') { ?>
        <!-- <img src="https://i.ytimg.com/vi/<?=$highlight['YouTubeLink']?>/hqdefault.jpg" /> -->
        <!-- <iframe width="640" height="360" src="https://www.youtube.com/embed/<?=$highlight['YouTubeLink']?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> -->
    <? } else { ?>
        <? if ($highlight["YouTubeLink"] == "PENDING") { ?>
            YouTube upload pending...
        <? } else { ?>
            <a class="btn btn-success btn-sm" href="/?gameId=<?=$highlight['GameId']?>&highlightId=<?=$highlight['HighlightId']?>">Send to YouTube</a>
        <? } ?>
        <a href="#player" onclick="verifyHighlight()" class="">Verify Highlight</a>
        <a href="#player" onclick="verifyHighlight()" class="">Update Highlight</a>
    <? } ?>
    </td>
</tr>

<?
/*
TODO:
    1: for mobile, remove one column and combine player and video info
    2: for larger, move video into its own column
<td>
    <? if ($highlight["Verified"] == 1 && $highlight["YouTubeLink"] <> 'PENDING') { ?>
        <!-- <img src="https://i.ytimg.com/vi/<?=$highlight['YouTubeLink']?>/hqdefault.jpg" /> -->
        <!-- <iframe width="640" height="360" src="https://www.youtube.com/embed/<?=$highlight['YouTubeLink']?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> -->
    <? } else { ?>
        <? if ($highlight["YouTubeLink"] == "PENDING") { ?>
            YouTube upload pending...
        <? } else { ?>
            <a type="button" class="btn btn-success btn-sm" href="/?gameId=<?=$highlight['GameId']?>&highlightId=<?=$highlight['highlightId']?>">Send to YouTube</a>
        <? } ?>
        <a href="#player" onclick="verifyHighlight()" class="">Verify Highlight</a>
        <a href="#player" onclick="verifyHighlight()" class="">Update Highlight</a>
    <? } ?>
    </td>
    */
    ?>