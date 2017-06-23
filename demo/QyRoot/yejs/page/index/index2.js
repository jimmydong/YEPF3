define(function(require){
  	var Vue = require("vue")
  	var tpl = require("text!page/index/index2.html!strip");
  	var tpl_sso = require("text!page/index/sso.html!strip");
  	Vue.config.debug = true;
  	Vue.config.devtools = true;
  	vheader = require("component/vue/header");
  	vmuseui = require("component/vue/museui");
  	
  	const Component = Vue.extend({
  		template: tpl,
  		components: {
  			vheader: vheader,
  			vmuseui: vmuseui
  		}
  	});

  	const routes = {
  		'tpl' : { template: tpl},
  		'sso' : { template: tpl_sso}
  	}

  	return Vue.extend({
		props: ['data'],
		data: function(){
			return {tpl:"tpl"}
		},
		computed:{
			getTpl: function(){
				console.log(tpl);
				return tpl;
			}
		},
		//template: sso,
		components: {
			vheader: vheader,
			vmuseui: vmuseui
		},
		methods: {
			handleClick: function(){
				console.log(this.tpl);
				this.tpl = 'sso';
			}
		},
		render: function(h){
			return h(Component);
		}
	});
});
