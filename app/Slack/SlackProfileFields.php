<?php

namespace App\Slack;


use App\Customer;

class SlackProfileFields
{
    private const IS_MEMBER_FIELD = 'Xf017FTYKWA3';
    private const MEMBER_CODE_FIELD = 'Xf030ZFYLUDP';

    /**
     * @param Customer|null $customer
     * @param array $profileFields
     * @return array
     */
    public static function compareExpectedFieldValues(?Customer $customer, array $profileFields): array
    {
        $updated = [];
        self::compareIsMemberField($customer, $profileFields, $updated);
        self::compareMemberCodeField($customer, $profileFields, $updated);

        return $updated;
    }

    private static function compareIsMemberField(?Customer $customer, array $profileFields, array &$updated)
    {
        $expectedValue = "Yes";
        if (is_null($customer) || !$customer->member) {
            $expectedValue = "No";
        }

        if (!array_key_exists(self::IS_MEMBER_FIELD, $profileFields) ||
            $profileFields[self::IS_MEMBER_FIELD]['value'] != $expectedValue) {
            $updated[self::IS_MEMBER_FIELD] = [
                'value' => $expectedValue,
            ];
        }
    }

    private static function compareMemberCodeField(?Customer $customer, array $profileFields, array &$updated)
    {
        if (is_null($customer)) {
            if (array_key_exists(self::MEMBER_CODE_FIELD, $profileFields)) {
                $updated[self::MEMBER_CODE_FIELD] = [
                    'value' => '',
                    'alt' => '',
                ];
            }
            return;
        }

        $memberCode = $customer->member_code;
        $memberCodeUrl = "https://denhac.org/member/{$memberCode}";

        $needsUpdate = false;

        if (!array_key_exists(self::MEMBER_CODE_FIELD, $profileFields)) {
            $needsUpdate = true;
        } else {
            $field = $profileFields[self::MEMBER_CODE_FIELD];
            if (!array_key_exists('value', $field) || $field['value'] != $memberCodeUrl) {
                $needsUpdate = true;
            }
            if (!array_key_exists('alt', $field) || $field['alt'] != $memberCode) {
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $updated[self::MEMBER_CODE_FIELD] = [
                'value' => $memberCodeUrl,
                'alt' => $memberCode,
            ];
        }
    }
}
