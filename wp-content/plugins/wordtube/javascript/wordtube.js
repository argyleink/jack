	function thisMovie(movieName) {
		if(navigator.appName.indexOf("Microsoft") != -1) {
			return window[movieName];
		} else {
			return document[movieName];
		}
	};

	function loadRecommendation(rID, rAuthor, rTitle, rFile, rImage) {
                 pid = rID;
		loadFile(rID, {author:rAuthor,title:rTitle, file:rFile, image:rImage});
		setTimeout("thisMovie(pid).sendEvent('playitem', 0)", 500);
	};

	function loadFile(swf, obj) {	
		thisMovie(swf).loadFile(obj);
		thisMovie(swf).sendEvent('playitem',0);
	};
