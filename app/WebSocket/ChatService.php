<?php
namespace App\WebSocket;

use Cache;
use App\Model\User;
use App\Model\PrjChat;
use App\Model\Message;

class ChatService{
    public $server;

    public function __construct($port = 9501) {
        $this->server = new \swoole_websocket_server("0.0.0.0", $port);

        // daemonize => 1，加入此参数后，转入后台作为守护进程运行
        $daemonize = 0;

        if (env('APP_ENV') == 'product')
            $daemonize = 1;

        $this->server->set([
            'daemonize' => $daemonize
        ]);

        $this->server->on('open', [$this, 'onOpen']);

        $this->server->on('message', [$this, 'onMessage']);

        $this->server->on('close', [$this, 'onClose']);

        // $this->server->on('request', function ($request, $response) {
        //     // 接收http请求从get获取message参数的值，给用户推送
        //     // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
        //     foreach ($this->server->connections as $fd) {
        //         $this->server->push($fd, $request->get['message']);
        //     }
        // });

        // $this->server->start();
    }

    public function start(){
        $this->server->start();

        return $this->server::$master_pid;
    }

    public static function onOpen(\swoole_websocket_server $server, $request){
        echo "server: handshake success with fd{$request->fd}\n";
    }

    public static function onMessage(\swoole_websocket_server $server, $frame){
        // echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $fd = $frame->fd;
        // json
        $data = $frame->data;
        // array
        $paras = json_decode($data, true);
        $cmd = $paras['cmd'] ?? '';
        $cmd = strtolower($cmd);
        $success = false;
        $result = [];

        // socket 接收命令集合
        $request_cmds = [
            // 注册 socket
            'register',
            // 发送文字
            'send_message',
        ];

        // socket 响应命令集合
        $response_cmds = [
            // 注册响应
            'register_response',
            // 发送 文字/图片 响应
            'send_response',
            // 接收消息
            'revice_message',
        ];

        // var_dump($cmd);

        if (in_array($cmd, $request_cmds)) {
            // 客户端注册
            if ($cmd == 'register') {
                $token = $paras['token'] ?? '';

                if ($token) {
                    $user = User::where([
                        'user_token' => $token
                    ])->first();

                    if ($user) {
                        $success = true;
                        $result = [
                            'cmd' => 'register_response',
                            'status' => 'success',
                            'register_user_id' => $user->user_id
                        ];

                        $cache_info = [
                            'user_id' => $user->user_id,
                            'fd' => $fd,
                        ];

                        // 通过 user_id 存储缓存
                        $user_cache_key = '_user_websocket_' . $user->user_id;
                        Cache::forever($user_cache_key, $cache_info);

                        // 通过 fd 存储缓存
                        $fd_cache_key = '_fd_websocket_' . $fd;
                        Cache::forever($fd_cache_key, $cache_info);

                        $server->push($frame->fd, json_encode($result));
                    }
                }
            }
            // 发送信息
            elseif ($cmd == 'send_message') {
                $from_user_id = $paras['from_user_id'] ?? '';
                $to_user_id = $paras['to_user_id'] ?? '';
                $message = $paras['message'] ?? '';

                if ($from_user_id && $to_user_id && $message && ($from_user_id != $to_user_id)) {
                    $success = true;
                    // 保存发送消息
                    $talk_key = $from_user_id . '_' . $to_user_id;

                    if ($from_user_id > $to_user_id)
                        $talk_key = $to_user_id . '_' . $from_user_id;

                    $chat_info = [
                        'prj_id' => $paras['project_id'] ?? '',
                        'chat_from_uid' => $from_user_id,
                        'chat_to_uid' => $to_user_id,
                        'talk_key' => $talk_key,
                        'content' => $message,
                        'created_at' => time()
                    ];

                    PrjChat::create($chat_info);

                    // 请求发送用户的返回信息
                    $result = [
                        'cmd' => 'send_response',
                        'status' => 'success',
                        'type' => 'outline',
                    ];

                    $to_user_cache_key = '_user_websocket_' . $to_user_id;
                    $to_user_cache = Cache::get($to_user_cache_key);

                    if ($to_user_cache) {
                        // 接收用户 socket fd
                        $to_fd = $to_user_cache['fd'] ?? 0;
                        // 接收用户 是否在线
                        $fd_info = $server->exist($to_fd);

                        // 接收消息用户在线
                        if ($fd_info) {
                            $result['type'] = 'online';

                            $msg_info = [
                                'cmd' => 'revice_message',
                                'from_user_id' => $from_user_id,
                                'to_user_id' => $to_user_id,
                                'message' => $message,
                                'project_id' => $chat_info['prj_id']
                            ];

                            $server->push($to_fd, json_encode($msg_info));
                        }
                    }
                    // 离线用户 系统通知
                    else{
                        $msg = new Message();
                        $msg->msg_from_uid = $from_user_id;
                        $msg->msg_to_uid = $to_user_id;
                        $msg->msg_action = 7;
                        $msg->msg_rid = $chat_info['prj_id'];
                        $msg->msg_remark = $message;
                        $msg->msg_time = time();
                        $msg->msg_read = 0;
                        $msg->msg_delete = 0;
                        $msg->msg_status = 0;

                        $msg->save();
                    }

                    $server->push($fd, json_encode($result));
                }
            }
        }

        if (!$success) {
            $result['cmd'] = $cmd;
            $result['status'] = 'failed';
            $server->push($frame->fd, json_encode($result));
        }

        // $result = [
        //     'cmd' => 'send_response',
        //     'status' => 1
        // ];

        // $server->push($frame->fd, json_encode($result));
    }

    public static function onClose($ser, $fd){
        echo "client {$fd} closed\n";

        // 移除用户socket注册缓存信息
        $fd_cache_key = '_fd_websocket_' . $fd;
        $cache_info = Cache::pull($fd_cache_key);

        if ($cache_info && isset($cache_info['user_id'])) {
            $user_cache_key = '_user_websocket_' . $cache_info['user_id'];

            Cache::forget($user_cache_key);
        }
    }
}