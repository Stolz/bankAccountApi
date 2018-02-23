# Bank account API

Sample [Lumen](https://lumen.laravel.com/) application running on Docker services.

Docker containers are based on [Alpine Linux](https://www.alpinelinux.org/about/) images which makes them smaller, more secure and more resource efficient.

The application implements an API to simulate the functionality of a basic bank account.

## Requirementes

You will need to install [Git](https://git-scm.com/downloads), [Docker](https://www.docker.com/get-docker) and [Docker Compose](https://docs.docker.com/compose/install/).

## Set up

Clone this repository

    git clone https://github.com/Stolz/bankAccountApi.git && cd bankAccountApi

Create configuration file for current environment

    cp lumen/.env.example lumen/.env

Install Lumen dependencies

    docker run --rm -it -v $PWD/lumen:/lumen -u $(id -u):$(id -g) composer install --ignore-platform-reqs --no-dev --working-dir=/lumen

Build images

    docker-compose build

Start containers

    docker-compose up

## Debugging

These two files have been included only for debugging purposes and they should be deleted for production environment:

- [lumen/public/phpinfo.php](http://localhost/phpinfo.php)
- [lumen/public/adminer.php](http://localhost/adminer.php?pgsql=db&username=lumen&db=lumen&ns=public) (use `secret` as password)

## Testing

Install Lumen testing dependencies

    docker run --rm -it -v $PWD/lumen:/lumen -u $(id -u):$(id -g) composer install --ignore-platform-reqs --working-dir=/lumen

Execute automatic tests

    docker exec -it bankaccountapi_app_1 ./vendor/bin/phpunit

A [Postman](https://www.getpostman.com/) collection for manual testing can be found at `lumen/Postman_collection.json` file.

