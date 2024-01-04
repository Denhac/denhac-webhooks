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
6. Add the webhook url. This will be `<your.ngrok.hostname>:8080/slack/membership`. 
7. Complete the slash command setup
8. Install the app into your workspace
  - In the app dashboard's sidebar, find the `Install App` tab
  - Click `Install to Workspace` and agree to installation
  - The bot should now show up under `Apps` in your slack workspace
9. Test your new bot
  - Send `\membership` in `#general` or some other channel
  - If you get something like `\membership is not a valid command`, the bot was not configured properly in slack, or it was not added to your workspace
  - If you get `/membership failed with the error "dispatch_failed", you have successfully installed the app, but it's not able to reach your webhook server. Verify that ngrok and the dev server are running, and that the domain name in the app configuration is accurate.

