function showRequest(request) {
    document.write(JSON.stringify(request));
}

function renderJSON(data) {
    var json_regex = /^\s*([\[\{].*[\}\]])\s*$/;
    var is_json = json_regex.test(data);
    if (is_json) {
        var jsonFormatter = new JSONFormatter();
        var jsonHTML = jsonFormatter.jsonToHTML(data, null, 'JSONViewer');
        return {'isJson':true, 'data':jsonHTML};
    }
    return {'isJson':false,'data':data};
}

// 响应头数据解析
var ConsoleLog = {
    exception: function(meta, body) {
        return {
            "title": body['Message'],
            'type': 'exception',
            "table": [
                [
                    {'type': 'string', 'val': 'Line', 'omit': false}, 
                    {'type': 'string', 'val': 'File', 'omit': false},
                    {'type': 'string', 'val': 'Trace', 'omit': false}
                ],
                [
                    {'type': 'number', 'val': meta['Line'], 'omit': false}, 
                    {'type': 'string', 'val': meta['File'], 'omit': false},
                    {'type': 'object', 'val': body['Trace'], 'omit': true}
                ]
            ]
        };
        
    },
    table: function(meta, body) {
        var table = body[1];
        // 下面是将每个输出item转化为格式化的数据，方便打印
        for (var i in table) {
            for (var j in table[i]) {
                if (table[i][j] !== null) {
                    if (Array.isArray(table[i][j])) {
                        var type = 'array';
                    } else {
                        var type = typeof(table[i][j]);
                    }
                    table[i][j] = {
                        type: type,
                        val: table[i][j],
                        omit: false
                    };
                    if (JSON.stringify(table[i][j]['val']).length > 200) {
                        table[i][j]['omit'] = true;
                    }
                } else {
                    table[i][j] = {
                        type: "null",
                        val: table[i][j],
                        omit: false
                    }
                }
            }
        }
        // // document.write(JSON.stringify(table));
        return {
            "title": body[0],
            'type': 'table',
            "table": table
        };
    }
};

var bus = new Vue();

/**
Vue components
*/
var Modal = Vue.extend({
    template: '#modal',
    props: ['data', 'show', 'isJson', 'json'],
    data: function() {
        return {
            showTxt: false
        }
    },
    created: function() {
        this.showTxt = this.isJson;
    },
    watch: {
        data: function(newVal, oldVal) {
            if (newVal) {
                document.querySelector(".modal .body").innerHTML = newVal;
                if (this.isJson) {
                    prettyJSON();
                }
            }
        }
    },
    methods: {
        closeModal: function() {
            bus.$emit('close-modal');
        },
        copy: function() {
            this.showTxt = !this.showTxt;
            if (this.showTxt) {
                document.querySelector(".modal .body").innerHTML = JSON.stringify(this.json);
            } else {
                document.querySelector(".modal .body").innerHTML = this.data;
                prettyJSON();
            }
        },
    }
});

var vm = new Vue({
    el: '#app',
    data: {
        requests: [], // 请求
        debug: '', // 调试日志
        pause: false, // Tab刷新时是否阻止清空

        jsonHTML: '',
        // 弹层的HTML内容
        isJson: false,
        json:"",
        // 弹层展示的数据是否是JSON
        modalHide: true, // 是否隐藏弹层
        showTxt: false,
    },
    created: function() {
        bus.$on('close-modal', this.closeModal);
        bus.$on('tab-update', this.tabUpdate);
        bus.$on('add-request', this.addRequest);
        bus.$on('disconnect', this.disconnect);
        bus.$on('request-debug', this.debugLog);
        /*
        bus.$emit({"url": "test", "headers": 
[
    {
        "name":"X-Wf-1-1-1-1", 
        "value": "107|[{\"Type\":\"TABLE\",\"File\":\"\",\"Line\":\"\"},[\"This Page Spend Times 0.073174\",[[\"Description\",\"Time\",\"Caller\"]]]]|\"", 
    }
], "connect": true
});*/
    },
    methods: {
        // 清空
        trash: function() {
            this.requests = [];
        },
        // 更新标签页
        tabUpdate: function() {
            this.debug += 'tab 更新了';
            if (!this.pause) {
                this.requests = [];
            }
        },
        // 切换清空状态
        switchPause: function() {
            this.pause = !this.pause;
        },
        // 初始化请求相关的
        initRequestDetail: function(data) {
            return {
                type: 'table',
                title: 'Request Fields',
                table: [[
                    {
                        omit: false,
                        type: 'string',
                        val: data.method
                    },
                    {
                        omit: data.method == 'POST',
                        type: 'array',
                        val: data.params
                    }
                    ]
                ]
            };
        },
        // 添加请求
        addRequest: function(data) {
            // 如果连接断开了，这时候页面刷新的请求是收不到的
            if (!data['connect']) {
                this.requests = [
                    {
                        "url": "https://github.com/aaronzjc",
                        "collection": [
                            {
                                "title": 'Oops, Your devtools connection to background has gone.Reopen console to continue . ',
                                'type': 'exception',
                                "table": []
                            }
                        ]
                    }
                ];
                return false;
            }
            
            var key = 'X-Wf-1-1-1-'; // 响应前缀


            // 整合过的完整JSON列表
            var item = [];
            var seeds = [];
            // this.debug += JSON.stringify(data);
            for (var i in data['headers']) {
                var value = data['headers'][i];
                if (value['name'].indexOf(key) >= 0) {
                    var fragment = value['value'].substring(value['value'].indexOf('|') + 1, value['value'].lastIndexOf('|'));
                    var index = parseInt(value['name'].substring(key.length));
                    var isEnd = value['value'].trim().slice(-1) == "\\"?false:true;
                    seeds.push({index:index, val:fragment, isEnd: isEnd});
                }
            }

            // 排序
            seeds.sort(function(a, b) {
                return (a.index > b.index) ? 1 : -1;
            });
            // 数据处理，将header片段拼接成完整的JSON段
            var json = '';
            for (var i in seeds) {
                json += seeds[i]['val'];
                if (seeds[i]['isEnd']) {
                    item.push(json);
                    json = '';
                }
            }
            var map = {
                'url': data['url'],
                'collection': []
            };
            for (var i in item) {
                var formatData = this.render(item[i]);
                if (formatData != false) {
                    map['collection'].push(formatData);
                }
            }
            map['collection'].push(this.initRequestDetail(data.params));
            // this.debug += JSON.stringify(map);
            var _self = this;
            setTimeout(function() {
                _self.requests.push(map);
            }, 200);
        },
        // 连接断开
        disconnect: function() {
            this.requests = [
                {
                    "title": 'Console Connect failed.Please reopen console to continue..',
                    'type': 'exception',
                    "table": []
                }
            ];
        },
        // 刷新当前页面
        refresh: function() {
            chrome.devtools.inspectedWindow.reload();
        },
        // 关闭弹层
        closeModal: function() {
            this.modalHide = true;
        },
        debugLog: function(data) {
            this.debug += JSON.stringify(data);
        },
        // 格式化数据
        render: function(data) {
            // document.write(data);
            data = JSON.parse(data); // 片段是JSON字符串，需要先解析
            /**
             * data => [[typeInfo], ["some intro info", [...]]]
             */
            switch (data[0]['Type']) {
            case 'INFO':
                break;
            case 'LOG':
                break;
            case 'WARN':
                break;
            case 'ERROR':
                break;
            case 'TRACE':
                break;
            case 'EXCEPTION':
                return ConsoleLog.exception(data[0], data[1]);
                break;
            case 'TABLE':
                return ConsoleLog.table(data[0], data[1]);
                break;
            }
            return false;
        },
        // 表格收放
        toggleTable: function(event) {
            $(event.target).toggleClass("collapse");
            $(event.target).next("table").toggle();
        },
        // 查看日志详情
        showDetail: function(data) {
            // TODO: JSON beauty
            this.modalHide = false;

            var render = renderJSON(data);
            this.isJson = render.isJson;
            this.json = data;
            this.jsonHTML = render.data;
        },
    },
    components: {
        'v-modal': Modal
    }
});

$(function() {
    var moveX = 0;
    var moveY = 0;

    var boxX = 0;
    var boxY = 0;
    // 设置弹层的拖动
    $('.modal .header').mousedown(function(event){
        boxX = parseInt($(".modal").position().left);
        boxY = parseInt($(".modal").position().top);

        var mouseOrignX = parseInt(event.clientX);
        var mouseOriginY = parseInt(event.clientY);

        console.log(boxX, boxY);
        console.log(mouseOrignX, mouseOriginY);

        function moveHandle(e) {
            moveX = e.clientX - mouseOrignX;
            moveY = e.clientY - mouseOriginY;

            $('.modal').css({
                'left': (boxX + moveX) + 'px',
                'top': (boxY + moveY) + 'px',
                'right': 'auto',
                'bottom': 'auto'
            });
        }

        $(document).mousemove(moveHandle);
        $(document).mouseup(function(e){
            $(document).unbind('mousemove', moveHandle);
        });
    });
});