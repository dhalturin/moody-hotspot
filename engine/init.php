<?
if(!defined('hotspot')) die('.!.');

require_once(root_dir . '/engine/classes/template.class.php');
require_once(root_dir . '/engine/classes/mysqli.class.php');
require_once(root_dir . '/engine/classes/data.class.php');
require_once(root_dir . '/engine/function.php');

$cfg = array(
    'admin_login' => 'admin',
    'admin_pass' => 'qswdef',
    'lease_time' => 12,
    'lease_file' => '/var/lib/dhcp/dhcpd.leases',
    'db_host' => '10.0.0.1:3306',
    'db_base' => 'hotspot',
    'db_user' => 'root',
    'db_pass' => 'password'
);


$_leaseState = unserialize(@file_get_contents(root_dir . '/engine/lease'));

if($_SERVER['HTTP_X_FORWARDED_FOR']) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
$hash = md5($cfg['admin_login'] . '_' . $cfg['admin_pass'] . '_' . $_SERVER['REMOTE_ADDR']);

if($_COOKIE['data'] != $hash)
{
    require_once(root_dir . '/engine/login.php');
}
else
{
    $_state = $_leaseState ? 1 : 2;

    $content = <<<html
<div class="header">
    <a href="?do=logout">Logout</a>
    <a href="?">Main</a><a href="?do=all">All devices</a><a href="?do=lease">Lease state<div class="active status{$_state}"></div></a><a href="?do=dhcp_scan">DHCP check</a>
</div>
html;

    if($_GET['do'] == 'logout')
    {
        setcookie('data', null, 0);

        header('location: ./?');
    }
    else
    if($_GET['do'] == 'lease')
    {
        file_put_contents(root_dir . '/engine/lease', serialize($_leaseState ? false : true));

        header('location: ?'); die;
    }
    else
    if($_GET['do'] == 'dhcp_scan')
    {
        exec('grep -E "lease|hostname|hardware|\}" ' . $cfg['lease_file'], $lease);
        preg_match_all('/lease(.*){([^}]*)}/i', implode(PHP_EOL, $lease), $lease);

        print '<pre>';

        $q = array();

        foreach ($lease[2] as $v)
        {
            preg_match('/hardware ethernet ([a-z0-9:]+)/i', $v, $m);
            preg_match('/client-hostname "(.*)"/i', $v, $c);

            if(sizeof($c) > 1)
            {
                $q[$m[1]] = "when `mac` = '{$m[1]}' then '{$c[1]}'";
            }
        }

        if(sizeof($q))
        {
            $q = 'update `device` set `name` = case ' . implode(PHP_EOL, $q) . ' else `name` end;';

            if($db->query($q))
            {
                header('location: /?'); die;
            }
            else
            {
                $content = show_message('Failure sql: ' . $q);
            }
        }
    }
    else
    if($_GET['do'] == 'privileged')
    {
        if(empty($_GET['mac']) || !is_numeric($_GET['act'])){ header('location: ?active_error'); exit; }

        $q = 'update `device` set `privileged` = ' . ($_GET['act'] == 1 ? 2 : 1) . ' where `mac` = \'' . $_GET['mac'] . '\'';

        if($db->query($q))
        {
            header('location: /?'); die;
        }
        else
        {
            $content = show_message('Failure sql: ' . $q);
        }
    }
    else
    if($_GET['do'] == 'active')
    {
        if(empty($_GET['ip']) || !is_numeric($_GET['act'])){ header('location: ?active_error'); exit; }

        $q = 'update `device` set `lease_start` = 0, `lease_end` = 0, `active` = ' . ($_GET['act'] == 1 ? 2 : 1) . ' where `ip` = \'' . ip2long($_GET['ip']) . '\'';

        if($db->query($q))
        {
            if($_GET['act'] == 1)
            {
                $data = $data->getList(array('ip', ip2long($_GET['ip'])));

                file_put_contents('/var/.intranet/user', serialize(array(
                    array('ip', $data[0]['ip']),
                    array('mac', $data[0]['mac']),
                    array('delete', 1)
                )));
            }

            header('location: /?'); die;
        }
        else
        {
            $content = show_message('Failure sql: ' . $q);
        }
    }
    else
    {
        $content.= <<<html
<div class="data">
    <div>
        <span>IP</span>
        <span>Speed</span>
        <span>Lease start</span>
        <span>Lease end</span>
        <span>Mac</span>
        <span>Name</span>
    </div>
html;

        //print(ip2long('10.0.0.5'));die;

        function get_rate($iface, $ip)
        {
            $octet = array_reverse(explode('.', $ip));

            $file = '/etc/htb/' . $iface . '-2:20:30:'. ($octet[0] + 30)  .'.' . $ip;

            if(file_exists($file))
            {
                $rule = array();
                preg_match_all('/([a-z]+)=([0-9]+)/i', file_get_contents($file), $file);

                foreach($file[1] as $k => $v)
                {
                    $rule[$v] = ($file[2][$k] > 1024 ? ($file[2][$k] / 1024) . 'MBit' : $file[2][$k] . 'KBit');
                }

                return $iface . ' - ' . ($rule['RATE'] == $rule['CEIL'] ? $rule['RATE'] : $rule['RATE'] . ' : ' . $rule['CEIL']);
            }
        }

        foreach($data->getList($_GET['do'] == 'all' ? '' : '`lease_end` > ' . time()) as $d)
        //foreach($data->getList() as $d)
        {
            $d['ip'] = long2ip($d['ip']);
            $d['lease_start'] = date('d.m.y H:i', $d['lease_start']);
            $d['lease_end'] = date('d.m.y H:i', $d['lease_end']);

            $privileged = $d['privileged'] == 2 ? 'privileged' : '';

            #### check speed device start ####
            if($d['speed'] > 1)
            {
                $d['speed'] = implode(', ', array(get_rate('eth0', $d['ip']), get_rate('eth1', $d['ip'])));
            }
            else
            {
                $d['speed'] = 'Normal';
            }
            #### check speed device end ####

            $content .= <<<html
<div>
    <span onclick="location.href='?do=active&ip={$d['ip']}&act={$d['active']}'"><div class="active status{$d['active']}"></div>{$d['ip']}</span>
    <span>{$d['speed']}</span>
    <span>{$d['lease_start']}</span>
    <span>{$d['lease_end']}</span>
    <span onclick="location.href='?do=privileged&mac={$d['mac']}&act={$d['privileged']}'" class="{$privileged}">{$d['mac']}</span>
    <span>{$d['name']}</span>
</div>
html;
        }

        $content .= '</div>';
    }

    $tpl->set('main', 'content', $content);
}

$tpl->set('main', 'date', date('YmdHi'));
$tpl->set('main', 'title', 'Moody hotspot');
$tpl->set('main', 'bg_list', json_encode(glob('data/img/*.jpg')));
