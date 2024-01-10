
## Docker
We have a dockerized development environment for your exploratory pleasure.

*All docker commands expect to be run from the `dev_containers` directory!*

Prerequisites: 
* Install docker
  * We use compose schema 3.9, which has been around since ~Dec 2022 and should be supported by:
    * Docker engine >= 20.10.0
    * Docker compose >= 1.27.1
* Set up environment variable files:
  * `dev_containers/.env`:
    * `WEBHOOKS_PORT=5555` (Port exposing webhook server to your local machine)
    * `DB_ROOT_PASSWORD=denhac` or a password of your choice
    * `DB_DATABASE=webhooks` or a database name of your choice
    * `DB_USERNAME=denhac` or a username of your choice
    * `DB_PASSWORD=denhac` or a password of your choice
    * `COMPOSE_PROJECT_NAME=denhac-webhooks` (optional)
  * `<project_root>/.env`:
    * `DB_CONNECTION=mysql`
    * `DB_HOST=db` <- *Important! Not a typo!* docker dns will resolve `db` to the db container. 
    * `DB_DATABASE=webhooks` (match above)
    * `DB_USERNAME=denhac` (match above)
    * `DB_PASSWORD=denhac` (match above)



### Start the containers: 
Remember to `cd dev_containers`, then run one of:
* `docker compose up --build` 
  * builds, then runs. Verbose output. Simple and reliable.
* `docker compose watch` 
  * Rebuilds when dependencies or configurations change (most of the time)
  * Convenient, but...
  * Does not output logs
  * Won't rebuild on all changes

Keep an eye on the terminal/docker desktop outputs. Once all containers show `Running` or `Started`, your application should be ready.

Connect locally at [localhost:5555](localhost:5555) (or `WEBHOOKS_PORT` in `dev_containers/.env`)

Connect over ngrok at `http://<yourdomain>`. The ngrok connection will _always use port 80_, so no need to enter that.

**If working properly**, you'll see a blue page with a 404 error, since we don't have any content defined for that path. Warm welcome, right?

### Other docker commands:

`docker exec -it <container name> <command>` will connect to a *running* container. If you want an interactive session, use a command like `bash`.

**Note**: `<container name>` follows these patterns:
  * `<docker compose name>-<service name>-<container number>` (default)
  * `<docker compose name>-<container_name>` if `container_name` set for service in `docker-compose.yml`.
  * Where `<docker compompose name>` defaults to `dev_containers` (dirname) unless `COMPOSE_PROJECT_NAME` set in `dev_containers/.env`

EG:
  * `denhac-webhooks-app-1` (Running the laravel project)
  * `denhac-webhooks-webhooks-db` (Running laravel project's db)


## Name resolution/Tunneling
Any externally hosted services (eg, slack) that you want to connect to your locally hosted dev server will need a way to reach your server.
An easy way to accomplish this is to use a service called [`ngrok`](https://dashboard.ngrok.com/get-started/setup) to quickly establish a reverse proxy that exposes your dev environment to the 
internet with a static name. 

The docker stack already includes a container with the ngrok agent installed! All you need to do is sign up for an account and set your token as `NGROK_AUTHTOKEN` in `dev_containers/.env`. Then open your ngrok account and look under "agents" to find the public name of your app.

**Note:** Under the free tier of ngrok, you'll be assigned a alpha-numerical subdomain that *will change* every time the container restarts. With a paid subscription, you can get a permanent subdomain of your choice. Set this as `NGROK_DOMAIN` in `dev_containers/.env` to use this feature.


## Slack
You'll need a slack workspace where you have permission to install and manage apps, and won't disrupt anyone if something goes awry.
You can [create a free workspace](https://slack.com/get-started#/create) for yourself, such as `dev-denhac-<your-username>`

### Membership App
1. While signed into a slack account with access to your test workspace, go to [api.slack.com/apps](api.slack.com/apps) and click `Create New App`.
2. Select `From Scratch`
3. Give your app a name such as `Membership Bot` and select your test workspace. Click `Create App`.
4. Select the `Slash Commands` feature.
5. Enter `/membership` for the command
6. Add the webhook url. This will be `<your.ngrok.hostname>/slack/membership`. 
7. Complete the slash command setup
8. Enable "Interactivity". This is required for the modals to function.
  - Under "Features", open the "Interactivity & Shortcuts" tab
  - Set the interactivity toggle to on
  - Scroll down to "Select Menus" and enter `<your.ngrok.hostname>/slack/options` in the "Options Load URL".
  - Failure to complete these steps may result in the `/membership` command not loading a modal, or having an empty options list.
9. Install the app into your workspace
  - In the app dashboard's sidebar, find the `Install App` tab
  - Click `Install to Workspace` and agree to installation
  - The bot should now show up under `Apps` in your slack workspace
10. Configure secrets. In your `<application_root>/.env`, add:
  - `SLACK_SPACEBOT_API_TOKEN=` the "Bot User OAuth Token" from the "OAuth & Permissions" tab
    - This token is used to sign requests that we send to slack, such as the ones that create and update the modals.
    - Note that the membership command uses the `SPACEBOT` variables, not the `MANAGEMENT` ones. 
  - `SLACK_SPACEBOT_API_SIGNING_SECRET=` The "Signing Secret" from the "App Credentials" section under the "Basic Information" tab.
    - This secret is used to verify that requests to the `/slack/*` endpoints were in fact sent by slack (and originate from our workspace).
11. Test the integration
  - Send `\membership` in `#general` or some other channel
  - If you get something like `\membership is not a valid command`, the bot was not configured properly in slack, or it was not added to your workspace
  - If you get `/membership failed with the error "dispatch_failed", you have successfully installed the app, but it's not able to reach your webhook server. Verify that ngrok and the dev server are running, and that the domain name in the app configuration is accurate.


## Orientation
### Logging
Import Laravel's logging util with `use Illuminate\Support\Facades\Log;`.
Logging is configured at `app/config/logging.php` and with the env variable `LOG_CHANNEL`.
The `stack` channel (default) is a composite channel defined in `logging.php`. It currently is an alias to the `single` channel, but offers support for multiple channels.

Per the configuration of `single`, logs are written to `storage/logs/laravel.log`. 
`storage/` is a bind mount in dev, so files are synchronized to your development directory.
This enables you to watch the application logs with `tail -f storage/logs/laravel.log` run from a terminal on your host machine (from the application root).