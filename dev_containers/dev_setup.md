## Name resolution/Tunneling
Any externally hosted services (slack) that you want to connect to your locally hosted dev server will need a way to reach your server.
An easy way to accomplish this is to use a service called [`ngrok`](https://dashboard.ngrok.com/get-started/setup) to quickly establish a reverse proxy that exposes your dev environment to the internet with a static name.

## Docker

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
Import Laravel's logging util with `use Illuminate/Support/Facades/Log;`.
Logging is configured at `app/config/logging.php` and with the env variable `LOG_CHANNEL`.
The `stack` channel (default) is a composite channel defined in `logging.php`. It currently is an alias to the `single` channel, but offers support for multiple channels.

Per the configuration of `single`, logs are written to `storage/logs/laravel.log`. 
`storage/` is a bind mount in dev, so files are synchronized to your development directory.
This enables you to watch the application logs with `tail -f storage/logs/laravel.log` run from a terminal on your host machine (from the application root).