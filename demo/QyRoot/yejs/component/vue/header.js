define(function(require){
  	var Vue = require("vue");
  	var tpl = require("text!component/vue/header.html!strip");
  	return Vue.extend({
		template: tpl,
		methods:{
		},
		props: ['data'],
		data: function(){
			return {
				pageTitle: ('string' == typeof this.data.pageTitle)?this.data.pageTitle:'宜生CMS',
				menuList: this.data.menuList
			};
		},
		components:{
		}
	});
});
