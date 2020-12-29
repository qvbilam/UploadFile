<?php


namespace App\HttpController;


use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
        /*
          * eg path : /router/index.html  ; /router/ ;  /router
         */
        $routeCollector->get('/router','/test');
        $routeCollector->get('/doc','/index/doc');
        /*
         * eg path : /closure/index.html  ; /closure/ ;  /closure
         */
        $routeCollector->get('/closure',function (Request $request,Response $response){
            $response->write('this is closure router');
            //不再进入控制器解析
            return false;
        });

        $routeCollector->addGroup('/api/upload/chunk',function (RouteCollector $collector){
            $collector->addRoute('GET','/init','/Api/Upload/init'); // 初始化
            $collector->addRoute('POST','/upload','/Api/Upload/chunkUpload'); // 初始化
            $collector->addRoute('POST','/merge','/Api/Upload/mergeChunk'); // 初始化
        });

    }
}