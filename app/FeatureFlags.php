<?php

namespace App;

/**
 * This class stores our feature flag names. Feature flags allows us to write tests and features that are not enabled
 * until whatever precise time we want them to be enabled.
 */
class FeatureFlags
{
    /**
     * With this feature flag off, things that would invite people to a specific slack channel will still be handled in
     * the specific locations they've been hardcoded. For example, the SlackReactor might use the TrainableEquipment
     * table to manage who gets invited to a channel for a user or for a trainer. With it on, that code should be
     * prevented from running and instead the VolunteerGroupsReactor should handle it.
     */
    public const USE_VOLUNTEER_GROUPS_FOR_SLACK_CHANNELS = 'use-volunteer-groups-for-slack-channels';

    // The following flags are no longer used but are kept here as a reference to make sure they're not re-used.
    // When removing a feature, mark it as private so it cannot be used anywhere else and tag it as deprecated.

    /**
     * @deprecated true
     *
     * New insurance requirements forced us to get a digital signature for every member. In order to facilitate that,
     * while still letting members use the space before we signed the insurance paperwork, we used this feature flag
     * to allow people to still use the space right up until we cut-over to require waivers to access the space.
     */
    private const WAIVER_REQUIRED_FOR_CARD_ACCESS = 'waiver-required-for-card-access';

    /**
     * @deprecated true
     *
     * When doing the cut-over from subscription statuses affecting memberships to user membership statuses affecting
     * memberships, this flag controls which one we would listen to. If true, listen to user memberships. If false,
     * subscriptions.
     */
    private const SUBSCRIPTION_STATUS_IGNORED = 'subscription-status-ignored';

    /**
     * @deprecated false
     *
     * During COVID when we could not meet as readily in the space, we wanted anyone who signed up on the website to get
     * full access to our slack, even before we could do an ID check since the ID check often was delayed for a while.
     * If this flag is true, then anyone signing up would be made a full member in slack. If false, they are only added
     * to the "need id check" channel as a single channel guest.
     */
    private const NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL = 'need-id-check-gets-added-to-slack-and-email';

    /**
     * @deprecated false
     *
     * During COVID when we could not meet as readily in the space, we wanted to make it so members who became
     * non-members were kept as full members in slack. If this flag is true, we would not demote them to single channel
     * guests in the "public" channel.
     */
    private const KEEP_MEMBERS_IN_SLACK_AND_EMAIL = 'keep-members-in-slack-and-email';

    /**
     * @deprecated false
     *
     * When doing consistency checks and identifying any issues with our membership/system, if we have a slack account
     * or email that we cannot associate to a membership, should we ignore it? If true, we ignore it
     */
    private const IGNORE_UNIDENTIFIABLE_MEMBERSHIP = 'ignore-unidentifiable-membership';
}
