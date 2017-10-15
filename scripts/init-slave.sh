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
sed -i "s/short_open_tag*/short_open_tag = On/g" /etc/php.ini
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
        root /var/www/html;
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
sed -i "s/listen = 127.0.0.1:9000/listen = \\/var\\/run\\/php-fpm\\/php-fpm.sock/g" /etc/php-fpm.d/www.conf
sed -i "s/user = apache/user = nginx/g" /etc/php-fpm.d/www.conf
sed -i "s/group = apache/group = nginx/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.owner = nobody/listen.owner = nginx/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.group = nobody/listen.group = nginx/g" /etc/php-fpm.d/www.conf

mkdir -p /var/www/html
chown nginx:nginx /var/www/html

# Настройка SELinux
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html(/.*)?"
restorecon -R /var/www/html
chown -R nginx:nginx /var/lib/php/session
chown -R nginx:nginx /var/lib/nginx

semanage permissive -a httpd_t
setsebool -P httpd_can_network_connect on

# Перезапуск сервисов
systemctl restart nginx
systemctl restart php-fpm