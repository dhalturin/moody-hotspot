#!/usr/bin/env php
<?php
error_reporting(E_ALL ^ E_NOTICE);

print ' .. get iptables' . PHP_EOL;

exec('iptables-save', $iptables);
$iptables = strtolower(implode(PHP_EOL, $iptables));

$user = array();

print ' .. get user file'. PHP_EOL;

if(!file_get_contents('/var/.intranet/user')) die;

foreach(unserialize(file_get_contents('/var/.intranet/user')) as $v)
{
    if($v) $user[$v[0]] = $v[1];
}

if(!$user) die;

print ' .. check user data for ' . $user['mac'] . PHP_EOL;
if(!strstr($iptables, $user['mac']) || $user['delete'])
{
    $user['ip'] = long2ip($user['ip']);

    print ' .. ' . ($user['delete'] ? 'delete' : 'add') . ' ' . $user['ip'] . ' ' . $user['mac'] . PHP_EOL;

    if($user['delete'])
    {
        exec('/sbin/iptables -t mangle -D PREROUTING -s ' . $user['ip'] . ' -m mac --mac-source ' . $user['mac'] . ' -j MARK --set-xmark 0x1/0xffffffff; rm /var/.intranet/user_' . $user['ip']);

        exec('ps axwuf | grep user_' . $user['ip'] . ' | awk \'{print $2}\' | xargs kill -9');
    }
    else
    {
        exec('/sbin/iptables -t mangle -A PREROUTING -s ' . $user['ip'] . ' -m mac --mac-source ' . $user['mac'] . ' -j MARK --set-mark 1');
        file_put_contents('/var/.intranet/user_' . $user['ip'], '#!/bin/bash' . PHP_EOL . 'sleep ' . (12 * 60 * 60) . PHP_EOL . '/sbin/iptables -t mangle -D PREROUTING -s ' . $user['ip'] . ' -m mac --mac-source ' . $user['mac'] . ' -j MARK --set-xmark 0x1/0xffffffff' . PHP_EOL . 'rm /var/.intranet/user_' . $user['ip']);
        exec('/bin/bash /var/.intranet/user_' . $user['ip'] . ' > /dev/null 2>&1 &');
    }

    //exec('echo -n > /var/.intranet/user');
}
else print " .. data exists";

print ' .. conntrack flush' . PHP_EOL;
exec('conntrack -F > /dev/null 2>&1');

