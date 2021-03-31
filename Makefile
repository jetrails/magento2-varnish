NAMESPACE=JetRails_Varnish
VERSION=$(shell git describe --tags `git rev-list --tags --max-count=1`)

.PHONY: bump deploy watch package clean nuke

bump: ## Bump version in source files based on latest git tag
	VERSION=$(VERSION); find ./src -type f -iname "*.php" -exec sed -E -i '' "s/([\t ]+\*[\t ]+@version[\t ]+)(.*)/\1$$VERSION/g" {} +
	VERSION=$(VERSION); sed -E -i '' "s/(Version-)(.+)(-lightgrey)/\1$$VERSION\3/g" ./README.md
	VERSION=$(VERSION); sed -E -i '' "s/(<version>)(.+)(<\/version>)/\1$$VERSION\3/g" ./src/app/code/JetRails/Varnish/etc/config.xml
	VERSION=$(VERSION); sed -E -i '' "s/(\"version\": \")(.+)(\")/\1$$VERSION\3/g" ./composer.json

deploy: ## Deploy code to public_html directory
	mkdir -p ./public_html
	rsync -uavq ./src/ ./public_html/

sync: deploy ## Intermittently sync code to public_html directory
	fswatch -o ./src | xargs -n1 -I{} make deploy

package: bump ## Package into archive file
	mkdir -p ./dist
	cd src; tar -czvf ../dist/$(NAMESPACE)-$(VERSION).tar.gz *

clean: ## Remove generated files and folders
	rm -rf ./dist

nuke: clean ## Remove generated & deployment data
	rm -rf ./node_modules ./data ./public_html

help: ## Display available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
