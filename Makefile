init: docker-down-clear  \
	docker-pull docker-build docker-up \
	app-init
up: docker-up
down: docker-down
restart: down up

update-deps: app-composer-update restart

app-init: app-composer-install app-wait-db app-migrations app-index-create app-index-indexer

app-yii-init: # инициализация yii framework
	docker-compose run --rm cli-php php init

app-composer-install:
	docker-compose run --rm cli-php composer install

app-composer-update:
	docker-compose run --rm cli-php composer update

app-wait-db:
	docker-compose run --rm cli-php wait-for-it app-postgres:5432 -t 30

app-migrations:
	docker-compose run --rm cli-php php yii migrate --interactive=0

app-index-create:
	docker-compose run --rm cli-php php yii index/create --interactive=0

app-index-indexer:
	docker-compose run --rm cli-php php yii index/indexer --interactive=0

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

parse-all:
	./app/bin/fct-parser.linux.amd64 -a -j -h -o ./app/data/
#	./app/bin/fct-parser.linux.amd64 -j -h -o ./app/data/ https://фкт-алтай.рф/qa/question/view-32983

parse-current:
	./app/bin/fct-parser.linux.amd64 -j -h -o ./app/data/

indexer:
	docker-compose run --rm cli-php php yii index/indexer

update-current:
	./app/bin/fct-parser.linux.amd64 -j -h -o ./app/data/site https://фкт-алтай.рф/qa/question/view-8162
	docker-compose run --rm cli-php php yii index/update-current-comments


update-current-comments:
	docker-compose run --rm cli-php php yii index/update-current-comments

build:
	docker --log-level=debug build --pull --file=app/frontend/docker/production/nginx/Dockerfile --tag=${REGISTRY}/fct-search-frontend:${IMAGE_TAG} app
#	docker --log-level=debug build --pull --file=app/frontend/docker/production/php-fpm/Dockerfile --tag=${REGISTRY}/fct-search-frontend-php-fpm:${IMAGE_TAG} app
	docker --log-level=debug build --pull --file=app/console/docker/production/php-cli/Dockerfile --tag=${REGISTRY}/fct-search-cli-php:${IMAGE_TAG} app

try-build:
	REGISTRY=localhost IMAGE_TAG=0 make build

push:
	docker push ${REGISTRY}/fct-search-frontend:${IMAGE_TAG}
#	docker push ${REGISTRY}/fct-search-frontend-php-fpm:${IMAGE_TAG}
	docker push ${REGISTRY}/fct-search-cli-php:${IMAGE_TAG}

deploy:
	ssh -o StrictHostKeyChecking=no deploy@${HOST} -p ${PORT} 'docker network create --driver=overlay traefik-public || true'
	ssh -o StrictHostKeyChecking=no deploy@${HOST} -p ${PORT} 'rm -rf site_${BUILD_NUMBER} && mkdir site_${BUILD_NUMBER}'

	envsubst < docker-compose-production.yml > docker-compose-production-env.yml
	scp -o StrictHostKeyChecking=no -P ${PORT} docker-compose-production-env.yml deploy@${HOST}:site_${BUILD_NUMBER}/docker-compose.yml
	rm -f docker-compose-production-env.yml

	ssh -o StrictHostKeyChecking=no deploy@${HOST} -p ${PORT} 'mkdir site_${BUILD_NUMBER}/secrets'
	#ssh -o StrictHostKeyChecking=no deploy@${HOST} -p ${PORT} 'mkdir site_${BUILD_NUMBER}/fct-site-parsed-files'
	scp -o StrictHostKeyChecking=no -P ${PORT} ${APP_DB_PASSWORD_FILE} deploy@${HOST}:site_${BUILD_NUMBER}/secrets/app_db_password
	scp -o StrictHostKeyChecking=no -P ${PORT} ${APP_MAILER_PASSWORD_FILE} deploy@${HOST}:site_${BUILD_NUMBER}/secrets/app_mailer_password
	scp -o StrictHostKeyChecking=no -P ${PORT} ${SENTRY_DSN_FILE} deploy@${HOST}:site_${BUILD_NUMBER}/secrets/sentry_dsn

	ssh -o StrictHostKeyChecking=no deploy@${HOST} -p ${PORT} 'cd site_${BUILD_NUMBER} && docker stack deploy --compose-file docker-compose.yml fct-search --with-registry-auth --prune'

deploy-clean:
	rm -f docker-compose-production-env.yml

rollback:
	ssh -o StrictHostKeyChecking=no deploy@${HOST} -p ${PORT} 'cd site_${BUILD_NUMBER} && docker stack deploy --compose-file docker-compose.yml fct-search --with-registry-auth --prune'
