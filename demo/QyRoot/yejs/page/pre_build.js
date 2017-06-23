/**
 * 准备编译辅助文件
 * 
 * 参数格式： node pre_build.js [_c] [_a] [force]
 * 
 * eg: 待编译文件为 demo.js
 * 
 * 1, _demo_profile.js :  以 ../config.js 为蓝本
 * 2，_demo.js : 以模板 ../common.html 为蓝本  
 * 
 * 【注意】模板中必须有//###BuildMarkBegin###标记
 */
const fs = require('fs');
var profile = '../config.js';
var templatefile = '../../../_TEMPLATE/qy/common.html';

function log(v){
	console.log(v);
}
var args = process.argv.splice(2)

var _profile = './' + args[0] + '/_' + args[1] + '_profile.js';
var _jsfile = 'page/' + args[0] + '/_' + args[1];
var _buildfile = '_' + args[1] + '_build.js';
if(fs.existsSync(_profile) && args[2]==undefined){
	log('skip ' + _profile);
}else{
	content = fs.readFileSync(profile, 'utf-8');
	res = content.match(/\/\/###BuildMarkBegin###([\s\S]*)\/\/###BuildMarkEnd###/);
	fs.writeFile('/tmp/build_temp.js', 'module.exports = ' + res[1] , 'utf-8', function(){
		log('write to temp');
		info = require('/tmp/build_temp.js');
		info['baseUrl'] = "../../";
		info['name'] = _jsfile;
		info['out'] = _buildfile
		fs.writeFile(_profile, '(' + JSON.stringify(info) + ')', 'utf-8', function(){
			log('write to ' + _profile);
		})
	});
}

var _mainfile =  './' + args[0] + '/_' + args[1] + '.js';
if(fs.existsSync(_mainfile) && args[2]==undefined){
	log('skip ' + _profile);
}else{
	content = fs.readFileSync(templatefile, 'utf-8');
	content = content.replace('"vue", page, "museui"', '"vue", "page/'+args[0]+'/'+args[1]+'", "museui"')
	res = content.match(/\/\/###BuildMarkBegin###([\s\S]*)\/\/###BuildMarkEnd###/);
	fs.writeFile(_mainfile, res[1] , 'utf-8', function(){
		log('write to ' + _mainfile);
	});
}