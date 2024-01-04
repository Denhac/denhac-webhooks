#! /usr/bin/env bash

# ngrok is an optional service that creates a tunnel from a local environment to a publicly available address.
# It is useful for allowing external services to send messages to our local webhook server.
# It also handles ssh automatically.
# If you wish to use this service, you'll need to create an account at ngrok.com and configure the below variables in dev_containers/.env
# For more information, see dev_setup.md

if [ -z $NGROK_AUTHTOKEN ]; then
    echo "\$NGROK_AUTHTOKEN not found in environment. Not running ngrok"
    exit 1
fi

# ngrok is running in a container connected to the docker network "laravel_network", per the docker-compose.yml configuration.
# It needs to connect to the nginx server, which is running on the "web" service connected to the same network and serving on :80
# Note that it matters only what port nginx is actually listening to within the container; the port exposed to the host machine
# by way of docker-compose mapping is irrelevant.
# Docker provides DNS resolution based on for containers based on `container_name`, which in this case is defined in docker-compose.yml.
HOST="laravel_web:80"

if [ -z $NGROK_DOMAIN ]; then
    # ngrok's ephermeral mode assigns you a random alphanumeric subdomain, which you can find in:
    #   - the log output
    #   - the local ngrok management portal (localhost:4040)
    #   - your ngrok account, under the agents section
    # This domain will change every time you restart the service.
    echo "\$NGROK_DOMAIN not found in environment. Starting ngrok in ephemeral domain mode."
    ngrok http $HOST
else
    # domain mode allows you to specify a static domain, but requires a paid subscription to ngrok
    echo "Starting ngrok at $NGROK_DOMAIN:80"
    ngrok http --domain=$NGROK_DOMAIN $HOST
fi
