define(function(require){
  	var Vue = require("vue");

  	var path = "text!" + pageUrl + ".html!strip";
  	var tpl = require("text!component/util/BottomNavbar.html!strip");
  	Vue.config.debug = true;
  	Vue.config.devtools = true;

  	return Vue.extend({
		template: tpl,
        props: ['selected'],
		data: function() {
			return {
				tab: {
					items: [
                      {
                          id: 'project',
                          link: '/?_c=demo&_a=full',
                          name: '项目'
                      },
                      {
                          id: 'regulater',
                          link: '/?_c=demo&_a=regulater',
                          name: '技师'
                      },
                      {
                          id: 'order',
                          link: '/?_c=demo&_a=order',
                          name: '订单'
                      },
                      {
                          id: 'my',
                          link: '/?_c=demo&_a=my',
                          name: '我的'
                      }
                  ]
				},
			}
		}
  	});
 });