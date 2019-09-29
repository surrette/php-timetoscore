(function() {
'use strict';

//***YOUTUBE iFrame API CODE - https://developers.google.com/youtube/iframe_api_reference
// 2. This code loads the IFrame Player API code asynchronously.
var player;
var done = false;
// gets the time
var controls = document.querySelectorAll('.video-controls, .highlights');
controls.forEach( control => { control.addEventListener('click', jump); });

// 3. This function creates an <iframe> (and YouTube player)
//    after the API code downloads.

function onYouTubeIframeAPIReady() {
    // player = new YT.Player('video-player', {
    //     height: window.videoHeight,
    //     width: window.videoWidth,
    //     videoId: window.videoId,
    //     events: {
    //         'onReady': onPlayerReady,
    //         'onStateChange': onPlayerStateChange
    //     }
    // });
}

// 4. The API will call this function when the video player is ready.
function onPlayerReady(event) {
    //event.target.playVideo();
}

// 5. The API calls this function when the player's state changes.
//    The function indicates that when playing a video (state=1),
//    the player should play for six seconds and then stop.
function onPlayerStateChange(event) {}

function pausePlay() {
    if (player.getPlayerState() == 1) {
        player.pauseVideo();
    } else {
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
    window.location.hash = '#video-player'
    player.playVideo();
}

function jump(evt) {
    var target = evt.target;
    var seconds = target.dataset.skip;
    if (seconds) {
        seconds = parseInt(seconds, 10);
        if (seconds === 0) {
            pausePlay();
        } else {
            player.seekTo(player.getCurrentTime() + seconds, true);
        }
    }
}

function verifyHighlight(highlightId, hName, start, end, type, subtype, tags){
    document.getElementById("hName").value = hName
    document.getElementById("start").value = start
    document.getElementById("startPre").value = start
    document.getElementById("end").value = end
    document.getElementById("type").value = type
    document.getElementById("subtype").value = subtype
    document.getElementById("tags").value = tags
    document.getElementById("update").value = highlightId
    document.getElementById("update").innerHTML = ("Update Highlight: " + highlightId)
    document.getElementById("new").hidden = "hidden"
    document.getElementById("update").hidden = ""
    $('#collapseHighlight').collapse('show')
    seek(start)
}

window.addEventListener('DOMContentLoaded', (event) => {
    // TODO: gonna use this?
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    // document.body.appendChild(tag);

    // the highlight form is disabled until all the youtube crap finishes loading
    // TODO: remove this?
    document.querySelectorAll('fieldset[disabled]').forEach(el => {
        el.removeAttribute('disabled');
    });


    // this is for scrolling the playlist to the correct item
    // TODO: will need to add scrollTop stuff for a vertical list
    // this gets the anchor
    var viewing = document.querySelector('.active');
    if (viewing) {
        // now get its container
        viewing = viewing.parentNode;
        viewing.parentNode.scrollLeft = viewing.offsetLeft;
    }
});


/**
 * handles new/updated highlights via api. will receive a json response and
 * update the UI
 */
document.querySelector('form').addEventListener('submit', (evt) => {
    evt.preventDefault();
    // get form data
    // post to api
    // parse response
    // update UI

    var form = new FormData(evt.target);
    window.req = fetch('/api', {
        method: 'POST',
        body: form
    })
        .then(response => response.json())
        .then(response => {
            updateTable(response.markup);
            // reset the form to its initial state
            evt.target.reset();
            return response;
        });

    function updateTable(markup) {
        var tbody = document.querySelector('.highlight-table tbody');
        tbody.innerHTML = markup + tbody.innerHTML;
    }
});

var filter = document.querySelector('#highlight-filter');
var highlights = document.querySelectorAll('[data-highlight-type]');
// enable the valid filter options
Array.from(filter.options).forEach(option => {
    if (document.querySelector(`[data-highlight-type="${option.value}"]`)) {
        option.disabled = false;
    }
});

// listen for changes to the select and only show those highlights
filter.addEventListener('change', (evt) => {
    var target = evt.target;
    var value = target.value;

    highlights.forEach(highlight => {
        var type = highlight.dataset.highlightType;
        highlight.hidden = value === '' ? false : type !== value;
    });
});
})();
