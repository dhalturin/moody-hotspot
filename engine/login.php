<?
if(!defined('hotspot')) die('.!.');

$tpl->open('auth_form');

$lease_time = $cfg['lease_time'] * 60 * 60;
$lease_ips = array();
$auth_button = 'Получить токкен';

//$cmd = 'arp -n ' . $_SERVER['REMOTE_ADDR'] . ' | grep ' . $_SERVER['REMOTE_ADDR'] . ' | awk \'{print $3}\' | sed \'s/--//g\'';
//$cmd = 'arp -n ' . $_SERVER['REMOTE_ADDR'] . ' | awk \'$1=="' . $_SERVER['REMOTE_ADDR'] . '" && $3!="--" {print $3}\'';
//$mac = exec($cmd);

if (preg_match_all("/lease(.*){([^}]*)}/", file_get_contents($cfg['lease_file']), $mixed) !== false)
{
    foreach($mixed[0] as $v)
    {
        preg_match('/lease ([0-9.]+)/', $v, $ip);
        preg_match('/client-hostname "(.*)"/', $v, $hostname);
        preg_match('/hardware ethernet (.*);/', $v, $mac);

        $lease_ips[$ip[1]] = array(
            'mac' => $mac[1],
            'hostname' => $hostname[1]
        );
    }
}
else die('exit code: 1');

$ip = $_SERVER['REMOTE_ADDR'];
$user = $data->getList(array('mac', $lease_ips[$ip]['mac']));

if($user)
{
    if(time() < $user[0]['lease_end'])
    {
        $auth_button = 'Обновить токкен';

        $tpl->data['auth_form'].= show_message('Аренда адреса (' . $_SERVER['REMOTE_ADDR'] . ', ' . $lease_ips[$ip]['mac'] . ') закончится через ' . gmdate('H ч i мин s сек', ($lease_time - (time() - $user[0]['lease_start']))));
    }
}

$_free = $_SERVER['HTTP_HOST'] != '10.0.0.1';

if($_POST || $_free)
{
    if($_POST['enter'])
    {
        if(($_POST['login'] == $cfg['admin_login']) && ($_POST['pass'] == $cfg['admin_pass']))
        {
            setcookie('data', $hash, (time() + (60 * 60 * 24))); // 1 day

            header('location: ./?'); die;
        }
        else
        {
            $tpl->data['auth_form'] .= show_message('Wrong fucking enter data');
        }
    }
    else
    {
        if($lease_ips[$ip]['mac'] && $_leaseState)
        {
            $field = array(
                array('ip', ip2long($_SERVER['REMOTE_ADDR'])),
                array('mac', $lease_ips[$ip]['mac']),
                array('name', $lease_ips[$ip]['hostname']),
                array('lease_start', time()),
                array('lease_end', time() + $lease_time)
            );

            $user = $data->getList(array('mac', $lease_ips[$ip]['mac']));

            $_logged = false;

            if($user)
            {
                //print '<pre>'; print_r($user); die;

                if($user[0]['active'] == 1) $_logged = true;

                if(time() > $user[0]['lease_end'] && $user[0]['active'] == 1)
                {
                    /* update device info */

                    if(!$data->deviceUpdate($field, array('mac', $lease_ips[$ip]['mac'])))
                    {
                        $tpl->data['auth_form'].= show_message('Failure update data in sql');
                    }

                    $field[] = array('delete' => 1);
                }
                else
                {
                    if($user[0]['active'] == 1)
                    {
                        exec('ps axufw | grep u[s]er_' . $_SERVER['REMOTE_ADDR'], $ps);
                        exec('iptables-save', $iptables);

                        $iptables = strtolower(implode(PHP_EOL, $iptables));

                        if(!empty($ps) && strstr($iptables, $_SERVER['REMOTE_ADDR']))
                        {
                            header('location: ' . ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); die;
                        }
                    }
                    else
                    {
                        $tpl->data['auth_form'].= show_message('Device is lock');
                    }
                }
            }
            else
            {
                if($data->deviceInsert($field))
                {
                    $_logged = true;
                }
                else
                {
                    $tpl->data['auth_form'].= show_message('Failure insert data in sql');
                }
            }

//print_r($_free); die;

            if($_logged && $_free)
            {
//print 'writing'; die;

                if(file_put_contents('/var/.intranet/user', serialize($field)))
                {
                    header('location: ' . ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); die;
                    //header('location: http://' . $_SERVER['SERVER_ADDR'] . '/?write'); die;
                }
                else
                {
                    $tpl->data['auth_form'].= show_message('Failure insert data in file');
                }
            }
        }
        else
        {
            $tpl->data['auth_form'].= show_message($_leaseState ? 'Could not get fucking IP-data. Command: ' . $cmd : 'Dammit! Lease wrong ');
        }
    }
}

$tpl->set('auth_form', 'auth_button', $auth_button);
$tpl->set('main', 'content', 'auth_form', true);
