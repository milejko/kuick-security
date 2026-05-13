PHP_VERSION ?= 8.5
IMAGE_NAME := kuickphp/security

.PHONY: *

test:
	$(eval CI_TAG := $(IMAGE_NAME):$(PHP_VERSION)-$(shell date +%s%N))
	docker build --build-arg=PHP_VERSION=$(PHP_VERSION) --tag $(CI_TAG) .
	docker run --rm -v ./:/var/www/html $(CI_TAG) sh -c "composer up && composer fix:phpcbf && composer test:phpunit"
	docker image rm $(CI_TAG)
