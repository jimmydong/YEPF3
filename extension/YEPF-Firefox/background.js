"use strict";

var storage = browser.storage.local;
var USE_FIREPHP = false;

//获取调试数据
storage.get('active', function(FirePHP) {
	if(FirePHP.active == true) {
		browser.browserAction.setIcon({
			'path':"icon/24.png"
		});
		USE_FIREPHP = true;
	}else{
		browser.browserAction.setIcon({
			'path':"icon/!!.png"
		});
		USE_FIREPHP = false;
	}
});
if(browser.tabs.onUpdated) {
	browser.tabs.onUpdated.addListener(checkTab);
};
function checkTab(tabId, change, tab){
	var isChromePath = tab.url.match("chrome://");
	var isChromeExt = tab.url.match("chrome-extension://");
	var isFilePath = tab.url.match("file://");
	var isAboutPage = tab.url.match("about:");
	var isAbove = isChromePath || isChromeExt || isFilePath || isAboutPage;
	if(isAbove){
		browser.browserAction.setIcon({
			'path':"icon/24.png"
		});
	}
}

function rewriteUserAgentHeader(e) {
	storage.get('active', function(FirePHP) {USE_FIREPHP = FirePHP.active;});
	
	if (USE_FIREPHP == false) {
		chrome.browserAction.setIcon({
			'path':"icon/!!.png"
		});
	}else{
		chrome.browserAction.setIcon({
			'path':"icon/!.png"
		});
		var flag = false;
		for (var header of e.requestHeaders) {
			if (header.name.toLowerCase() === "x-yepf") {
			  header.value = "Firefox-3.0";
			  flag = true;
			}
		}
		if(! flag) e.requestHeaders.push({name:"X-YEPF",value:"Firefox-3.0"})
	}
	return {requestHeaders: e.requestHeaders};
}

browser.webRequest.onBeforeSendHeaders.addListener(rewriteUserAgentHeader,
                                          {urls: ["http://*/*","https://*/*"]},
                                          ["blocking", "requestHeaders"]);
