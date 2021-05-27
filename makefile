TARGET=origin/mayur_docker_run
DC_PARAMETERS=-f docker-compose.yml -f docker-compose.demo.yml
APP_ENV=demo
PROJECT_NAME=$(APP_ENV)_vocalizr
APP_CONTAINER=php_1
APP_SHELL=docker exec -it $(PROJECT_NAME)_$(APP_CONTAINER) /bin/sh -c
APP_BIN=bin/console

#update: force-pull
#	make post-update

post-update: build restart

#pull:
#	git pull --ff-only

#force-pull:
#	git fetch --all
#	git checkout --force "$(TARGET)"

restart: stop start

stop:
	docker-compose $(DC_PARAMETERS) -p $(PROJECT_NAME) down

start:
	docker-compose $(DC_PARAMETERS) -p $(PROJECT_NAME) up -d

rebuild:
	docker-compose build --no-cache --pull

build :
	docker-compose up --build

up :
	docker-compose up

down :
	docker-compose down

#composer install
start :
	docker exec -it php_vocalizr composer install

#Start symfony server
start :
	docker exec -it php_vocalizr php bin/console server:run 0.0.0.0:8000

#Clear symfony cache
cache :
	docker exec -it php_vocalizr php bin/console cache:clear

#Show all running containers ips
ips :
	docker inspect -f '{{.Name}} - {{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $(docker ps -aq)

#Load the symfony data fixtures
fixtures :
	docker exec -it php_vocalizr php app/console d:f:l

shell:
	docker exec -it $(PROJECT_NAME)_$(APP_CONTAINER) /bin/sh

#	chmod 777 docker/mysql/data

# non-deployments targets
install: create-dirs
	composer dump-autoload

create-dirs:
	mkdir -p web/uploads/about/full
	mkdir -p web/uploads/about/thumb
	mkdir -p web/uploads/album_art
	mkdir -p web/uploads/avatar/large
	mkdir -p web/uploads/avatar/medium
	mkdir -p web/uploads/avatar/small
	mkdir -p web/uploads/background
	chmod -R 777 web/uploads/
