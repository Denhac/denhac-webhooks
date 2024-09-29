<?php

namespace Tests\Helpers\Wordpress;

use Tests\Helpers\BaseBuilder;

/**
 * Class CustomerBuilder.
 *
 * @property int id
 * @property string email
 * @property string username
 * @property string first_name
 * @property string last_name
 * @property ?string github_username
 * @property ?string slack_id
 * @property ?string birthday
 * @property ?string access_card_temporary_code
 */
class CustomerBuilder extends BaseBuilder
{
    use HasMetaData;

    public function __construct()
    {
        $this->data = [
            'id' => 1,
            'date_created' => '2020-01-01T00:00:00',
            'date_created_gmt' => '2020-01-01T00:00:00',
            'date_modified' => '2020-01-01T00:00:00',
            'date_modified_gmt' => '2020-01-01T00:00:00',
            'email' => 'email@example.com',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'role' => 'customer',
            'username' => 'username',
            'billing' => [
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'company' => '',
                'address_1' => '1234 Main St.',
                'address_2' => '',
                'city' => 'Denver',
                'postcode' => '80204',
                'country' => 'US',
                'state' => 'CO',
                'email' => 'email@example.com',
                'phone' => '5550001234',
            ],
            'is_paying_customer' => false,
            'meta_data' => [],
        ];
    }

    public function id($id): static
    {
        $this->data['id'] = $id;

        return $this;
    }

    public function first_name($firstName): static
    {
        $this->data['first_name'] = $firstName;

        return $this;
    }

    public function last_name($lastName): static
    {
        $this->data['last_name'] = $lastName;

        return $this;
    }

    public function email($email): static
    {
        $this->data['email'] = $email;

        return $this;
    }

    public function id_was_checked(): static
    {
        return $this->meta_data('id_was_checked', true);
    }

    public function access_card($card): static
    {
        return $this->meta_data('access_card_number', $card);
    }

    public function github_username($username): static
    {
        return $this->meta_data('github_username', $username);
    }

    public function slack_id($slack_id): static
    {
        return $this->meta_data('access_slack_id', $slack_id);
    }

    public function birthday($birthday): static
    {
        return $this->meta_data('account_birthday', $birthday);
    }

    public function access_card_temporary_code($code): static
    {
        return $this->meta_data('_access_temporary_code', $code);
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'github_username':
                $this->github_username($value);
                break;
            case 'slack_id':
                $this->slack_id($value);
                break;
            case 'birthday':
                $this->birthday($value);
                break;
            case 'access_card_temporary_code':
                $this->access_card_temporary_code($value);
            default:
                parent::__set($name, $value);
        }
    }

    public function __get($name)
    {
        return match ($name) {
            'github_username' => $this->get_meta_data($name),
            'slack_id' => $this->get_meta_data('access_slack_id'),
            'birthday' => $this->get_meta_data('account_birthday'),
            'access_card_temporary_code' => $this->get_meta_data('access_card_temporary_code'),
            default => parent::__get($name),
        };
    }
}
