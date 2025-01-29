<?php

namespace Tests\Feature;

use App\Mail\ClientAccountCreation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\PurchaseMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseMailTest extends TestCase
{
    use RefreshDatabase;

    public function testPurchaseMailIsSent()
    {
        Mail::fake();

        $details = [
            'title' => 'Validation d\'une commande',
            'body' => 'Test message'
        ];

        $article_file = '1712512530.car1.jpeg';

        //Mail::to('aymartchimwa@gmail.com')->send(new PurchaseMail($details, $article_file));
        Mail::to('aymartchimwa@gmail.com')->send(new ClientAccountCreation($details));
        //Mail::to('aymartchimwa@gmail.com')->send(new PurchaseMail($details, $article_file));

        // Assert that the emails were sent
        Mail::assertSent(ClientAccountCreation::class);
        //Mail::assertSent(PurchaseMail::class);

    }
}
