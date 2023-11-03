PORT ?= 8000
DATABASE_URL?=postgresql://aelitka:mypassword@localhost:5432/mydb
start:
	PHP_CLI_SERVER_WORKERS=5 DATABASE_URL=$(DATABASE_URL) php -S 0.0.0.0:$(PORT) -t public
install:
	composer install
validate:
	composer validate
lint:
	composer exec --verbose phpcs -- --standard=PSR12 public
lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 public