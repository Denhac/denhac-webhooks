<?php

namespace App;

/**
 * This class stores our feature flag names. Feature flags allows us to write tests and features that are not enabled
 * until whatever precise time we want them to be enabled.
 */
class FeatureFlags
{
    public const WAIVER_REQUIRED_FOR_CARD_ACCESS = 'waiver-required-for-card-access';

    // The following flags are no longer used but are kept here as a reference to make sure they're not re-used.
    // When removing a feature, mark it as private so it cannot be used anywhere else and tag it as deprecated.

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
