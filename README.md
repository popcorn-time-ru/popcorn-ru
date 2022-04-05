# Popcorn Time Ru API server

It's API server, you don't need this for watch films. Just download client from project page (https://popcorn-time.ga/build/).
For english films you may simply put https://popcorn-time.ga/ as the Custom API Server in the advanced settings
if you use the official Popcorn Time app (0.4.4+git), but for films in other languages you need fork client.

If you want add some trackers or create self server - then fork it.

It's hosted in free tier google cloud - search is slow - no elastic and 580 mb ram.
No anime now, please extend api if you know same api for anime as tmdb for films and shows.

## Deploy

It's standard symfony 5 application, need nginx + php7 + mysql + cron

Examples of config files you may found in deploy folder `deploy/roles/project/templates`

Highly recommended configure nginx cache

```
git clone
composer install
bin/console doctrine:database:create
bin/console doctrine:schema:create
bin/console enqueue:setup-broker
```

configure cron from `deploy/roles/project/templates/crontab.j2`

Add `.env.local` vars
```
TMDB_API_KEY=
TRAKT_KEY=
SENTRY_DSN=
```

Configure search in `config/services.yaml`

if you have issues with some spiders setup tor node and configure tor proxy

``` bin/conole spider:run --all ``` for init filling database

also you may setup grafana and use `grafana.json` for app dashboard

### Ansible install

Deploy playbook for single server in deploy folder
