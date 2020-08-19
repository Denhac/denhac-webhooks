<?php

namespace App\Slack;


class SlackID
{
    public const MEMBERSHIP_MODAL_CALLBACK_ID = 'membership-command-modal';
    public const MEMBERSHIP_OPTION_BLOCK_ID = 'membership-option-block';
    public const MEMBERSHIP_OPTION_ACTION_ID = 'membership-option-action';
    public const CANCEL_MEMBERSHIP_VALUE = 'value-cancel-membership';
    public const SIGN_UP_NEW_MEMBER_VALUE = 'value-sign-up-new-member';

    public const SIGN_UP_NEW_MEMBER_CALLBACK_ID = 'sign-up-new-member-modal';
    public const SIGN_UP_NEW_MEMBER_BLOCK_ID = 'sign-up-new-member-block';
    public const SIGN_UP_NEW_MEMBER_ACTION_ID = 'sign-up-new-member-action';

    public const NEW_MEMBER_DETAIL_CALLBACK_ID = 'new-member-detail-modal';
    public const NEW_MEMBER_DETAIL_FIRST_NAME_BLOCK_ID = 'new-member-detail-first-name-block';
    public const NEW_MEMBER_DETAIL_FIRST_NAME_ACTION_ID = 'new-member-detail-first-name-action';
    public const NEW_MEMBER_DETAIL_LAST_NAME_BLOCK_ID = 'new-member-detail-last-name-block';
    public const NEW_MEMBER_DETAIL_LAST_NAME_ACTION_ID = 'new-member-detail-last-name-action';
    public const NEW_MEMBER_DETAIL_CARD_NUM_BLOCK_ID = 'new-member-detail-card-num-block';
    public const NEW_MEMBER_DETAIL_CARD_NUM_ACTION_ID = 'new-member-detail-card-num-action';
}
