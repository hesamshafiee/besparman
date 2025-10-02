<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private User $user;
    private mixed $cart;
    private string $cartKey;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $cart, User $user)
    {
        $this->cart = $cart;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cart = Cart::where('user_id', $this->user->id)->first();

        if (!$cart) {
            $cart = new Cart();
            $cart->user_id = $this->user->id;
        }

        $cart->cart_detail = $this->cart;

        $cart->save();
    }
}
