
var storage = chrome.storage.local;
var USE_FIREPHP = false;

//获取调试数据
storage.get('active', function(FirePHP) {
	if(FirePHP.active == true) {
		chrome.browserAction.setIcon({
			'path':"icon/24.png"
		});
	}else{
		chrome.browserAction.setIcon({
			'path':"icon/!!.png"
		});
	}
});

//程序载入
if(chrome.tabs.onUpdated) {
	chrome.tabs.onUpdated.addListener(checkTab);
	chrome.webRequest.onBeforeSendHeaders.addListener(function(details){
	  	var url = details.url;
		var headers = details.requestHeaders;
		console.log("start request url:" + url);
		var blockingResponse = modifyHeader(headers, url);
		return blockingResponse;
	},{
		urls : ["http://*/*", "https://*/*"]
	},["requestHeaders", "blocking"]
	);
};
function checkTab(tabId, change, tab){
	var isChromePath = tab.url.match("chrome://");
	var isChromeExt = tab.url.match("chrome-extension://");
	var isFilePath = tab.url.match("file://");
	var isAboutPage = tab.url.match("about:");
	var isAbove = isChromePath || isChromeExt || isFilePath || isAboutPage;
	if(isAbove){
		chrome.browserAction.setIcon({
			'path':"icon/24.png"
		});
	}
}
function modifyHeader(_headers, _url){
	var blockingResponse = {};
	
	//判断是否启动调试
	storage.get('active', function(FirePHP) {USE_FIREPHP = FirePHP.active;});
	
	if (USE_FIREPHP == false) {
		chrome.browserAction.setIcon({
			'path':"icon/!!.png"
		});
	}else{
		chrome.browserAction.setIcon({
			'path':"icon/!.png"
		});
		var x_flag = false;
		for (var j = 0; j < _headers.length; j++){
			if(_headers[j].name == "X-YEPF"){
				_headers[j].value = "Chrome-3.0";
				x_flag = true;
				break;
			}
		}
		if(! x_flag)_headers.push({name:"X-YEPF",value:"Chrome-3.0"});
	}
	blockingResponse.requestHeaders = _headers;
	return blockingResponse;
}

