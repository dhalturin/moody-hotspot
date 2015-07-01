<?
if(!defined('hotspot')) die('.!.');

function show_message($str)
{
    return <<<html
<div class="message_wrap">$str</div>
html;
}

function getmicrotime($start = false)
{
    global $_microtime;

    if(empty($_microtime))
    {
        $_microtime = microtime(true);
    }
    else
    {
        return sprintf('%.6f', microtime(true) - $_microtime);
        //return sprintf('%.6f (%.6f - %.6f)', microtime(true) - $_microtime, microtime(true), $_microtime);
    }

    //list($usec, $sec) = explode(' ', microtime());
    //return microtime(true);
}
