<?php

/**
 * This file is part of EasySwoole
 * @link     https://github.com/easy-swoole
 * @document https://www.easyswoole.com
 * @license https://github.com/easy-swoole/easyswoole/blob/3.x/LICENSE
 */

namespace App\HttpController\Api;

use EasySwoole\HttpAnnotation\AnnotationTag\Api;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiDescription;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiFail;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiRequestExample;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiSuccess;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiSuccessParam;
use EasySwoole\HttpAnnotation\AnnotationTag\Param;
use EasySwoole\HttpAnnotation\AnnotationTag\Method;
Use EasySwoole\HttpAnnotation\AnnotationTag\ApiGroup;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiGroupAuth;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiFailParam;
use App\Cache\Redis\RedisBase;
use App\Common\Lib\RedisKey;
use EasySwoole\Utility\Random;

/**
 * @ApiGroup(groupName="上传文件")
 * @ApiGroupAuth(name="token",from="登录接口",required="",notEmpty="",description="登录后返回的用户标示")
 */
class Upload extends AuthBase
{

    protected $chunkSize = 1 * 1024 * 1024; // 分块的大小/Mb


    /**
     * @Api(name="初始化",path="/api/upload/chunk/init")
     * @ApiDescription(value="上传文件初始化")
     * @Method(allow={GET})
     * @Param(name="user_name",description="用户昵称",required="must",notEmpty="")
     * @Param(name="file_hash",description="文件hash值",required="must",notEmpty="")
     * @Param(name="file_size",description="文件大小", required="",notEmpty="")
     * @Param(name="file_path",description="文件存放路径", required="",notEmpty="")
     * @Param(name="file_name",description="文件名称", required="",notEmpty="")
     * @ApiSuccess({"code":0,"msg":"ok","data":{"upload_id":"qvbilam-1608624238","file_hash":"xxxx","file_size":"100","file_path":"xxxx\/","file_name":"xxx","chunk_size":1048576,"chunk_count":1}})
     * @ApiSuccessParam(name="upload_id",from="result",type="string",description="上传编号")
     * @ApiSuccessParam(name="file_hash",from="result",type="string",description="文件hash值")
     * @ApiSuccessParam(name="file_size",from="result",type="string",description="文件大小")
     * @ApiSuccessParam(name="file_path",from="result",type="string",description="文件存放目录")
     * @ApiSuccessParam(name="file_name",from="result",type="string",description="文件名称")
     * @ApiSuccessParam(name="chunk_size",from="result",type="string",description="每个文件块的大小")
     * @ApiSuccessParam(name="chunk_count",from="result",type="int",description="文件块数量")
     * @ApiFail({"code": -1,"msg": "初始化异常"})
     */
    public function init()
    {
        try {
            if (empty($this->params['user_name']) || empty($this->params['file_hash']) || empty($this->params['file_size']) || empty($this->params['file_path']) || empty($this->params['file_name'])) {
                return $this->error('缺少参数');
            }
            // 1.解析请求信息
            $userName = $this->params['user_name'];
            $fileHash = $this->params['file_hash'];
            $fileSize = $this->params['file_size'];
            $filePath = $this->params['file_path'];
            $fileName = $this->params['file_name'];
            // 3. 生成分块上传初始化信息
            $res = [
                'upload_id' => $userName . time() . Random::character(6), // 上传id,没次上传不一样
                'user_name' => $userName,
                'file_hash' => $fileHash, // 文件hash值
                'file_size' => $fileSize, // 文件大小
                'file_path' => trim($filePath, '/') . '/', // 用户上传目录
                'file_name' => $fileName, // 用户上传文件名称
                'chunk_size' => $this->chunkSize, // 文件块的大小
                'chunk_count' => ceil($fileSize / $this->chunkSize), // 文件块总数量
            ];
            // 4.初始化信息写入到redis中
            $redis = RedisBase::getInstance();
            foreach ($res as $k => $v) {
                $redis->hSet(RedisKey::getUploadFileKey($res['upload_id']), $k, $v);
            }
            $redis->hSet(RedisKey::getUploadFileKey($res['upload_id']), 'success_chunk', 0);
            // 5.初始化信息返回给客户端
            return $this->success($res);
        } catch (\Exception $e) {
            return $this->error('初始化异常');
        }
    }


    /**
     * @Api(name="分块上传",path="/api/upload/chunk/upload")
     * @ApiDescription(value="上传文件初始化")
     * @Method(allow={GET})
     * @Param(name="file_hash",type="string",description="文件hash值",required="must",notEmpty="")
     * @Param(name="upload_file",type="file",description="文件块",required="must",notEmpty="")
     * @ApiSuccess({"code":0,"msg":"ok","data":[]})
     * @ApiFail({"code": -1,"msg": "上传失败"})
     */
    public function chunkUpload()
    {
        if (empty($this->params['upload_id'])) {
            return $this->error('缺少参数');
        }
        // 1. 解析请求
        $uploadId = $this->params['upload_id'];
        $fileInfo = RedisBase::getInstance()->hGetAll(RedisKey::getUploadFileKey($uploadId));
        $fileUserPath = $fileInfo['file_path'] ?: '';
        $fileUserName = $fileInfo['file_name'] ?: '';
        $userName = $fileInfo['user_name'] ?: '';
        // 文件地址: Upload / 用户名 / 用户选择上传文件路径 / 文件名 / 分块目录
        $filePath = EASYSWOOLE_ROOT . "/Upload/$userName/" . $fileUserPath . $fileUserName . ".chunk/";
        if (!file_exists(iconv("UTF-8", "GBK", $filePath))) {
            mkdir($filePath, 0744, true);
        }
        $file = $this->request()->getUploadedFile('upload_file'); // 获取上传文件
        $flag = $file->moveTo($filePath . $file->getClientFilename());
        if (!$flag) {
            return $this->error('上传失败');
        }
        return $this->success();
    }

    /**
     * @Api(name="合并上传",path="/api/upload/chunk/merge")
     * @ApiDescription(value="上传文件初始化")
     * @Method(allow={GET})
     * @Param(name="file_hash",description="文件hash值",required="must",notEmpty="")
     * @ApiSuccess({"code":0,"msg":"ok","data":[]})
     * @ApiFail({"code": -1,"msg": "分块全部没上传成功"})
     */
    public function mergeChunk()
    {
        if (empty($this->params['upload_id'])) {
            return $this->error('缺少参数');
        }
        // 1. 解析请求
        $uploadId = $this->params['upload_id'];
        // 3. 通过upload_id查询redis判断所有分块是否完成
        $redis = RedisBase::getInstance();
        $fileInfo = $redis->hGetAll("File_" . $uploadId);
        if (!$fileInfo) {
            return $this->error('上传失败');
        }
        $chunkNumString = $fileInfo['chunk_number'];
        if (empty($chunkNumString)) {
            return $this->error('分块全部没上传成功');
        }
        $chunkNum = explode(',', $chunkNumString);
        if (count($chunkNum) != $fileInfo['chunk_count']) {
            return $this->error('分块全部没上传成功');
        }
        // 4. 合并分块
        $filePath = $fileInfo['file_path'] ?: '';
        $fileName = $fileInfo['file_name'] ?: '';
        $userName = $fileInfo['user_name'] ?: '';
        $fileSha1 = $this->mergeUserFIle($userName, $fileName, $filePath);
        // 5. 删除分块
        $delFile = $this->delFile($userName, $fileName, $filePath);
        // 6. 响应结果
        if ($fileSha1 != $fileInfo['file_hash']) {
            return $this->error('文件合并异常');
        }
        if (!$delFile) {
            // 删除块异常
        }
        $redis->del("File_" . $uploadId);
        return $this->success();
    }

    /*
     * 合并用户文件
     * $userName: 用户名
     * $userFileName: 用户文件名
     * $userFilePaht: 用户上传目录
     * */
    protected function mergeUserFIle($userName, $userFileName, $userFilePath)
    {
        $chunkFilePath = EASYSWOOLE_ROOT . "/Upload/$userName/$userFilePath" . $userFileName . ".chunk/";
        $newFilaPath = EASYSWOOLE_ROOT . "/Upload/$userName/$userFilePath";
        $newFile = $newFilaPath . $userFileName;
        // 获取分块的文件名
        $chunkFile = scandir($chunkFilePath);
        foreach ($chunkFile as $value) {
            if ($value != '.' && $value != '..') {
                // 读区文件内容
                $content = fopen($chunkFilePath . '/' . $value, 'rb');
                file_put_contents($newFile, $content, FILE_APPEND);
            }
        }
        return sha1_file($newFile);
    }

    protected function delFile($userName, $userFileName, $userFilePath)
    {
        $dir = EASYSWOOLE_ROOT . "/Upload/$userName/$userFilePath" . $userFileName . ".chunk/";
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹：
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }

    }

}