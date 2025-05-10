<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\External\WooCommerce{
/**
 * Class WebhookCall.
 *
 * @property string name
 * @property array payload
 * @property array exception
 * @property string topic
 * @property int id
 * @property int $id
 * @property string $name
 * @property array<array-key, mixed> $payload
 * @property array<array-key, mixed>|null $exception
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $topic
 * @property string|null $url
 * @property array<array-key, mixed>|null $headers
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereException($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereTopic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookCall whereUrl($value)
 */
	class WebhookCall extends \Eloquent {}
}

namespace App\Models{
/**
 * Class ActiveCardHolderUpdate.
 *
 * @property array card_holders
 * @property int $id
 * @property array<array-key, mixed> $card_holders
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate whereCardHolders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActiveCardHolderUpdate whereUpdatedAt($value)
 */
	class ActiveCardHolderUpdate extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Card.
 *
 * @property string number
 * @property bool active
 * @property bool member_has_card
 * @property int customer_id
 * @property Customer customer
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $number
 * @property int $active
 * @property int $member_has_card
 * @property int $customer_id
 * @property-read \App\Models\Customer|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereMemberHasCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Card whereUpdatedAt($value)
 */
	class Card extends \Eloquent {}
}

namespace App\Models{
/**
 * Class CardUpdateRequest.
 *
 * @property string type
 * @property int customer_id
 * @property string card
 * @property Customer customer
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $type
 * @property string $customer_id
 * @property string $card
 * @property-read \App\Models\Customer|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest whereCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardUpdateRequest whereUpdatedAt($value)
 */
	class CardUpdateRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Customer.
 *
 * @property int id
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property string username
 * @property bool member
 * @property string github_username
 * @property string slack_id
 * @property Carbon birthday
 * @property Collection subscriptions
 * @property Collection cards
 * @property Collection memberships
 * @property Collection equipmentTrainer
 * @property string member_code
 * @property bool id_checked
 * @property ?string stripe_card_holder_id
 * @property ?int id_was_checked_by_id
 * @property ?Customer idWasCheckedBy
 * @property ?string access_card_temporary_code
 * @method static Builder whereSlackId($slackId)
 * @property int $id
 * @property string $username
 * @property string $email
 * @property bool $member
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $github_username
 * @property string|null $slack_id
 * @property string|null $capabilities
 * @property \Illuminate\Support\Carbon|null $birthday
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property bool $id_checked
 * @property string|null $stripe_card_holder_id
 * @property int|null $id_was_checked_by_id
 * @property string|null $access_card_temporary_code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Card> $cards
 * @property-read int|null $cards_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainableEquipment> $equipmentTrainer
 * @property-read int|null $equipment_trainer_count
 * @property-read string $member_code
 * @property-read Customer|null $idWasCheckedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserMembership> $memberships
 * @property-read int|null $memberships_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Waiver> $waivers
 * @property-read int|null $waivers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAccessCardTemporaryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCapabilities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereGithubUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIdChecked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIdWasCheckedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereMember($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereStripeCardHolderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer withoutTrashed()
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int id
 * @property string status
 * @property int customer_id
 * @property Customer customer
 * @property int $id
 * @property string $customer_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Customer|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withoutTrashed()
 */
	class Subscription extends \Eloquent {}
}

namespace App\Models{
/**
 * Class TempBan
 *
 * @property string user_id
 * @property string channel_id
 * @property ?Carbon expires_at
 * @property ?string reason
 * @property ?string banned_by_id
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string $user_id
 * @property string $channel_id
 * @property string|null $reason
 * @property string|null $banned_by_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereBannedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TempBan whereUserId($value)
 */
	class TempBan extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int id
 * @property string name
 * @property int user_plan_id
 * @property int trainer_plan_id
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property int $user_plan_id
 * @property string|null $user_slack_id
 * @property string|null $user_email
 * @property int $trainer_plan_id
 * @property string|null $trainer_slack_id
 * @property string|null $trainer_email
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereTrainerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereTrainerPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereTrainerSlackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereUserEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereUserPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment whereUserSlackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainableEquipment withoutTrashed()
 */
	class TrainableEquipment extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Client> $clients
 * @property-read int|null $clients_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Token> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * Class UserMembership.
 *
 * @property int id
 * @property int customer_id
 * @property int plan_id
 * @property string status
 * @property int $id
 * @property int $plan_id
 * @property int $customer_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Customer|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMembership withoutTrashed()
 */
	class UserMembership extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int id
 * @property string name
 * @property int plan_id
 * @property int max_people
 * @property Collection channels
 * @method static Builder wherePlanId($planId)
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property int $plan_id
 * @property int $max_people
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VolunteerGroupChannel> $channels
 * @property-read int|null $channels_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup whereMaxPeople($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroup whereUpdatedAt($value)
 */
	class VolunteerGroup extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int volunteer_group_id
 * @property string type
 * @property string value
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $volunteer_group_id
 * @property string $type
 * @property string $value
 * @property-read \App\Models\VolunteerGroup|null $group
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VolunteerGroupChannel whereVolunteerGroupId($value)
 */
	class VolunteerGroupChannel extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string waiver_id
 * @property string template_id
 * @property string template_version
 * @property string first_name
 * @property string last_name
 * @property string email
 * @property int customer_id
 * @property Customer customer
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $waiver_id
 * @property string $template_id
 * @property string $template_version
 * @property string $status
 * @property string|null $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property int|null $customer_id
 * @property-read \App\Models\Customer|null $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereTemplateVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Waiver whereWaiverId($value)
 */
	class Waiver extends \Eloquent {}
}

