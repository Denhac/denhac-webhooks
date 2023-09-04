<?php

namespace Tests\Helpers\WaiverForever;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\Helpers\BaseBuilder;

class WaiverBuilder extends BaseBuilder
{
    use WithFaker;

    public function __construct()
    {
        $this->setUpFaker();

        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $birthday = $this->faker->dateTime;

        $this->data = [
            'type' => 'new_waiver_accepted',
            'content' => [
                'id' => $this->faker->uuid,
                'ip' => $this->faker->ipv4,
                'data' => [
                    [
                        'type' => 'name_field',
                        'title' => 'Please fill in your name',
                        'value' => "$firstName $lastName",
                        'last_name' => $lastName,
                        'first_name' => $firstName,
                        'middle_name' => null,
                    ],
                    [
                        'day' => $birthday->format('d'),
                        'type' => 'date_field',
                        'year' => $birthday->format('Y'),
                        'month' => $birthday->format('m'),
                        'title' => 'Please fill your Date of Birth',
                        'value' => $birthday->format('m/d/Y'),
                        'format' => 'MM/DD/YYYY',
                    ],
                    [
                        'type' => 'email_field',
                        'title' => 'Please fill in your email',
                        'value' => $this->faker->email,
                    ],
                    [
                        'type' => 'name_field',
                        'title' => "Guardian's Name",
                        'value' => null,
                        'last_name' => null,
                        'first_name' => null,
                        'middle_name' => null,
                    ],
                ],
                'note' => null,
                'tags' => [],
                'device' => null,
                'status' => 'approved',
                'has_pdf' => true,
                'pictures' => [],
                'signed_at' => 1687641050,
                'request_id' => null,
                'geolocation' => [],
                'received_at' => 1687641056,
                'template_id' => $this->faker->uuid,
                'tracking_id' => null,
                'template_title' => 'Waiver 2023-06-26 (Updated)',
                'template_version' => $this->faker->uuid,
                's3_pdf_download_url' => $this->faker->url,
            ],
            'content_type' => 'waiver',
        ];
    }

    public function id($id): static
    {
        $this->data['content']['id'] = $id;

        return $this;
    }
}
