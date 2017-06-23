define(function(require){
  	var Vue = require("vue")
  	//var tpl = 'no data';
  	var tpl = require("text!component/vue/demo.html!strip");
  	console.log(tpl)
  	Vue.config.debug = true;
  	Vue.config.devtools = true;


	return Vue.extend({
		template: tpl
	});
});
