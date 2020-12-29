<?php


namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\HotReload\HotReloadOptions;
use EasySwoole\HotReload\HotReload;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Redis\Config\RedisClusterConfig;
use EasySwoole\Redis\Config\RedisConfig;
use mysql_xdevapi\Exception;
use EasySwoole\Http\Message\Status;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
        // redis 默认127.0.0.1：6379
        Redis::getInstance()->register('redis', new RedisConfig([
            'host' => '127.0.0.1',
            'port' => 6379,
            'auth' => ''
        ]));
        // redis集群
        Redis::getInstance()->register('redisCluster', new RedisClusterConfig([
            ['127.0.0.1', 6379]
        ]));

        \EasySwoole\Component\Di::getInstance()->set(\EasySwoole\EasySwoole\SysConst::HTTP_GLOBAL_ON_REQUEST, function (\EasySwoole\Http\Request $request, \EasySwoole\Http\Response $response){
            $response->withHeader('Access-Control-Allow-Origin', '*');
            $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            if ($request->getMethod() === 'OPTIONS') {
                $response->withStatus(Status::CODE_OK);
                return false;
            }
            return true;
        });
    }

    public static function mainServerCreate(EventRegister $register)
    {
        $hotReloadOptions = new HotReloadOptions();
        $hotReload = new HotReload($hotReloadOptions);
        $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App']);
        $server = ServerManager::getInstance()->getSwooleServer();
        $hotReload->attachToServer($server);
    }
}