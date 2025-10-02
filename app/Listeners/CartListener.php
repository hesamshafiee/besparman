<?php

namespace App\Listeners;

use App\Services\V1\Cart\Cart;

class CartListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        Cart::dbGet('esaj');
    }
}
