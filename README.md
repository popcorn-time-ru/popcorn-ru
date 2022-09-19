# Popcorn Time Ru API server

It is an API server, you don't need this to watch films on the client.<br>
Just download the client from project page (https://popcorn-time.ga/build/).
For english films you may simply put https://popcorn-time.ga/ as the Custom API Server in the advanced settings if you use the official Popcorn Time app (0.4.4+git).<br>
However, for films in other languages, you need the fork version of the client.

If you want to add some trackers or create a self-hosted server then you can fork it.

It is hosted in free tier google cloud - search is slow - no elastic and 580 mb ram.

No anime for at the moment, please extend the api if you know an api for anime like TMDB for films and shows.

## Deployment
It is a standard symfony v5 application that requires:<br>
`nginx, php-8.1, mariadb, cron, tor, elasticsearch, git, redis`<br>

It is highly recommended that you configure a nginx cache.

### Installation guide for RHEL 8/9 based systems
You can find the installation guide for RHEL 8/9 based systems [here](Documentation/RHEL-8-9.md).

### Installation guide for Debian 10/11 based systems
You can find the installation guide for Debian 10/11 based systems [here](Documentation/Debian-10-11.md).

### Configuring Search
You can configure search in `config/services.yaml`.

### Issues with spiders & tor
If you have issues with some spiders, setup tor node and configure tor proxy.<br>
Refer to the respective installation guides above for tor<br>

### Initialise the database
You can run the following command to initialise the database.<br>
```sh
bin/console spider:run --all
```

### Grafana dashboard
Additionally, you may set up grafana and use `grafana.json` for the app dashboard.<br>

### Ansible install
The deployment playbook for a single server is located in the deploy folder.
