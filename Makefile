VENDOR=JetRails
MODULE=Varnish
NAMESPACE=$(VENDOR)_$(MODULE)
NAMESPACE_PATH=$(VENDOR)/$(MODULE)
VERSION=$(shell git describe --tags `git rev-list --tags --max-count=1`)
MODULE_FILES=Block Console Controller Model etc Helper Logger Observer view registration.php
MODULE_FILES_EXTRA=composer.json LICENSE.md
SYNC_PATH=/var/www/app/code

.PHONY: bump deploy build sync package clean lint help

bump: ## Bump version in source files based on latest git tag
	VERSION=$(VERSION); find $(MODULE_FILES) -type f -iname "*.php" -exec sed -E -i '' "s/([\t ]+\*[\t ]+@version[\t ]+)(.*)/\1$$VERSION/g" {} +
	VERSION=$(VERSION); sed -E -i '' "s/(Version-)(.+)(-lightgrey)/\1$$VERSION\3/g" ./README.md
	VERSION=$(VERSION); sed -E -i '' "s/(\"version\": \")(.+)(\")/\1$$VERSION\3/g" ./composer.json
	VERSION=$(VERSION); sed -E -i '' "s/(<version>)(.+)(<\/version>)/\1$$VERSION\3/g" ./etc/config.xml
	VERSION=$(VERSION); sed -E -i '' "s/setup_version=\"([^\"]+)\"/setup_version=\"$$VERSION\"/g" ./etc/module.xml
	VERSION=$(VERSION); sed -E -i '' "s/schema_version=\"([^\"]+)\"/schema_version=\"$$VERSION\"/g" ./etc/module.xml
	VERSION=$(VERSION); sed -E -i '' "s/const MODULE_VERSION = \"([^\"]+)\"/const MODULE_VERSION = \"$$VERSION\"/g" ./Helper/Data.php

build: ## Copy over files to build directory
	rm -rf ./build/$(NAMESPACE_PATH)
	mkdir -p ./build/$(NAMESPACE_PATH)
	rsync -uavq $(MODULE_FILES) ./build/$(NAMESPACE_PATH)

deploy: build ## Deploy code to docker container
	docker compose exec magento rm -rf /bitnami/magento/app/code/$(NAMESPACE_PATH)
	docker compose exec magento mkdir -p /bitnami/magento/app/code/$(NAMESPACE_PATH)
	docker compose cp ./build/$(NAMESPACE_PATH) magento:/bitnami/magento/app/code/$(VENDOR)
	docker compose exec magento chown -R daemon:root /bitnami/magento/app/code

sync: ## Sync built module to remote server via rsync (requires SYNC_USER, SYNC_HOST)
	@test -n "$(SYNC_USER)" || { echo "Error: SYNC_USER is required"; exit 1; }
	@test -n "$(SYNC_HOST)" || { echo "Error: SYNC_HOST is required"; exit 1; }
	ssh $(SYNC_USER)@$(SYNC_HOST) "mkdir -p $(SYNC_PATH)/$(NAMESPACE_PATH)"
	rsync -avz --delete --chmod=Du=rwx,Dgo=rx,Fu=rw,Fgo=r ./build/$(NAMESPACE_PATH)/ $(SYNC_USER)@$(SYNC_HOST):$(SYNC_PATH)/$(NAMESPACE_PATH)/

package: bump ## Package into archive file
	rm -rf ./dist
	mkdir -p ./dist
	zip -r dist/$(NAMESPACE)-$(VERSION).zip $(MODULE_FILES) $(MODULE_FILES_EXTRA)

clean: ## Remove generated files and folders
	rm -rf ./dist ./build

lint: ## Report PHP 8.5 compatibility issues
	@test -d vendor || composer install
	php -d auto_prepend_file=phpcs-magento-stubs.php vendor/bin/phpcs -p --colors --standard=vendor/phpcompatibility/php-compatibility/PHPCompatibility --runtime-set testVersion 8.5 $(MODULE_FILES)

help: ## Display available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
