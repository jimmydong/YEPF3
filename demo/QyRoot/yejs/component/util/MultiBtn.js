define(function(require){
  	var Vue = require("vue");
  	var bus = require("bus");
	var $ = require("jquery");
  	var tpl = require("text!component/util/MultiBtn.html!strip");
  	Vue.config.debug = true;
  	Vue.config.devtools = true;
  	
  	return Vue.extend({
		template: tpl,
		props: ["categorys"],
		data: function(){
			return {
				first: 0,
				second: 0,
				third: 0,

				active:'cate-active',
				values:[],
				valueNames:new Array(),
				step:1,
				id:0,
				name:'',
				dialog:false,
				cate_money:'',
				money:''
			}
		},
		computed: {
			showSecond: function() {
				// 因为Vue会默认给对象添加一个属性，这里就判断>1了
				if (this.first == 0) {
					return false;
				}
				console.log('sec', Object.getOwnPropertyNames(this.categorys[this.first]['child']).length);
				if (Array.isArray(this.categorys[this.first]['child'])) {
					return false;
				} else {
					return Object.getOwnPropertyNames(this.categorys[this.first]['child']).length > 1?true:false;
				}
			
			},
			secondList: function() {
				if (!this.showSecond) {
					return {};
				}
				return this.categorys[this.first]['child'];
			},
			showThird: function() {
				if (!this.showSecond || this.second == 0) {
					return false;
				}
				if (Array.isArray(this.secondList[this.second]['child'])) {
					return false;
				} else {
					return Object.getOwnPropertyNames(this.secondList[this.second]['child']).length > 1?true:false;
				}
			},
			thirdList: function() {
				if (!this.showThird) {
					return {};
				}
				return this.secondList[this.second]['child'];
			}
		},
		created: function() {
		},
		methods: {
			cate1:function (id,name) {
				this.first 	= id;
				this.step 	= 1;
				this.name 	= name;
				this.second = this.third = 0;
				if($.inArray(id,this.values) == -1){
					if(this.categorys[id]['child'].length <= 1){
						//清空分类信息 只能保存一大类,不能同时保存多个1级分类
						this.valueNames = new Array();
						this.values	 	= [];
						this.cate_money = '';
						this.refreshMoney();
						this.dialog = true;
					}else{
						this.dialog = false;
					}
				}
				this.onSelectChange();
			},
			cate2:function (id,name,_this) {
				$(_this).addClass('cate-active');
				this.second = id;
				this.third 	= 0;
				this.step 	= 2;
				this.name 	= name;
				if($.inArray(id,this.values) == -1){
					if(this.secondList[id]['child'].length <= 1){
						this.cate_money = '';
						this.dialog = true;
					}else{
						this.dialog = false;
					}
				}
				this.onSelectChange();
			},
			cate3:function (id,name) {
				this.third 	= id;
				this.step 	= 3;
				this.name 	= name;
				if($.inArray(id,this.values) == -1){
					if(this.thirdList[id]['child'].length <= 1) {
						this.cate_money = '';
						this.dialog = true;
					}else{
						this.dialog = false;
					}
				}
				this.onSelectChange();
			},
			onSelectChange: function() {
				if (this.first > 0) {
					bus.$emit('select-category', {
						first: this.first,
						second: this.second,
						third: this.third,
						values:this.values,
						valueNames:this.valueNames,
						money:this.money
					});
				}
			},
			handleClose: function(id) {
				var splice = $.inArray(id,this.values);
				if(splice > -1){
					this.values.splice(splice,1);
					var newArr = new Array();
					$.each(this.valueNames,function (i,item) {
						console.log(item);
						if(item.id != id){
							newArr.push(item);
						}
					})
					this.valueNames = newArr;
				}
				this.refreshMoney();
			},
			close:function () {
				this.dialog = false;
				this.cate_money = '';
			},
			saveData:function (id) {
				var moneyPreg = /^[0-9]*(\.[0-9]{1,2})?$/;
				if(!moneyPreg.test(this.cate_money)){
					alert('金额格式不正确');
					return false;
				};
				this.values.push(id);
				var arr = new Object();
				arr['id'] 	= id;
				arr['name'] = this.name;
				arr['money']= this.cate_money;
				arr['top_id']= this.first;
				this.valueNames.push(arr);
				this.dialog = false;
				this.refreshMoney();
				console.log(this.money)
			},
			saveMoney:function () {
				switch(this.step){
					case 1:
						this.saveData(this.first);
						break;
					case 2:
						this.saveData(this.second);
						break;
					case 3:
						this.saveData(this.third);
						break;
				}
			},
			refreshMoney:function(){
				var money = 0;
				$.each(this.valueNames,function (i,item) {
					money += parseFloat(item.money);
				})
				this.money = money.toFixed(2);
				this.onSelectChange();
			}
		},
		components: {
		}
	});
});
