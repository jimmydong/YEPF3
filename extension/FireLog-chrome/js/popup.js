var storage = chrome.storage.local;

var vm = new Vue({
	el: "#app",
	data: {
		state: false,
		background:null
	},
	created: function () {
		// 初始化时，更新状态
		var _self = this;
		this.background = chrome.extension.getBackgroundPage();
		storage.get('active', function(state) {
			console.log('debug created');
			_self.state = state.active?true:false;
			_self.background.update(_self.state);
		});
	},
	computed: {
		btnMsg: function () {
			return this.state?'关闭调试':'开启调试';
		}
	},
	methods: {
		switchState: function () {
			this.state = !this.state;
			storage.set({'active': this.state}, function () {});
			this.background.update(this.state);
		}
	}
});