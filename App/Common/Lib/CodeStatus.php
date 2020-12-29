<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2020-12-22
 * Time: 13:08
 */

namespace App\Common\Lib;


class CodeStatus
{
    // 通用返回码
    const SUCCESS = 0;                                  // 接口成功
    const ADMIN_SUCCESS = 0;                            // 后台返回成功
    const INVALID = -1;                                 // 错误

    // 数据库返回码
    const MYSQL_LOADED_ERROR = 100;                   // 缺少数据库拓展
    const MYSQL_CONNECT_ERROR = 101;                  // 数据库连接错误
    // Redis返回码
    const REDIS_LOADED_ERROR = 200;                   // 缺少Redis拓展
    const REDIS_CONNCE_ERROR = 201;                   // Redis连接错误
    const REDIS_EXE_ERROR = 202;                   // Redis执行错误


    static public $msg = [
        -1 => 'error',
        0 => 'ok',
        1 => 'ok',
    ];

    // 通过code获取msg
    static function getReasonPhrase($statusCode)
    {
        if (isset(self::$msg[$statusCode])) {
            return self::$msg[$statusCode];
        } else {
            return null;
        }
    }
}