#!/bin/bash

versions='7.1 7.2 7.3 7.4 8.0 8.1 8.2'

for version in ${versions}; do
	echo "$(tput setaf 16)$(tput setab 2)Build docker image for PHP ${version}$(tput sgr 0)"
	docker build --rm -f "./docker/Dockerfile.${version}" -t babadzhanyan/php-unit:${version} . --push
done

