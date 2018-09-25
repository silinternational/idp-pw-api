start: api

test:
	make testunit && make testapi

testunit: composer emailcron rmTestDb upTestDb brokerDb broker yiimigratetestDb yiimigratetestDblocal
	docker-compose run emailcron whenavail emaildb 3306 100 ./yii migrate --interactive=0
	docker-compose run --rm cli bash -c 'MYSQL_HOST=testDb MYSQL_DATABASE=test ./vendor/bin/codecept run unit'

# Run testunit first at least once. Otherwise, this will have 5 test failures.
testapi: upTestDb brokerDb broker yiimigratetestDb yiimigratetestDblocal
	docker-compose up -d zxcvbn
	docker-compose run --rm apitest

api: upDb composer yiimigrate yiimigratelocal
	docker-compose up -d api zxcvbn cron phpmyadmin

composer:
	docker-compose run --rm cli composer install

composerupdate:
	docker-compose run --rm cli composer update

dockerpullall:
	docker pull phpmyadmin/phpmyadmin:latest
	docker pull silintl/data-volume:latest
	docker pull silintl/mariadb:latest
	docker pull silintl/php7:latest
	docker pull wcjr/zxcvbn-api:1.1.0

email:
	docker-compose up -d email

emailcron:
	docker-compose up -d emailcron

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

brokerDb:
	docker-compose up -d brokerDb

broker:
	docker-compose up -d broker

bounce:
	docker-compose up -d api

clean:
	docker-compose kill
	docker-compose rm
