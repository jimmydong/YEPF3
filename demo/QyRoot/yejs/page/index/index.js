define(function(require){
  	var Vue = require("vue")
  	var tpl = require("text!page/index/index.html!strip");
  	Vue.config.debug = true;
  	Vue.config.devtools = true;
  	vheader = require("component/vue/header");
  	vmuseui = require("component/vue/museui");
  	
  	return Vue.extend({
		template: tpl,
		props: ['data'],
		data: function(){
			return {}
		},
		components: {
			vheader: vheader,
			vmuseui: vmuseui
		},
		methods: {
			handleClick: function(){
				data.content = "<h1>HTML test OK</h1>";
				this.$toast('hello world!');
			}
		}
	});
});
