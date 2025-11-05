<?php

namespace Tests\Feature\Http\Slack;

use App\External\WooCommerce\Api\customer\CustomerApi;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Models\Customer;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ConfirmInviteControllerTest extends TestCase
{
    private const string SLACK_ID = 'U123456789';

    private MockInterface|WooCommerceApi $wooCommerceApi;

    private MockInterface|CustomerApi $customerApi;

    protected function setUp(): void
    {
        parent::setUp();

        Passport::actingAs($this->apiUser, ['slack:invite']);

        $this->wooCommerceApi = $this->mock(WooCommerceApi::class);
        $this->customerApi = $this->mock(CustomerApi::class);
        $this->wooCommerceApi->customers = $this->customerApi;
    }

    protected function postInvite($data): TestResponse
    {
        return $this->postJson('/api/slack/invites', $data);
    }

    #[Test]
    public function missing_email_is_invalid(): void
    {
        $this->postInvite(['slack_id' => self::SLACK_ID])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    #[Test]
    public function email_must_be_valid_email(): void
    {
        $this->postInvite(['slack_id' => self::SLACK_ID, 'email' => 'foo'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    #[Test]
    public function missing_slack_id_is_invalid(): void
    {
        $this->postInvite(['email' => $this->faker->email])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'slack_id',
                ],
            ]);
    }

    #[Test]
    public function missing_customer_returns_404(): void
    {
        $this->postInvite(['slack_id' => self::SLACK_ID, 'email' => $this->faker->email])
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'message',
            ]);
    }

    #[Test]
    public function customer_with_existing_slack_id_cannot_be_re_assigned(): void
    {
        $customer = Customer::factory()->withSlackId()->create();

        $this->postInvite(['slack_id' => self::SLACK_ID, 'email' => $customer->email])
            ->assertStatus(Response::HTTP_CONFLICT)
            ->assertJsonStructure([
                'message',
            ]);
    }

    #[Test]
    public function can_assign_slack_id_to_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->customerApi->expects('update')
            ->with($customer->id, [
                'meta_data' => [
                    [
                        'key' => 'access_slack_id',
                        'value' => self::SLACK_ID,
                    ],
                ],
            ]);

        $this->assertNull($customer->slack_id);

        $this->postInvite(['slack_id' => self::SLACK_ID, 'email' => $customer->email])
            ->assertStatus(Response::HTTP_OK);

        $customer->refresh();

        // This endpoint updates the WordPress user, it does not update our local customer. We still want to wait for
        // the webhook call for the local user update.
        $this->assertNull($customer->slack_id);
    }
}
