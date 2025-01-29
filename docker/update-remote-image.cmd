@echo off


set registry="registry.gitlab.com"

rem docker logout "%registry%"
docker login "%registry%"


set version="7.4"
set image_name=ntbe-php
set tag=%version%-1.0
set registry_prefix=registry.gitlab.com/ntb-entreprise-group/dodo-empire/dodo-shop/dodo-shop-api

set src_image="%registry_prefix%/%image_name%:latest"

rem docker pull %src_image%

docker build -t %src_image% .
rem docker pull %src_image%



docker tag %src_image% "%registry_prefix%/%image_name%:%tag%"
docker tag %src_image% "%registry_prefix%/%image_name%:latest"

docker push "%registry_prefix%/%image_name%:%tag%"
docker push "%registry_prefix%/%image_name%:latest"


