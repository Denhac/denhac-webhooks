<?php

namespace App;

class FeatureFlags
{
    public const KEEP_MEMBERS_IN_SLACK_AND_EMAIL = 'keep-members-in-slack-and-email';
    public const NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL = 'need-id-check-gets-added-to-slack-and-email';
    public const IGNORE_UNIDENTIFIABLE_MEMBERSHIP = 'ignore-unidentifiable-membership';


    // The following flags are no longer used but are kept here as a reference to make sure they're not re-used.
    // When removing a feature, mark it as private so it cannot be used anywhere else.
    private const SUBSCRIPTION_STATUS_IGNORED = 'subscription-status-ignored';
}
