<?php

namespace App\Models;

use App\FeatureFlags;
use App\VolunteerGroupChannels\ChannelInterface;
use App\VolunteerGroupChannels\GitHubTeam;
use App\VolunteerGroupChannels\GoogleGroup;
use App\VolunteerGroupChannels\SlackChannel;
use App\VolunteerGroupChannels\SlackUserGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use YlsIdeas\FeatureFlags\Facades\Features;

/**
 * @property int volunteer_group_id
 * @property string type
 * @property string value
 */
class VolunteerGroupChannel extends Model
{
    use HasFactory;

    private ChannelInterface|null $channelInstance = null;

    /*
     * For new channels, add the constant here in the form of <system>_<object type>_<field type> alphabetically.
     * That's not a hard and fast rule, but it groups parts in the same system, let's us know what in that system this
     * channel is for, and what the identifier we're using to refer to it is. For example, SLACK_USER_GROUP_ID is the
     * system "Slack", the object is "user group", and we're referring to the user group by its built in id, vs
     * referring to it by name which can change in Slack's system. Once the field is used, the value cannot change since
     * that's what's used in the database. The const key can change, however.
     *
     * Don't forget to make a class that implements ChannelInterface. Channels should be idempotent meaning calling add
     * when someone is already in a channel or remove when they're not should not do anything. Channels are also
     * required to queue add and remove
     */
    public const GITHUB_TEAM_NAME = 'github_team_name';
    public const GOOGLE_GROUP_EMAIL = 'google_group_email';
    public const SLACK_CHANNEL_ID = 'slack_channel_id';
    public const SLACK_USER_GROUP_ID = 'slack_user_group_id';

    protected $fillable = [
        'volunteer_group_id',
        'type',
        'value',
    ];

    public function group()
    {
        return $this->belongsTo(VolunteerGroup::class);
    }

    protected function getChannel(): ChannelInterface
    {
        if (is_null($this->channelInstance)) {
            $this->channelInstance = collect(get_declared_classes())
                ->filter(fn($name) => str_starts_with($name, 'App\\VolunteerGroupChannels'))
                ->map(fn($name) => new ReflectionClass($name))
                ->filter(fn($reflect) => $reflect->implementsInterface(ChannelInterface::class))
                ->filter(fn($reflect) => $reflect->getMethod('getTypeKey')->invoke(null) == $this->type)
                ->map(fn($reflect) => $reflect->getName())
                ->first();

            if (is_null($this->channelInstance)) {
                throw new \Exception("Unknown channel type: {$this->type}");
            }
        }

        return $this->channelInstance;
    }

    public function add(Customer $customer): void
    {
        if (! Features::accessible(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS) && $this->type == self::SLACK_CHANNEL_ID) {
            return;  // If we're a slack channel, but the feature flag isn't enabled, don't add the customer this way.
        }

        try {
            $this->getChannel()->add($customer, $this->value);
        } catch (\Exception $exception) {
            report($exception);
        }
    }

    public function remove(Customer $customer): void
    {
        if (! Features::accessible(FeatureFlags::USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS) && $this->type == self::SLACK_CHANNEL_ID) {
            return;  // If we're a slack channel, but the feature flag isn't enabled, don't remove the customer this way.
        }

        try {
            $this->getChannel()->remove($customer, $this->value);
        } catch (\Exception $exception) {
            report($exception);
        }
    }

    public function removeOnMembershipLost(): bool
    {
        return $this->getChannel()::removeOnMembershipLost();
    }
}
