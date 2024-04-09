<?php

namespace Tests\Unit\Actions\Stripe;

use App\Actions\Stripe\UpdateStripeCardsFromSource;
use App\Models\StripeCard;
use Mockery\MockInterface;
use Stripe\Service\Issuing\CardService;
use Stripe\Service\Issuing\IssuingServiceFactory;
use Stripe\StripeClient;
use Tests\Helpers\Stripe\StripeIssuing;
use Tests\TestCase;

class UpdateStripeCardsFromSourceTest extends TestCase
{
    use StripeIssuing;

    private UpdateStripeCardsFromSource $updateCardsFromSource;

    private MockInterface|StripeClient $stripeClient;
    private MockInterface|IssuingServiceFactory $issuing;
    private MockInterface|CardService $cards;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripeClient = $this->mock(StripeClient::class);
        $this->issuing = $this->mock(IssuingServiceFactory::class);
        $this->stripeClient->issuing = $this->issuing;

        $this->cards = $this->mock(CardService::class);
        $this->issuing->cards = $this->cards;

        $this->updateCardsFromSource = app(UpdateStripeCardsFromSource::class);
    }

    /** @test */
    public function polling_for_the_list_of_cards_creates_new_card_entries(): void
    {
        $newCard = $this->stripeIssuingCard();
        $this->cards->allows('all')
            ->andReturns($this->stripeCollection($newCard));

        $this->assertEmpty(StripeCard::all());

        $this->updateCardsFromSource->execute();

        $cardModels = StripeCard::all();
        $this->assertEquals(1, $cardModels->count());

        $cardModel = $cardModels->first();
        $this->assertEquals($newCard->id, $cardModel->id);
        $this->assertEquals($newCard->cardholder->id, $cardModel->cardholder_id);
        $this->assertEquals($newCard->type, $cardModel->type);
        $this->assertEquals($newCard->status, $cardModel->status);
    }

    /** @test */
    public function polling_for_the_list_of_cards_only_updates_the_fields_that_can_change(): void
    {
        $newCard = $this->stripeIssuingCard();
        $this->cards->allows('all')
            ->andReturns($this->stripeCollection($newCard));

        $this->assertEmpty(StripeCard::all());

        $this->updateCardsFromSource->execute();

        $cardModels = StripeCard::all();
        $this->assertEquals(1, $cardModels->count());

        // At this point, we have our one created stripe model. Now begins the testing.

        // Once these fields are set on creation, they should never change so we should be sure to never respond to a
        // change.
        $originalCardHolder = $newCard->cardholder;
        $newCard->cardholder = $this->stripeIssuingCardHolder();

        $this->updateCardsFromSource->execute();

        $cardModel = $cardModels->first();
        $this->assertEquals($newCard->id, $cardModel->id);
        $this->assertEquals($originalCardHolder->id, $cardModel->cardholder_id);
        $this->assertEquals($newCard->type, $cardModel->type);
        $this->assertEquals($newCard->status, $cardModel->status);
    }
}
