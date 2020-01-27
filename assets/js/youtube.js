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
    // var tag = document.createElement('script');
    // tag.src = "https://www.youtube.com/iframe_api";
    // document.body.appendChild(tag);

    // this is for scrolling the playlist to the correct item
    // TODO: will need to add scrollTop stuff for a vertical list
    var viewing = document.querySelector('.active'); // this gets the anchor
    if (viewing) {
        viewing = viewing.parentNode; // now get its container
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

// fills in some dynamic values in the rest of the highlight form
document.getElementById('type').addEventListener('change', (evt) => {
    const el = evt.target;
    const value = el.value.toLowerCase();
    // const label = document.getElementById('subtype-label');
    // label.innerText = `Type of ${value}`;
    const hiddenRows = el.form.querySelectorAll('.form-row[hidden]')
    hiddenRows.forEach((row) => {
        row.hidden = false;
    });
    const datalist = document.getElementById('subtype-values');
    datalist.innerHTML = '';
    let newList = document.getElementById(`${value}-subtypes`);
    if (newList) {
        datalist.innerHTML = newList.innerHTML;
    }


    // enable the submit buttons
    document.getElementById('new').disabled = false;
    document.getElementById('update').disabled = false;
});

// handler for adding player tags to a highlight
const template = document.querySelector('.add-template');
const templateParent = template.parentNode;
const GOAL_SCORER = 'Goal Scorer';
const PRIMARY = 'Primary assist';
const SECONDARY = 'Secondary Assist';
document.getElementById('player-tags').addEventListener('click', (evt) => {
    const target = evt.target;
    if (target.type !== 'button') {
        return;
    }
    const method = target.dataset.method;
    // add a new tag
    if (method === '+') {
        const clone = template.cloneNode(true);
        const input = clone.querySelector('input');
        clone.classList.add('live')
        templateParent.insertBefore(clone, template);
        clone.hidden = false;
        input.focus();
    }
    // delete a tag
    else {
        const row = target.closest('.row');
        row.parentNode.removeChild(row);
    }

    const type = document.getElementById('type');
    // if it's a goal, let's get all the inputs and make the placeholder text more helpful
    if (type.value === 'Goal') {
        const tags = templateParent.querySelectorAll('.live input');
        tags.forEach((tag, index) => {
            switch (index) {
                case 0:
                    tag.placeholder = GOAL_SCORER;
                    break;
                case 1:
                    tag.placeholder = PRIMARY;
                    break;
                case 2:
                    tag.placeholder = SECONDARY;
                    break;
                default:
                    break;
            }
        });
    }
});
})();
