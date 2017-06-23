/**
 * 组件： 用户检索
 * 
 * 使用说明：
 * 		触发 search-user/select-user 事件
 * 		调用方需提供对应处理方法
 * 
 * eg: 
 * 		bus.$on("search-user", this.searchUser);
 * 
 */

define(function(require){
  	var Vue = require("vue");
  	var bus = require("bus");
  	var tpl = require("text!component/util/ChooseUser.html!strip");
  	Vue.config.debug = true;
  	Vue.config.devtools = true;
  	
  	return Vue.extend({
		template: tpl,
		props: ["contacts", "show"],
		data: function(){
			return {
				keyword: "",
			};
		},
		created: function() {
		},
		methods: {
			togglePop: function() {
				// 切换面板显示
				this.keyword = "";
				bus.$emit("toggle-pop");
			},
			searchUser: function() {
				// 检索用户的事件
				if (this.keyword != '') {
					bus.$emit("search-user", {"keyword": this.keyword});
				}
			},
			selectUser: function(id) {
				bus.$emit("select-user", {"id": id});
			}
		}
	});
});
