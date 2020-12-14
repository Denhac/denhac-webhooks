# denhac-webhooks
This repo holds some of our membership automation. It listens to webhooks from the main site and updates slack and google groups membership as well as updating access cards. It also listens to slack commands like `/membership` which allows us to customize that list based on the slack user that issued it, whether they're a board member, have an active subscription, etc.

# Architecture
Quite a bit of the code runs using event sourcing. The MembershipAggregate is the main entry point for a lot of the functionality as it decides based on subscription updates if someone is a member or not. Various projectors update models in the database and various reactors do things like send emails when needed.

## Helpful Things
### Aggregate Version Reset
Usually, you don't delete events from the database, but it can be useful to do so in the event of deprecation of events or fixing badly stored events. Sometimes, when that happens, the version numbers for the aggregate get out of wack. This is a helpful bit of SQL to fix that issue:

```sql
UPDATE stored_events
JOIN (
	SELECT
		id,
		aggregate_version,
		ROW_NUMBER() OVER (PARTITION BY aggregate_uuid ORDER BY id) as rn
	FROM stored_events
	WHERE aggregate_version IS NOT NULL
) subq
ON subq.id = stored_events.id
SET stored_events.aggregate_version = subq.rn
WHERE subq.rn != subq.aggregate_version
```
