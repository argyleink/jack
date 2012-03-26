<?php
/*
extended wordtube_statistics 2.0
author : Frederic de Ranter, Alex Rabe
*/
header('Content-Type: text/javascript; charset=utf-8', true);
?>
var player = new Array();
//var counter = 0;
function playerReady(obj) {
	var id = obj['id'];
	var version = obj['version'];
	var client = obj['client'];
	//console.log('the videoplayer '+id+' has been instantiated');
	player[id] = document.getElementById(id);
	addListeners(id);
};

function addListeners(id) {
	if (player[id]) { 
		player[id].addModelListener("STATE", "stateListener");
	} else {
		setTimeout("addListeners()",100);
	}
}

function stateListener(obj) { 
	//possible states IDLE, BUFFERING, PLAYING, PAUSED, COMPLETED
	currentState = obj.newstate; 
	previousState = obj.oldstate;
	//console.log('current state : '+ currentState + ' previous state : '+ previousState );
    
	//find out what title is playing (or id of the file)
	var cfg = player[obj.id].getConfig();
	var plst = player[obj.id].getPlaylist();

	//decide if the counter needs updating and then 
	//update in the db with ajax request
	var decision = false;
	if (((currentState == "PLAYING") && ( (previousState == "BUFFERING") ||(previousState == "COMPLETED")))) {
		decision = true;
	}
	
	if(decision) {
		var ajaxString = "file=" + escape( plst[cfg["item"]].file );
		jQuery.ajax({
			type: "POST",
			data: ajaxString,
			url: "<?php echo get_option ('siteurl') . '/index.php?wt-stat=true'; ?>"
		});	
	}
}