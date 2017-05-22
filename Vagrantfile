# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
    config.vm.box = "centos/7"
    # boxcutter/centos72
    #config.vbguest.auto_update = false

    config.vm.network "forwarded_port", guest: 8080, host: 8080

    config.vm.synced_folder ".", "/home/vagrant/sync", disabled: true
    config.vm.synced_folder ".", "/vagrant", type: "virtualbox"

    config.vm.provider "virtualbox" do |vb|
        # Customize the amount of memory on the VM:
        vb.memory = "1024"
        vb.cpus = 2
        vb.name = "ratetube.ru"
    end

    config.vm.provision "shell", inline: <<-'SHELL'
        # Обновление системы
        sudo yum -y update

        # Установка nginx
        sudo yum -y install epel-release
        sudo yum -y localinstall https://mirror.webtatic.com/yum/el7/webtatic-release.rpm
        sudo yum -y install yum-plugin-replace
        sudo yum -y install policycoreutils-python sshpass
        sudo yum -y install nginx
        sudo systemctl start nginx
        sudo systemctl enable nginx

        # Установка MySQL
        sudo yum -y install mariadb-server mariadb
        sudo systemctl start mariadb
        sudo systemctl enable mariadb

        # Установка PHP-FPM
        sudo yum -y replace php-common --replace-with=php71w-common
        sudo yum -y install php71w php71w-fpm php71w-common php71w-gd php71w-mbstring php71w-xml php71w-mcrypt php71w-mysql php71w-opcache php71w-cli
        sudo systemctl start php-fpm
        sudo systemctl enable php-fpm

        # Запуск PHP-FPM от пользователя vagrant
        sudo sed -i "s/listen = 127.0.0.1:9000/listen = \\/var\\/run\\/php-fpm\\/php-fpm.sock/g" /etc/php-fpm.d/www.conf
        sudo sed -i "s/user = apache/user = vagrant/g" /etc/php-fpm.d/www.conf
        sudo sed -i "s/group = apache/group = vagrant/g" /etc/php-fpm.d/www.conf
        sudo sed -i "s/;listen.owner = nobody/listen.owner = vagrant/g" /etc/php-fpm.d/www.conf
        sudo sed -i "s/;listen.group = nobody/listen.group = vagrant/g" /etc/php-fpm.d/www.conf

        # Настройки PHP
        sudo rm -rf /etc/localtime
        sudo ln -s /usr/share/zoneinfo/Europe/Moscow /etc/localtime
        sudo sed -i "s/;date.timezone.*/date.timezone = Europe\\/Moscow/g" /etc/php.ini
        sudo sed -i "s/; max_input_vars.*/max_input_vars = 100000/g" /etc/php.ini
        sudo sed -i "s/short_open_tag*/short_open_tag = On/g" /etc/php.ini

        # Настройка веб-сервера
        sudo echo "
        user vagrant;
        worker_processes auto;
        error_log /var/log/nginx/error.log;
        pid /run/nginx.pid;

        events {
            worker_connections 1024;
        }

        http {
            log_format main  '\$remote_addr - \$remote_user [$time_local] "\$request" '
                             '\$status \$body_bytes_sent "\$http_referer" '
                             '"\$http_user_agent" "\$http_x_forwarded_for"';

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
                listen 8080 default_server;
                server_name  _;
                root /home/vagrant/sync;
                index index.html index.htm index.php;

                include /etc/nginx/default.d/*.conf;

                location / {
                    try_files \$uri \$uri/ @urlrewrite;
                }

                location ~ \.php$ {
                    try_files \$uri @urlrewrite;
                    include /etc/nginx/fastcgi_params;
                    fastcgi_pass  unix:/var/run/php-fpm/php-fpm.sock;
                    fastcgi_index index.php;
                    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
                    fastcgi_read_timeout 300;
                }

                location @urlrewrite {
                    include /etc/nginx/fastcgi_params;
                    fastcgi_pass  unix:/var/run/php-fpm/php-fpm.sock;
                    fastcgi_index index.php;
                    fastcgi_param SCRIPT_FILENAME \$document_root/index.php;
                    fastcgi_read_timeout 300;
                }
            }
        }
        " > /etc/nginx/nginx.conf

        sudo sed -i "s/short_open_tag = .*/short_open_tag = On/g" /etc/php.ini
        sudo sed -i "s/display_errors = .*/display_errors = On/g" /etc/php.ini
        sudo sed -i "s/;realpath_cache_size = .*/realpath_cache_size = 4096k/g" /etc/php.ini
        sudo sed -i "s/opcache.max_accelerated_files=.*/opcache.max_accelerated_files=100000/g" /etc/php.d/opcache.ini

        # Монтирование папки
        sudo ln -fs /vagrant /home/vagrant/sync
        sudo chown -R vagrant:vagrant /home/vagrant/sync

        # Настройка SELinux
        sudo semanage fcontext -a -t httpd_sys_rw_content_t "/home/vagrant/sync(/.*)?"
        sudo restorecon -R /home/vagrant/sync
        sudo chown -R vagrant:vagrant /var/lib/php/session
        sudo chown -R vagrant:vagrant /var/lib/nginx

        sudo semanage permissive -a httpd_t
        sudo setsebool -P httpd_can_network_connect on

        # Перезапуск сервисов
        sudo systemctl restart nginx
        sudo systemctl restart php-fpm
        sudo systemctl restart mariadb

        sudo mysql -u root mysql -e'UPDATE user SET Password = PASSWORD("me-262-a1"); FLUSH PRIVILEGES;'

        # Периодический дамп БД в файл (на Windows - с преобразованием в CRLF)
        sudo mysql -u root -pme-262-a1 -e'CREATE DATABASE IF NOT EXISTS ratetube CHARACTER SET utf8 COLLATE utf8_unicode_ci'
        echo "*/2 * * * * mysqldump --skip-comments -u root -pme-262-a1 ratetube > /home/vagrant/sync/sql/db.sql" | crontab
    SHELL
end
