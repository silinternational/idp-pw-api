start: api

test: testlocal testintegration

testlocal: testunit testapi

testunit: composer rmTestDb upTestDb broker yiimigratetestDb
	docker-compose run --rm unittest

testapi: upTestDb yiimigratetestDb
	docker-compose kill broker
	docker-compose up -d broker
	docker-compose run --rm apitest

testintegration:
	docker-compose run --rm integrationtest

api: upDb broker composer yiimigrate
	docker-compose up -d api zxcvbn phpmyadmin brokerpma emailpma

composer:
	docker-compose run --rm cli composer install

composershow:
	docker-compose run --rm cli bash -c 'composer show --format=json --no-dev --no-ansi --locked | jq ".locked[] | { \"name\": .name, \"version\": .version }" > dependencies.json'

composerupdate:
	docker-compose run --rm cli bash -c "composer update"
	make composershow

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

broker:
	docker-compose up -d broker

ldap:
	docker-compose up -d ldap

ldapload:
	docker-compose kill ldap
	docker-compose rm -f ldap
	docker-compose run --rm ldapload

bounce:
	docker-compose up -d api

clean:
	docker-compose kill
	docker-compose rm -f

raml2html: api.html

api.html: api.raml
	docker-compose run --rm raml2html

psr2:
	docker-compose run --rm cli bash -c "vendor/bin/php-cs-fixer fix ."
