## TimoPHP

一个简单、快速、规范、灵活、扩展性好的PHP MVC+框架，主要用于API接口开发（前后端分离已是常态）

官网：http://timo.gumaor.com/

文档：http://timo.gumaor.com/document/

## 新建一个项目
```
> cd 你的项目存放目录
> composer create-project tomener/timo timo-demo
```

## 运行测试

### 进入命令行，执行一下命令
```
> cd timo-demo
> php timo serve
```

### 打开浏览器访问
> http://localhost:8090

## 你也可以配合apache、nginx来运行
### apache
```
<VirtualHost *:8090>
    ServerAdmin webmaster@gumaor.com
    DocumentRoot "D:\php\timo-demo\public"
    ServerName localhost
    ErrorLog "logs/timo-demo-error.log"
    CustomLog "logs/timo-demo-access.log" common

    <Directory "D:\php\timo-demo\public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        Header set Access-Control-Allow-Origin *
        Header set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Token"
        Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
        Header set Access-Control-Max-Age 86400
    </Directory>
</VirtualHost>
```

### nginx
```
server {
    listen       8090;
    server_name  localhost;

    charset utf-8;
    access_log off;

    root D:/php/timo-demo/public;

    error_page 404 /404.html;

    location /favicon.ico {
        log_not_found off;
        access_log off;
    }

    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Headers 'Token,Uptoken';
    add_header Access-Control-Allow-Methods GET,POST,OPTIONS;
    add_header Access-Control-Max-Age 86400;

    location ^~ / {
        if ($request_method = 'OPTIONS') {
            return 204;
        }
        index index.php index.html;
        if (!-e $request_filename) {
            rewrite ^/(.*)$ /index.php/$1 last;
        }
    }

    location ~ \.php(/|$) {
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass   unix:/dev/shm/php-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO        $fastcgi_path_info;
        include        fastcgi_params;
    }

    location ~ /\.ht {
        deny  all;
    }
}
```

## 入口模式

##### 多入口
一个应用一个入口，默认

##### 单一入口
所有应用共用一个入口