## Installation Process for Debian based systems
We are now going to install all the required dependencies for the Popcorn Time API.<br>
Make sure that all these commands are ran as the `root` user.<br>
You can do this by running `sudo su`.<br>

### Important Note
Please note that we will be completely removing the firewall for this installation to work correctly.<br>
This is not recommended for production environments, but is required for this installation to work correctly.<br>
The machine that you are installing this on should be a dedicated machine, and not a shared server.

#### Let's Update the current repositories and install the required dependencies
Firstly, we are going to update the current repositories and install the required dependencies.<br>
```sh
apt update -y
apt -y install software-properties-common curl apt-transport-https ca-certificates gnupg lsb-release git
```

#### Remove The Firewall
Next, we are going to remove the firewall, this will allow the Popcorn Time API to work correctly.<br>
```sh
apt remove -y ufw
```

#### MariaDB & Redis
Next, we are going to install MariaDB and Redis, these are the databases that the Popcorn Time API will use.<br>
```sh
sh -c "DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-server mariadb-client mariadb-common redis-server"
systemctl enable --now mariadb redis-server
```
We are now going to secure the MariaDB installation, this will allow us to set a root password for the database.<br>
```sh
mysql_secure_installation
```
You will be prompted to set a root password, please do this and keep a note of the password.<br>
You will also be prompted to remove anonymous users, disable remote login, and remove the test database.<br>
Please answer `Y` to all of these prompts.<br>

#### PHP 8.1
After that, we are going to install PHP 8.1.<br>
```sh
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/sury-php.list
wget -qO - https://packages.sury.org/php/apt.gpg | sudo apt-key add -
apt -y update
apt install -y php8.1 php8.1-{cli,common,gd,mysql,mbstring,bcmath,xml,fpm,curl,zip,redis}
systemctl enable --now php8.1-fpm
```

#### Composer
Next, we are going to install Composer and it's requirements, this is the package manager for PHP.<br>
```sh
apt install -y zip unzip tar
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

#### Tor
Next, we are going to install Tor, this is the proxy that the Popcorn Time API will use to scrape the torrent sites.<br>
Tor does not need any configuration, and will work out of the box for our use case.<br>
```sh
apt install -y tor
systemctl enable --now tor
```

#### Elasticsearch
Next, we are going to install Elasticsearch, this is the search engine that the Popcorn Time API will use.<br>
```sh
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-8.x.list
apt update -y && apt install -y elasticsearch
```
We are now going to configure Elasticsearch to disable the security features and ssl.<br>
Open the file `/etc/elasticsearch/elasticsearch.yml` and make sure security and ssl is disabled:<br>
```yaml
xpack.security.enabled: false

xpack.security.http.ssl:
  enabled: false
```
Now, we can start Elasticsearch.<br>
```sh
systemctl enable --now elasticsearch
```

#### Nginx
Next, we are going to install the webserver & SSL certificates. Replacing `<domain>` with your domain.<br>
```sh
### Install Nginx & Certbot
apt install -y nginx python3-certbot-nginx
systemctl enable --now nginx

### Generate SSL Certificates, replace <domain> with your domain
certbot certonly --nginx -d <domain>
```
Now, you should paste the contents below into the file `/etc/nginx/sites-enabled/popcorntime.conf`.<br>
```conf
server_tokens off;

server {
    listen 80;
    server_name <domain>;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name <domain>;

    ssl_certificate /etc/letsencrypt/live/<domain>/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/<domain>/privkey.pem;

    root /var/www/popcorntime/public;
    index index.html;
    
    access_log /var/log/nginx/popcorntime.app-access.log;
    error_log  /var/log/nginx/popcorntime.app-error.log error;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Install The Popcorn Time API
Finally, we are going to configure & install the Popcorn Time API.<br>
We are going to download all the files first.<br>
```sh
cd /var/www/
git clone https://github.com/popcorn-time-ru/popcorn-ru
mv popcorn-ru popcorntime
cd popcorntime
chown -R www-data:www-data /var/www/popcorntime/*
chown www-data:www-data /var/www/popcorntime/.env
chmod 400 /var/www/popcorntime/.env
```
Now we are going to configure the Popcorn Time API `.env` file to our needs.<br>
Firstly, login to your MariaDB database and note down the server version number.<br>
```sh
### First run this command, then enter the password when prompted
mysql -u root -p

### Then you can run this command to exit mariadb
exit
```

After that, we are going to configure the `.env.local` file.<br>
Make sure to change the `APP_SECRET` to a random string.<br>
Update `password1234` to the password you set for the root mariadb user and set the correct server version.<br>
You can get a TMDB API key [here](https://www.themoviedb.org/settings/api). Make sure to use the - `v3 Auth Key`<br>
You can get a TRAKT API key [here](https://trakt.tv/oauth/applications/). Make sure to use the - `Client ID`<br>
```env
APP_ENV=prod
APP_SECRET=ThisTokenIsNotSoSecretChangeIt
DATABASE_URL=mysql://root:password1234@127.0.0.1:3306/popcorn?serverVersion=mariadb-10.5.1
TMDB_API_KEY=
TRAKT_KEY=
```

<i>Please note that these commands should be run once at a time</i>
```sh
systemctl restart php8.1-fpm nginx
composer install
bin/console doctrine:database:create
bin/console doctrine:schema:create
bin/console enqueue:setup-broker
```

### Initialise Popcorn Time Database
We will now initialise the Popcorn Time database with the default data and set up the cron jobs.<br>
```sh
(crontab -l ; echo "0 0 1 */3 * /var/www/popcorntime/bin/console spider:run --all")| crontab -
(crontab -l ; echo "0 0 * * * /var/www/popcorntime/bin/console spider:run --all --last=48")| crontab -
(crontab -l ; echo "0 8 * * 1 /var/www/popcorntime/bin/console update:stat")| crontab -
(crontab -l ; echo "0 3,11,19 * * * /var/www/popcorntime/bin/console update:trending")| crontab -
(crontab -l ; echo "0 1 * * * /var/www/popcorntime/bin/console update:syncOld 500 --days-check=180 --days-delete=360")| crontab -
(crontab -l ; echo "0 23 * * * /var/www/popcorntime/bin/console cache:clear")| crontab -
(crontab -l ; echo "9  * * * * cd /var/www/popcorntime/ && killall -9 php")| crontab -
(crontab -l ; echo '10  * * * * cd /var/www/popcorntime/ && pgrep -c -f enqueue:consume || bin/console enqueue:consume --time-limit="now + 55 minutes" --no-debug --memory-limit=200')| crontab -
bin/console spider:run --all
```
