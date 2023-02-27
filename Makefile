init: docker-down-clear  \
	docker-pull docker-build docker-up \
	app-init
up: docker-up
down: docker-down
restart: down up

update-deps: app-composer-update restart

app-init: app-composer-install

app-yii-init: # инициализация yii framework
	docker-compose run --rm cli-php php init

app-composer-install:
	docker-compose run --rm cli-php composer install

app-composer-update:
	docker-compose run --rm cli-php composer update

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build --pull

parse:
	./app/bin/fct-parser.linux.amd64 -a -j -h -o ./app/data/
#	./app/bin/fct-parser.linux.amd64 -j -h -o ./app/data/ https://fct-altai.ru/qa/question/view-32983

indexer:
	docker-compose run --rm cli-php php yii index/indexer
