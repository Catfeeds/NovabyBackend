<?php
/**
 * Created by PhpStorm.
 * User: wz
 * Date: 2017/8/21
 * Time: 12:48
 */
?>
<script src="/scripts/socket.io.js"></script>
<script>
//客户端也使用socket.io，测试代码：控制台打印输出

//连接socket服务器
var socket = io('http://123.57.83.62:6001');
socket.on('connection', function (data) {
    console.log("###")
    console.log(data);
});

//收听的频道
socket.on('channel-1', function(data) {
//控制台输出广播消息
    console.log("@@@");
console.log(message);

//这里可以根据收到的消息，做一些改变页面结构的工作……
});

//可以收听多个频道
socket.on('channel-system', function(data){
    console.log("$$$$");
console.log(data);

//这里可以根据收到的消息，做一些改变页面结构的工作……
});

//控制台输出连接信息
console.log(socket);
</script>