# denhac-webhooks
This repo holds some of our membership automation. It listens to webhooks from the main site and updates slack and google groups membership as well as updating access cards. It also listens to slack commands like `/membership` which allows us to customize that list based on the slack user that issued it, whether they're a board member, have an active subscription, etc.

# Architecture
Quite a bit of the code runs using event sourcing. The MembershipAggregate is the main entry point for a lot of the functionality as it decides based on subscription updates if someone is a member or not. Various projectors update models in the database and various reactors do things like send emails when needed.

## Helpful Things
### Aggregate Version Reset
Usually, you don't delete events from the database, but it can be useful to do so in the event of deprecation of events or fixing badly stored events. Sometimes, when that happens, the version numbers for the aggregate get out of wack. Here is a helper command to fix that:

```bash
php artisan event-sourcing:fix-aggregate-version
```

# Dev Environment
The webhooks Laravel server can be run locally to test and build new functionality. The easiest way to do this is using Sail, which is a docker compose wrapper built into the Laravel ecosystem.

It's worth noting that by its very nature, the webhooks system depends heavily on external services, and is not particularly useful on its own. Sail will stand up the core dependencies needed to run the Laravel server, but you'll need to either mock or connect other dev services for features you want to test, including the denhac.org Wordpress server (the source of all our user data and APIs), slack (for testing slack commands and notifications), quickbooks and stripe (for financial automations). You don't _need_ to provide these services to get the core server running, but that functionality won't work.

## Running Laravel Sail
1. Have docker, composer, and php installed on your system
2. `cd $ProjectDir`
3. `composer install --ignore-platform-reqs`
4. `cp ./.env.sail.example ./.env`:
  - OR: Update the following values in the `.env` file
    1. `DB_HOST=mysql`
    2. `REDIS_HOST=redis`
    3. `MAIL_HOST=mailpit`
5. `sail up -d`
6. `sail artisan key:generate`
7. `sail artisan migrate`
8. `sail artisan db:seed`
9. Profit

## Service Integrations
### Name resolution/Tunneling
Any externally hosted services (eg, slack) that you want to connect to your locally hosted dev server will need a way to reach your server.
There are many services available these days that let you quickly establish secure tunnels and reverse proxies to expose your development environment to the internet. These include [tailscale funnels](https://tailscale.com/kb/1223/funnel) (free), [cloudflare tunnels](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/) (free), and [`ngrok`](https://dashboard.ngrok.com/get-started/setup) (freemium)

### Slack
You'll need a slack workspace where you have permission to install and manage apps, and won't disrupt anyone if something goes awry.
You can [create a free workspace](https://slack.com/get-started#/create) for yourself, such as `dev-denhac-<your-username>`

#### Membership App
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