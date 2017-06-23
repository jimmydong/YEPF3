define([], function(){
	function buildURL(options) {
		var prefix = "/?";
		var args = [];
		for (var x in options) {
			args.push(x + "=" + options[x]);
		}
		return prefix + args.join('&');
	}

	return {
		buildURL: buildURL
	}
});