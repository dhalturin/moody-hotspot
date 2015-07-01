============================================= 
Moody Hotspot - panel for intranet. 
============================================= 

Author: Denis Halturin
@dhalturin

Preview
========
* Login page (admin / client lease) ![Login page](http://cs627922.vk.me/v627922986/5d34/54GOB1qGgAk.jpg)
* List active devices ![List active](https://pp.vk.me/c627922/v627922576/7a00/Ty2TzUYNmxw.jpg)
* List all devices (active and blocked) ![List all devices](https://pp.vk.me/c627922/v627922576/79f6/NeU_q-8Rczs.jpg)

Depends
========
* Apache + php5 (or Nginx + php-fpm)
* mysql
* incron
* iptables
* dhcp (isc-dhcp-server)

Installation
========
Create work directory:
```bash
   mkdir -p /home/htdocs/intranet/www
   mkdir -p /var/.intranet
```

Create virtualhost. Example for nginx:
```nginx
server
{
    listen                  10.0.0.1:80;

    access_log              /var/log/nginx/intranet.access.log main;
    error_log               /var/log/nginx/intranet.error.log notice;

    root                    /home/htdocs/intranet/www/;

    location / {
        rewrite             ^/(.*)$ /index.php?q=$1 last;
    }

    location                /data/ {}

    include                 /etc/nginx/conf/php;
}
```

Puts /etc/nginx/conf/php:
```nginx
   location ~ \.php$
   {
       try_files               $uri =404;

       fastcgi_pass            php_pool;
       fastcgi_index           index.php;
       include /etc/nginx/conf/fastcgi_params; # << optional fastcgi options. go to google ;)
       fastcgi_param           SCRIPT_FILENAME $document_root$fastcgi_script_name;
       fastcgi_ignore_client_abort off;
   }
```

Add incron event for /var/.intranet/intranet.php on file /var/.intranet/user:
```bash
   incrontab -e
```
```bash
   /var/.intranet/user IN_CLOSE_WRITE /var/.intranet/intranet.php
```

Clone repo:
```bash
   cd /home/htdocs/intranet/www
   git clone https://github.com/dhalturin/moody-hotspot.git
```

Move intranet.php:
```bash
   mv {,/var/.intranet/}intranet.php
```

Import create database and import dump.sql.

Change iptables route:
```bash
   iptables -A PREROUTING -i eth1 -p tcp -m mark ! --mark 0x1 -j DNAT --to-destination 10.0.0.1
   iptables -A POSTROUTING -s 10.0.0.0/24 -p udp -m udp --dport 53 -j SNAT --to-source 83.243.66.91
   iptables -A POSTROUTING -s 10.0.0.0/24 -m mark --mark 0x1 -j SNAT --to-source 83.243.66.91
```

See ./engine/init.php file. Change $cfg variable:
```php
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
```
