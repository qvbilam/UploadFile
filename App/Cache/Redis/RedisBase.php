<?php

namespace App\Cache\Redis;
use EasySwoole\RedisPool\Redis as RedisPool;
use EasySwoole\Pool\Manager;
use EasySwoole\Component\Singleton;

class RedisBase
{
    use Singleton;

    protected $redis;

    public function __construct()
    {
        /*判断有没有安装redis拓展*/
        if (!extension_loaded('redis')) {
            throw new \Exception('redis拓展不存在');
        }
        try {
            $this->redis = RedisPool::defer('redis');
        } catch (\Exception $e) {
            throw new \Exception('redis服务异常');
        }
    }

    public function __call($name, $arguments)
    {
        try{
            return $this->redis->$name(...$arguments);
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}