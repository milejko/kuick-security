.PHONY test:
	$(eval CI_TAG := testing)
	docker build --target=test-runner --tag $(CI_TAG) .
	docker run --rm -v ./:/var/www/html $(CI_TAG) sh -c "composer up && composer fix:phpcbf && composer test:phpunit"
	docker image rm $(CI_TAG)
