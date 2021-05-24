TARGET=origin/dev
DC_PARAMETERS=-f docker-compose.yml -f docker-compose.demo.yml
APP_ENV=demo
PROJECT_NAME=$(APP_ENV)_vocalizr
APP_CONTAINER=php_1
APP_SHELL=docker exec -it $(PROJECT_NAME)_$(APP_CONTAINER) /bin/sh -c
APP_BIN=app/console

update: force-pull
	make post-update

post-update: build restart

pull:
	git pull --ff-only

force-pull:
	git fetch --all
	git checkout --force "$(TARGET)"

restart: stop start

stop:
	docker-compose $(DC_PARAMETERS) -p $(PROJECT_NAME) down

start:
	docker-compose $(DC_PARAMETERS) -p $(PROJECT_NAME) up -d

build:
	if [ ! -d "vendor" ]; then composer install; fi;
	docker-compose $(DC_PARAMETERS) -p $(PROJECT_NAME) build

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