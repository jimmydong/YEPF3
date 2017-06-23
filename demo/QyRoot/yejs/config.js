/**
 * YEJS 配置文件
 */
requirejs.config(
//注意：以下为自动编译标识，请勿删除
//###BuildMarkBegin###
{
	baseUrl: "./yejs",
    paths:{
        bus: "./bus",
        jquery:"./lib/jquery.min",
        vue:"./lib/vue2",
        lodash : "./lib/lodash.min",
        text: "./lib/text",
        vuerouter: "./lib/vue-router",
        mintui: "./lib/mint-ui",
        museui: "./lib/muse-ui",
        ysvue: './lib/ys-vue', // YS-VUE的自定义插件
        // ys: "./lib/ys", // 自定义功能函数的模块
        components: "./component"
    },
    packages: [
    ]
}
//###BuildMarkEnd###
);

/* ---------------------------- 公用函数 ------------------------------ */
(function( w ){
	var loadJS = function( src, cb ){
		"use strict";
		var ref = w.document.getElementsByTagName( "script" )[ 0 ];
		var script = w.document.createElement( "script" );
		script.src = src;
		script.async = true;
		ref.parentNode.insertBefore( script, ref );
		if (cb && typeof(cb) === "function") {
			script.onload = cb;
		}
		return script;
	};
	// commonjs
	if( typeof module !== "undefined" ){
		module.exports = loadJS;
	}
	else {
		w.loadJS = loadJS;
	}
}( typeof global !== "undefined" ? global : this ));
