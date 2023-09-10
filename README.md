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
