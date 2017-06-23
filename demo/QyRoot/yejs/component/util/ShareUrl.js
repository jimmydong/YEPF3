/**
 * 组件： 检索用户进行分享
 * 
 * 使用说明：
 * 
 *  html:
  	<shareUrl :url="url"></shareUrl>
 *  js:
  	var shareUrl = require("components/util/ShareUrl");
  	return Vue.extend({
		template: tpl,
		props: ['data'],
		data: function(){
			return {url: window.location.href}  //设定分享的地址
		},
		components: {
			shareUrl: shareUrl
		}
	});
 * 	
 * 
 */

define(function(require){
  	var Vue = require("vue");
  	var bus = require("bus");
  	var $ = require("jquery");
  	var tpl = require("text!component/util/ShareUrl.html!strip");
  	var ChooseUser = require("components/util/ChooseUser");
  	Vue.config.debug = true;
  	Vue.config.devtools = true;
  	
  	return Vue.extend({
		template: tpl,
		props: ['url'],
		data: function(){
			return {
				showPop: false,
				contacts: []
			}
		},
		created: function() {
			// 绑定面板关闭事件
			bus.$on("toggle-pop", function(){this.showPop = !this.showPop}.bind(this));
			// 检索用户
			bus.$on("search-user", this.searchUser);
			// 选中用户
			bus.$on("select-user", this.selectUser);
		},
		methods: {
			doShare: function(){
				$.post('/?_c=util&_a=getAdminList',{},function(ret){
					if(ret.success){
						this.contacts = ret.data;
						this.showPop = true;
					}
				}.bind(this),'json');
			},
			searchUser: function(params) {
				var keyword = params["keyword"];
				$.post('/?_c=util&_a=getAdminList',{keyword,keyword},function(ret){
					if(ret.success){
						this.contacts = ret.data;
					}
				}.bind(this),'json');
			},
			selectUser: function(params) {
				var id = params["id"];
				$.post('/?_c=util&_a=shareUrl',{id:id,url:this.url},function(ret){
					if(ret.success){
						alert('已经发送完成');
						this.showPop = false;
					}
				}.bind(this),'json');
			}
		},
		components: {
			"chosse-user": ChooseUser
		} 
	});
});
