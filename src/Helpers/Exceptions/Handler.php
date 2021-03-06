<?php

namespace Tree6bee\Support\Helpers\Exceptions;

use Tree6bee\Support\Helpers\Arr;
use Tree6bee\Support\Helpers\Exceptions\Contracts\ExceptionsHandler;
use Tree6bee\Support\Helpers\Exceptions\Reporter\Debugger;

class Handler implements ExceptionsHandler
{
    protected $collapseDir;

    protected $cfVersion;

    /**
     * @param string $collapseDir 错误显示页面中需要折叠的代码目录
     * @param string $cfVersion 错误显示页面的框架标识
     */
    public function __construct($collapseDir = '', $cfVersion = 'TFramework/1.0')
    {
        $this->collapseDir = $collapseDir;
        $this->cfVersion = $cfVersion;
    }

    /**
     * 异常接管
     * 1. 记录框架异常日志
     * 2. 展示:
     *      * 命令行:直接输出
     *      * http方式:
     *          * 测试环境：输出错误页面
     *          * 其它环境(如 正式环境)：
     *              * 框架错误：框架处理
     *              * 其它：直接返回500错误
     */
    public function handle($e)
    {
        //有可能为 Exception 也有可能为 Throwable 需要进行转化为 Exception
        // 如果没有这两个函数的调用，那么在后续的错误处理过程中，当再次产生异常或是错误时，可能造成死循环
        restore_error_handler();
        restore_exception_handler();

        $this->report($e);

        $this->render($e);
    }

    protected function render($e)
    {
        if (php_sapi_name() == 'cli') { //命令行模式
            $this->renderForConsole($e);
        } else {    //web运行方式
            $this->renderHttpException($e);
        }
    }

    /**
     * 命令行模式
     */
    protected function renderForConsole($e)
    {
        echo (string) $e;
    }

    protected function renderHttpException($e)
    {
        (new Debugger($this->collapseDir, $this->cfVersion))->displayException($e);
    }

    /**
     * 获取记录日志用的异常字符串
     */
    protected function getLogOfException($e)
    {
        //获取异常信息
        $request_uri = (php_sapi_name() == 'cli') ? Arr::get($GLOBALS['argv'], 1, '/') : $_SERVER['REQUEST_URI'];
        return sprintf(
            "[%s %s] %s\n%s\n",
            date('Y-m-d H:i:s'),
            date_default_timezone_get(),
            $request_uri,
            (string) $e
        );

        //根据情况决定是否记录超全局变量，方便排查用户访问错误
        // gethostname();   //服务器主机名，方便排查集群中的具体机器错误
        // $GLOBALS $_SERVER $_REQUEST $_POST $_GET $_FILES $_ENV $_COOKIE $_SESSION
        // $_SERVER['REQUEST_URI'] $_SERVER['SCRIPT_NAME'] $_SERVER['HTTP_REFERER']
        // var_export($_SERVER, true);
        // var_export($_COOKIE, true);
        // var_export($_REQUEST, true);
    }

    /**
     * 错误日志记录
     */
    protected function report($e)
    {
    }
}
