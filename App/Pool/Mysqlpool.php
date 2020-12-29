<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 12/28/20
 * Time: 2:18 PM
 */

namespace App\Pool;

use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Config;
use EasySwoole\Redis\Redis;
use EasySwoole\Redis\Config\RedisConfig;


class Mysqlpool extends AbstractPool
{
    protected $redisConf;

    public function __construct(Config $conf,RedisConfig $redisConfig)
    {
        parent::__construct($conf);
        $this->redisConf = $redisConfig;
    }

    public function createObject()
    {
        $redis = new Redis($this->redisConf);
        return $redis;
    }
}