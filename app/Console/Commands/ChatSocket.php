<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\ChatService;

class ChatSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat_socket {--cmd=start}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Chat WebSocket';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $cmd = $this->option('cmd');

        // 启动 chat websocket 服务
        if ( $cmd == 'start') {
            $server = new ChatService();
            $pid = $server->start();
        }
    }
}
