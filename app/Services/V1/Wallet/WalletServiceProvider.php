<?php


namespace App\Services\V1\Wallet;

use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('wallet' , function() {
            return new Director(
                new TransferBuilder(),
                new RejectTransferBuilder(),
                new ConfirmTransferBuilder(),
                new IncreaseByCardBuilder(),
                new IncreaseByAdminBuilder(),
                new DecreaseByAdminBuilder(),
                new PayBuilder(),
                new IncreaseByBankBuilder(),
                new DecreaseByBankBuilder(),
                new IncreaseByRefundBuilder(),
                new IncreaseByPrizeBuilder(),
            );
        });
    }
}
