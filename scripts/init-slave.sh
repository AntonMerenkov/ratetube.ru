#!/usr/bin/env bash

# Обновление системы
yum -y update

# Установка nginx
yum -y install epel-release
yum -y localinstall https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
yum -y install yum-plugin-replace
yum -y install policycoreutils-python sshpass git
yum -y install nginx
systemctl start nginx
systemctl enable nginx

# Установка PHP-FPM
yum -y install php71w php71w-fpm php71w-common php71w-gd php71w-mbstring php71w-xml php71w-mcrypt php71w-mysql php71w-opcache php71w-cli
systemctl start php-fpm
systemctl enable php-fpm

# Настройки PHP
rm -rf /etc/localtime
ln -s /usr/share/zoneinfo/Europe/Moscow /etc/localtime
sed -i "s/;date.timezone.*/date.timezone = Europe\\/Moscow/g" /etc/php.ini
sed -i "s/; max_input_vars.*/max_input_vars = 100000/g" /etc/php.ini
sed -i "s/short_open_tag = .*/short_open_tag = On/g" /etc/php.ini
sed -i "s/display_errors = .*/display_errors = On/g" /etc/php.ini
sed -i "s/;realpath_cache_size = .*/realpath_cache_size = 4096k/g" /etc/php.ini
sed -i "s/opcache.max_accelerated_files=.*/opcache.max_accelerated_files=100000/g" /etc/php.d/opcache.ini

# Настройка веб-сервера
echo "
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log;
pid /run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    log_format main  '\$remote_addr - \$remote_user [\$time_local] \"\$request\" '
                     '\$status \$body_bytes_sent "\$http_referer" '
                     '\"\$http_user_agent\" \"\$http_x_forwarded_for\"';

    access_log  /var/log/nginx/access.log  main;

    sendfile            off; # bug in VirtualBox
    tcp_nopush          on;
    tcp_nodelay         on;
    keepalive_timeout   65;
    types_hash_max_size 2048;

    include             /etc/nginx/mime.types;
    default_type        application/octet-stream;

    # Load modular configuration files from the /etc/nginx/conf.d directory.
    # See http://nginx.org/en/docs/ngx_core_module.html#include
    # for more information.
    include /etc/nginx/conf.d/*.conf;

    server {
        listen 80 default_server;
        server_name  _;
        root /var/www/html/web;
        index index-slave.php;

        include /etc/nginx/default.d/*.conf;

        location / {
            try_files \$uri \$uri/ @urlrewrite;
        }

        location ~ \.php$ {
            try_files \$uri @urlrewrite;
            include /etc/nginx/fastcgi_params;
            fastcgi_pass  unix:/var/run/php-fpm/php-fpm.sock;
            fastcgi_index index-slave.php;
            fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            fastcgi_read_timeout 300;
        }

        location @urlrewrite {
            include /etc/nginx/fastcgi_params;
            fastcgi_pass  unix:/var/run/php-fpm/php-fpm.sock;
            fastcgi_index index-slave.php;
            fastcgi_param SCRIPT_FILENAME \$document_root/index-slave.php;
            fastcgi_read_timeout 300;
        }
    }
}
" > /etc/nginx/nginx.conf

# Запуск PHP-FPM от пользователя nginx
sed -i "s/listen = 127.0.0.1:9000/listen = \/var\/run\/php-fpm\/php-fpm.sock/g" /etc/php-fpm.d/www.conf
sed -i "s/user = apache/user = nginx/g" /etc/php-fpm.d/www.conf
sed -i "s/group = apache/group = nginx/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.owner = nobody/listen.owner = nginx/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.group = nobody/listen.group = nginx/g" /etc/php-fpm.d/www.conf

# Настройка доступа по SSH
mkdir /root/.ssh
echo "-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAv4SzscxZMXR3lOO5wcuInvVtPa+BIkMI40Rb9aT7vParcM5Z
l5SQMgvifQgjMewL+DIKnf9OqmHEXqcOO/lyuGaAWaCreatcRoWoENO0s/7hMmQC
+j6AvS6WsjGRCPUUoGR92CJ5aVI8RsF0tnS8h1VdhbrbbNiq/i2C7a//qrwF6s9x
UDdvaB1ctFzpsKs5Us0iH+c0FD/SgdbXgna8Q3OPAk9tlzKJO7tvBym82pM/8jnF
6ZipqfIAKGv2sRa8KLd2OIq/KW237bDneEDuzozBEoRSn1vFiXNLFNAEdxAuELoC
8MFQYIjc3YZAOflZm4xJnTk3oGHklszp1YybKQIDAQABAoIBAEqe6uDozQve5ETn
4dWndwjweWrief8efVUHqojwioFa3vup+vB7mx9U0B+FTylBXnyLCuX6tuzeAQQc
NQibLd65WWMSnh1e7iowI4bC5hKHybi3jQ1x0vljMKYnd+o0i5/e58WR0Rp/RysO
b2oz280jLrhPUPV9CkrU8sGnpIch497ymkO0Bq+u56hGYJkONGSJr10h7IV8qPR2
tRceQpiiNP73CC1P4u3sb+Tow3uKPZw1RtRYgWPW3zHOmBZzgTtCDvPcmRc1qZry
okDHV/UE48d6fJTF5BQQFCfKvX6vnmhHbfhUuaobC8R7DbU+a3q9oqHYNCbEG3LT
U6OSbz0CgYEA9tZTnqYjn0tn/2dCF+t+Fr2ft/En7BHu+AFqKYzm+1S7/hS0IsNC
B7fkUA2qkEyVV8heM8yqUYv+IHlgc1ZlVR7q5LkW72rDE/7xcYoxVZhLEyKJQrHg
D23JqYvr3brxi7CfNZs6wywmvm4JXS2G+Z/WGvxPeqWlIQG3GMUr87MCgYEAxqCv
V3LN3IbNSrPBSxqdfvMnvclloIorQO405wF9AkeLaF+bFJERrX+iz1vmaYlVL97/
rpvwrssreX9ds+1Zc82VU3N8i02NRIOkwDbjz1iD3Afg9JDLDebESVeTuWcprm/i
6u5/PjnMx+47P5Shm42Kb/WrfX0Q0LTT/B/Td7MCgYEAwILN+rjmpXEhLg+xe4hd
8Yx4yfQaR8KA1wn7a2aKK1Cdwf8Rst8IW46vUUQnV51zCGCsH8gquajuTRN9BtdF
9spDNpmoapegh7LZSc0WxwQc4VKZLNwfvMjKdCI9ldQcWO8qbJuhi+CeYvzc7r/4
Oi3PxYIs1qHkFMcKrxXwPoMCgYAx0FKpJ82hJN2Pgo1TfJVLJUguPLgUDxLR8euq
k6D6VV8NCg0ml0tLq9r1DiM3DI4kt1SAQfOWorWAfwTM/xWUCVcN2sS9WvG24R0M
Z7eyZIyNPhyYuUdzcRCBJEmUEd9ONBlAuheHT4+gBIsvYuM008aIVaBwlFEHRpJz
hLfQKQKBgQC/ZODSYWLs0LAuk2p/oU8Z5cRgB7KSy6FDEdiH437jXYEWlim7F/0p
MjtNhKrNrM/M7ca76va+7EaCvnwtqEBUAVmcIJDgrnXlc7m7wz3T79C0R+GsaFla
zS31UYyCjt7bYtEIQqIePUS9LsxlltmnLnaPQ4VGerR5cu66cbIJ0A==
-----END RSA PRIVATE KEY-----
" >  /root/.ssh/id_rsa
chmod 0600 /root/.ssh/id_rsa
ssh-keyscan ratetube.ru >> /root/.ssh/known_hosts

# Настройка SELinux
mkdir -p /var/www
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www(/.*)?"
restorecon -R /var/www
chown -R nginx:nginx /var/lib/php/session
chown -R nginx:nginx /var/lib/nginx

semanage permissive -a httpd_t
setsebool -P httpd_can_network_connect on

# Перезапуск сервисов
systemctl restart nginx
systemctl restart php-fpm

# Получение файлов из репозитория
cd /var/www
git clone ssh://root@ratetube.ru/var/git html
chown -R nginx:nginx /var/www

# Установка Composer
cd /tmp
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
mkdir -p /root/.config/composer
echo '{
    "github-oauth": {
        "github.com": "5a07804b8b4b3a25750cd446f42451d6701e8fc8"
    }
}
' > /root/.config/composer/auth.json
composer global require "fxp/composer-asset-plugin:^1.2.0"

# Обновление всех библиотек
cd /var/www/html
composer update
