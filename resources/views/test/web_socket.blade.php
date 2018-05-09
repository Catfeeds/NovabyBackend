<!DOCTYPE html>
<html>
<head>
    <title></title>

    <script src="https://cdn.jsdelivr.net/npm/vue"></script>
</head>
<body>
    <div id="app">
      {{ message }}
    </div>

    <script type="text/javascript">
        var app = new Vue({
            el: '#app',
            data: {
                message: 'Hello Vue!'
            }
        });

        // var socket = new WebSocket("ws://127.0.0.1:9501");

        // socket.onopen = function(event) {
        //     // 发送一个初始化消息
        //     // socket.send('I am the client and I\'m listening!');

        //     // 监听消息
        //     socket.onmessage = function(event) {
        //         console.log('Client received a message',event);

        //         var msg = $('#msg').html();
        //         msg += event.data + '<br >'
        //         $('#msg').html(msg);
        //     };

        //     // 监听Socket的关闭
        //     socket.onclose = function(event) {
        //         console.log('Client notified socket has closed',event);
        //     };
        // };
    </script>
</body>
</html>