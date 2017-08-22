start: api

test:
	make testunit && make testapi

testunit: composer rmTestDb upTestDb yiimigratetestDb yiimigratetestDblocal rmTestDb
	docker-compose run --rm cli bash -c 'MYSQL_HOST=testDb MYSQL_DATABASE=test ./vendor/bin/codecept run unit'

testapi: upTestDb yiimigratetestDb yiimigratetestDblocal
	docker-compose up -d zxcvbn
	docker-compose run --rm apitest

api: upDb composer yiimigrate yiimigratelocal
	docker-compose up -d api zxcvbn cron phpmyadmin

composer:
	docker-compose run --rm --user="0:0" cli composer install

composerupdate:
	docker-compose run --rm --user="0:0" cli composer update

dockerpullall:
	docker pull phpmyadmin/phpmyadmin:latest
	docker pull silintl/data-volume:latest
	docker pull silintl/mariadb:latest
	docker pull silintl/php7:latest
	docker pull wcjr/zxcvbn-api:1.1.0

rmDb:
	docker-compose kill db
	docker-compose rm -f db

upDb:
	docker-compose up -d db

yiimigrate:
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

yiimigratelocal:
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --migrationPath=console/migrations-local/ --interactive=0

basemodels:
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

yiimigratetestDb:
	docker-compose run --rm cli bash -c 'MYSQL_HOST=testDb MYSQL_DATABASE=test whenavail testDb 3306 100 ./yii migrate --interactive=0'

yiimigratetestDblocal:
	docker-compose run --rm cli bash -c 'MYSQL_HOST=testDb MYSQL_DATABASE=test whenavail testDb 3306 100 ./yii migrate --migrationPath=console/migrations-test/ --interactive=0'

rmTestDb:
	docker-compose kill testDb
	docker-compose rm -f testDb

upTestDb:
	docker-compose up -d testDb

bounce:
	docker-compose up -d api

clean:
	docker-compose kill
	docker-compose rm -f
