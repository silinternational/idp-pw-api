clean:
	docker-compose kill
	docker-compose rm -f

start: api

api: composer yiimigrate yiimigratelocal
	docker-compose up -d api

composer:
	docker-compose run --rm cli composer install

composerupdate:
	docker-compose run --rm cli composer update

yiimigrate:
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

yiimigratelocal:
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --migrationPath=console/migrations-local/ --interactive=0

basemodels:
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

yiimigratetestDb:
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

yiimigratetestDblocal:
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --migrationPath=console/migrations-local/ --interactive=0


test: composer
	docker-compose kill testDb
	docker-compose up -d testDb
	docker-compose run --rm cli whenavail testDb 3306 100 ./yii migrate --interactive=0