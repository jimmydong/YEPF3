define(function(require){
  	var Vue = require("vue");
  	var tpl = require("text!component/vue/museui.html!strip");
  	return Vue.extend({
		template: tpl,
		methods:{
		},
		props: [],
		data: function(){
			return {
				dialog: false
			}
		},
		components:{
		},
		methods: {
			openDialog: function(){
				console.log('openDialog');
				this.dialog = true;
			},
			closeDialog: function(){
				this.dialog = false;
			}
		}
		
	});
});
