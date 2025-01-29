#!/bin/bash

# Define variables for the GitLab runner configuration
GITLAB_URL="https://gitlab.com/"
REGISTRATION_TOKEN="GR1348941MxxKqx5zXDMFqDzsMSU3"
RUNNER_NAME="NTBE_NODEJS_RUNNER"
TAG_LIST="NTBE_NODE_RUNNER,NTBE_NODEJS_RUNNER"

# Define variables for the Docker image
DOCKER_REGISTRY="registry.gitlab.com"
DOCKER_IMAGE_NAME="ntb-entreprise-group/dodo-empire/dodo-shop/dodo-shop-admin/ntbe-node"
DOCKER_IMAGE_TAG="latest"

# Log in to your GitLab registry
sudo docker login $DOCKER_REGISTRY

# Pull the Docker image from your GitLab registry
sudo docker pull "$DOCKER_REGISTRY/$DOCKER_IMAGE_NAME:$DOCKER_IMAGE_TAG"

# Create the GitLab runner container using the Docker image from your GitLab registry
sudo docker run -d --name nodejs-gitlab-runner --restart always \
  -v /var/run/docker.sock:/var/run/docker.sock \
  "$DOCKER_REGISTRY/$DOCKER_IMAGE_NAME:$DOCKER_IMAGE_TAG" \
  register --non-interactive \
  --url "$GITLAB_URL" \
  --registration-token "$REGISTRATION_TOKEN" \
  --executor docker \
  --docker-image "$DOCKER_REGISTRY/$DOCKER_IMAGE_NAME:$DOCKER_IMAGE_TAG" \
  --name "$RUNNER_NAME" \
  --tag-list "$TAG_LIST" \
  --docker-volumes /var/run/docker.sock:/var/run/docker.sock

# Start the GitLab runner container
sudo docker start nodejs-gitlab-runner
