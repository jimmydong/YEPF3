/**
 * author: JC.
 * date: 2017.03.16
 * desc: URL跳转.
 * Vue.$vdirect({_c:"hello",_a:"world"});
 */
define(['vue'], function(Vue){
	var ysvue = {};
	ysvue.install = function (Vue, options) {
		if (ysvue.installed) {
			return;
		}

		// 不叫redirect是因为这个名字太普通了，避免日后和其他的插件混淆
		Vue.prototype.$vdirect = function(urlArgs) {
			var prefix = '/?';
			var args = [];
			for(var x in urlArgs) {
				args.push(x + "=" + urlArgs[x]);
			}
			window.location.href = prefix + args.join("&");
		}

		// localStorage处理方法
		Vue.prototype.$vstorage = {
			init: function() {
				if (!window.localStorage) {
					// 不支持这玩意
					return false;
				}
				return true;
			},
			setData: function(key, value, append) {
				if (!this.init()) return false;
				// 如果key存在，且append为true，则追加key的数据
				if (append) {
					var data = window.localStorage.getItem(key);
					data = JSON.parse(data);
					if (!data) {
						data = {};
					}
					for(var x in value) {
						data[x] = value[x];
					}
					window.localStorage.setItem(key, JSON.stringify(data));
				} else {
					window.localStorage.setItem(key, JSON.stringify(value));
				}

				return true;
			},
			getData: function(key) {
				if (!this.init()) return false;
				var data = window.localStorage.getItem(key);
				return JSON.parse(data)?JSON.parse(data):{};
			},
			removeData: function(key) {
				if (!this.init()) return false;
				window.localStorage.removeItem(key);
				return true;
			},
			clearData: function() {
				if (!this.init()) return false;
				window.localStorage.clear();
				return true;	
			}
		}
	}

	return ysvue;
});