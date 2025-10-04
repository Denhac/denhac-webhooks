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
