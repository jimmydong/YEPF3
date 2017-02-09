var storage = chrome.storage.local;
var message = document.querySelector('#message');
var yLog = document.querySelector('#log');
var yURL = document.querySelector('#url');

var state = document.querySelector('#switch');
var hide = document.querySelector('#hide');
var about = document.querySelector('#about');
var notice = document.querySelector('#noticebox');


//初始化
window.addEventListener('load', LoadInitData);
//切换按钮
state.addEventListener('click', SwitchActive);
//隐藏按钮
hide.addEventListener('click', function(){
	notice.style.display='none';
});
//关于按钮
about.addEventListener('click', function(){
    message.innerHTML = 'YEPF Debug原本设计应用在FirePHP基础上，因FireBug停止维护，改进为ChromeLogger兼容模式。请先安装<a href="https://chrome.google.com/webstore/detail/chrome-logger/noaneddfkdjfnfdakjjmocngnfkfehhd">Chrome Logger</a> <font color=red>注意：Chrome头500k限制</font>';
	notice.className = 'notice';
	notice.style.display='block';
});



function SwitchActive(){

	storage.get('active', function(FirePHP) {
		if(FirePHP.active == true) {
			storage.set({'active': false}, function(){});
			state.value="开启调试功能";
	        message.innerText = '禁用FirePHP调试功能.';
			notice.className = 'notice enable';
			chrome.browserAction.setIcon({
				'path':"icon/!!.png"
			});
		}else{
			storage.set({'active': true}, function(){});
	        message.innerText = '启用FirePHP调试功能.';
			state.value="暂停调试功能";
			notice.className = 'notice disable';
			chrome.browserAction.setIcon({
				'path':"icon/24.png"
			});
		}
	});
	notice.style.display='block';
}


function LoadInitData(){

	//获取调试状态
	storage.get('active', function(FirePHP) {
		if(FirePHP.active == true) {
			state.value="暂停调试功能";
			notice.className = 'notice enable';
			chrome.browserAction.setIcon({
				'path':"icon/24.png"
			});
		}else{
			state.value="开启调试功能";
			notice.className = 'notice disable';
			chrome.browserAction.setIcon({
				'path':"icon/!!.png"
			});
		}
	});

}









