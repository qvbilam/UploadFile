<?php

namespace App\Common\Lib;

class RedisKey
{
    // 获取上传文件key
    static public function getUploadFileKey($uplpadId)
    {
        return 'File_' . $uplpadId;
    }
}