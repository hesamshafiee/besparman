<?php

use App\Http\Controllers\V1\AclController;
use App\Http\Controllers\V1\AlertController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CartController;
use App\Http\Controllers\V1\CategoryController;
use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\DeliveryController;
use App\Http\Controllers\V1\DiscountController;
use App\Http\Controllers\V1\DraftTopupController;
use App\Http\Controllers\V1\FtpController;
use App\Http\Controllers\V1\GroupChargeController;
use App\Http\Controllers\V1\IrancellOfferPackageController;
use App\Http\Controllers\V1\CardChargeController;
use App\Http\Controllers\V1\ImageController;
use App\Http\Controllers\V1\LandingController;
use App\Http\Controllers\V1\LogController;
use App\Http\Controllers\V1\AddressController;
use App\Http\Controllers\V1\LogisticController;
use App\Http\Controllers\V1\MainPageReportController;
use App\Http\Controllers\V1\MenuController;
use App\Http\Controllers\V1\OperatorController;
use App\Http\Controllers\V1\OrderController;
use App\Http\Controllers\V1\PanelMessageController;
use App\Http\Controllers\V1\PaymentController;
use App\Http\Controllers\V1\PhoneBookController;
use App\Http\Controllers\V1\PointController;
use App\Http\Controllers\V1\PointHistoryController;
use App\Http\Controllers\V1\PrizeController;
use App\Http\Controllers\V1\ProductController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ProfitController;
use App\Http\Controllers\V1\ProfitGroupController;
use App\Http\Controllers\V1\ProfitSplitController;
use App\Http\Controllers\V1\purchaseWithoutCartController;
use App\Http\Controllers\V1\ReportController;
use App\Http\Controllers\V1\SaleController;
use App\Http\Controllers\V1\ScheduledTopupController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\TicketController;
use App\Http\Controllers\V1\TokenController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\UsernameController;
use App\Http\Controllers\V1\WalletController;
use App\Http\Controllers\V1\WalletTransactionExtraController;
use App\Http\Controllers\V1\WarehouseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\ReconciliationController;
use App\Http\Controllers\V1\TelegramController;
use App\Http\Controllers\V1\UserSettingController;
use App\Http\Controllers\V1\VersionController;
use App\Http\Controllers\V1\SearchDemoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['throttle:auth', 'validate.signature'])->group(function () {
    Route::post('/auth', [AuthController::class, 'auth']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/reset-google2fa', [AuthController::class, 'resetGoogle2fa']);
    Route::get('auth/type', [AuthController::class, 'authType']);
});

Route::middleware(['checkToken'])->group(function () {
    Route::middleware(['auth:sanctum', 'can:super-admin', 'validate.signature'])->group(function () {
        Route::get('/acl', [AclController::class, 'permissionsAndRoles']);
        Route::get('/acl/get-role-permissions/{role}', [AclController::class, 'getRolePermissions']);
        Route::post('/acl/give-permission-to-role', [AclController::class, 'givePermissionTo']);
        Route::post('/acl/revoke-permission-to-role', [AclController::class, 'revokePermissionTo']);
        Route::post('/acl/sync-permission-to-role', [AclController::class, 'syncPermissionsToRoles']);
        Route::post('/acl/assign-role-to-user', [AclController::class, 'assignRoleToUser']);
        Route::post('/acl/remove-role-to-user', [AclController::class, 'removeRoleToUser']);
        Route::post('/acl/create-role', [AclController::class, 'createRole']);
        Route::patch('/acl/update-role/{role}', [AclController::class, 'updateRole']);
        Route::delete('/acl/role/{role}', [AclController::class, 'deleteRole']);
        Route::get('/logs', [LogController::class, 'laravelLog']);

        Route::get('/ftp/files', [FtpController::class, 'listFiles']);
        Route::get('/ftp/download/{filename}', [FtpController::class, 'downloadFile'])->where('filename', '.*');
        Route::get('/operators/get-balances', [purchaseWithoutCartController::class, 'getBalance']);
        Route::get('/main-page/report', [MainPageReportController::class, 'report']);

        Route::get('/reconciliations', [ReconciliationController::class, 'index']);
        Route::get('/reconciliations2', [ReconciliationController::class, 'index2']);
        Route::get('/reconciliations/check', [ReconciliationController::class, 'check']);
        Route::post('/reconciliations/{walletTransaction}', [ReconciliationController::class, 'fixTransaction']);
        Route::post('/reconciliations2/{order}', [ReconciliationController::class, 'fixTransaction2']);
    });

    Route::middleware(['auth:sanctum', 'validate.signature'])->group(function () {
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/private', [ProductController::class, 'privateIndex']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/assign-category/{product}', [ProductController::class, 'assignCategoryToProduct']);
        Route::patch('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
        Route::patch('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
//    Route::post('/products/options/{product}', [ProductController::class, 'options']);

        Route::get('/discounts', [DiscountController::class, 'index']);
        Route::post('/discounts', [DiscountController::class, 'store']);
        Route::patch('/discounts/{discount}', [DiscountController::class, 'update']);
        Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy']);

        Route::get('/images/get/{name}/{rand}', [ImageController::class, 'getImage']);
        Route::delete('/images/delete/{name}/{driver}/{type}', [ImageController::class, 'deleteSingleImage']);
        Route::get('/images/list/private/{group}/{id}', [ImageController::class, 'imageListPrivate']);

        Route::get('/cart/discount/{discount?}', [CartController::class, 'addDiscount']);
        Route::post('/cart/delivery/{logistic}', [CartController::class, 'createDelivery']);
        Route::post('/cart/checkout', [CartController::class, 'checkout']);

        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::patch('/sales/{sale}', [SaleController::class, 'update']);
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);

        Route::get('/wallet/transactions', [WalletController::class, 'index']);
        Route::get('wallet/check/{code}', [WalletController::class, 'check']);
        Route::get('/clients/wallet/transactions', [WalletController::class, 'clientIndex']);
        Route::post('/wallet/transfer', [WalletController::class, 'transfer']);
        Route::post('/wallet/confirm-transfer', [WalletController::class, 'confirmTransfer']);
        Route::post('/wallet/reject-transfer', [WalletController::class, 'rejectTransfer']);
        Route::post('/wallet/increase-by-admin', [WalletController::class, 'increaseByAdmin']);
        Route::post('/wallet/decrease-by-admin', [WalletController::class, 'decreaseByAdmin']);


        Route::get('/clients/telegram', [TelegramController::class, 'clientIndex']);
        Route::delete('/clients/telegram/{userTelegramAccount}', [TelegramController::class, 'clientDestroy']);

        Route::get('/orders', [PaymentController::class, 'orders']);
        Route::get('/orders/physical', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'getOrder']);
        Route::get('/client/orders', [OrderController::class, 'clientIndexOrder']);
        Route::patch('/orders/status/{order}', [OrderController::class, 'updateStatus']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/clients/payments', [PaymentController::class, 'clientIndex']);
        Route::get('/clients/orders', [PaymentController::class, 'clientIndexOrder']);
        Route::post('/payment/card/increase', [PaymentController::class, 'cardToCard']);
        Route::get('/payment/confirm/{payment}', [PaymentController::class, 'confirm']);
        Route::get('/payment/reject/{payment}', [PaymentController::class, 'reject']);


        Route::get('/clients/user-settings', [UserSettingController::class, 'clientIndex']);
        Route::post('/clients/user-settings', [UserSettingController::class, 'clientStore']);
        Route::get('/clients/user-settings/{id}', [UserSettingController::class, 'clientShow']);
        Route::patch('/clients/user-settings/{id}', [UserSettingController::class, 'clientUpdate']);
        Route::delete('/clients/user-settings/{id}', [UserSettingController::class, 'clientDestroy']);

        Route::get('/menus', [MenuController::class, 'index']);
        Route::post('/menus', [MenuController::class, 'store']);
        Route::patch('/menus/{menu}', [MenuController::class, 'update']);
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);

        Route::get('/panel-messages', [PanelMessageController::class, 'index']);
        Route::post('/panel-messages', [PanelMessageController::class, 'store']);
        Route::patch('/panel-messages/{panelMessage}', [PanelMessageController::class, 'update']);
        Route::delete('/panel-messages/{panelMessage}', [PanelMessageController::class, 'destroy']);
        Route::get('/clients/panel-messages', [PanelMessageController::class, 'clientIndex']);

        Route::get('/versions', [VersionController::class, 'index']);
        Route::post('/versions', [VersionController::class, 'store']);
        Route::patch('/versions/{version}', [VersionController::class, 'update']);
        Route::delete('/versions/{version}', [VersionController::class, 'destroy']);
        Route::get('/versions/send-for-users', [VersionController::class, 'updateForUsers']);
        Route::get('/clients/versions', [PanelMessageController::class, 'clientIndex']);
        Route::get('/clients/versions/latest-by-type', [VersionController::class, 'latestByType']);

        Route::post('/auth/set-password', [AuthController::class, 'setPassword']);

        Route::get('/log/activity', [LogController::class, 'activityLog']);

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::patch('/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('/clients/addresses', [AddressController::class, 'clientIndex']);
        Route::post('/clients/addresses', [AddressController::class, 'clientStore']);
        Route::patch('/clients/addresses/{address}', [AddressController::class, 'clientUpdate']);
        Route::delete('/clients/addresses/{address}', [AddressController::class, 'clienteStroy']);

        Route::get('/logistics', [LogisticController::class, 'index']);
        Route::get('/clients/logistics', [LogisticController::class, 'clientIndex']);
        Route::post('/logistics', [LogisticController::class, 'store']);
        Route::patch('/logistics/{logistic}', [LogisticController::class, 'update']);
        Route::delete('/logistics/{logistic}', [LogisticController::class, 'destroy']);

        Route::get('/warehouses', [WarehouseController::class, 'index']);
        Route::post('/warehouses', [WarehouseController::class, 'store']);
        Route::patch('/warehouses/{warehouse}', [WarehouseController::class, 'update']);
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

        Route::get('/landings', [LandingController::class, 'index']);
        Route::post('/landings', [LandingController::class, 'store']);
        Route::patch('/landings/{landing}', [LandingController::class, 'update']);
        Route::delete('/landings/{landing}', [LandingController::class, 'destroy']);

        Route::get('/deliveries', [DeliveryController::class, 'index']);

        Route::get('/users/two-steps', [UserController::class, 'twoStepStatus']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/restore/{id}', [UserController::class, 'restoreUser']);
        Route::get('/users-soft-deleted', [UserController::class, 'indexSoftDeleted']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/add-images', [UserController::class, 'addImages']);

        Route::get('/auth/check', [AuthController::class, 'checkToken']);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::patch('/settings/{setting}', [SettingController::class, 'update']);
        Route::delete('/settings/{setting}', [SettingController::class, 'destroy']);

        Route::get('/comments', [CommentController::class, 'index']);
        Route::post('/comments', [CommentController::class, 'store']);
        Route::patch('/comments/{comment}', [CommentController::class, 'update']);
        Route::patch('/comments/status/{comment}', [CommentController::class, 'status']);

        Route::get('/profits', [ProfitController::class, 'index']);
        Route::post('/profits', [ProfitController::class, 'store']);
        Route::patch('/profits/{profit}', [ProfitController::class, 'update']);
        Route::delete('/profits/{profit}', [ProfitController::class, 'destroy']);

        Route::get('/profit-splits', [ProfitSplitController::class, 'index']);
        Route::post('/profit-splits', [ProfitSplitController::class, 'store']);
        Route::patch('/profit-splits/{profitSplit}', [ProfitSplitController::class, 'update']);
        Route::delete('/profit-splits/{profitSplit}', [ProfitSplitController::class, 'destroy']);

        Route::get('/profit-groups/user', [ProfitGroupController::class, 'getUserProfitGroup']);
        Route::get('/profit-groups', [ProfitGroupController::class, 'index']);
        Route::post('/profit-groups', [ProfitGroupController::class, 'store']);
        Route::patch('/profit-groups/{profitGroup}', [ProfitGroupController::class, 'update']);
        Route::delete('/profit-groups/{profitGroup}', [ProfitGroupController::class, 'destroy']);
        Route::post('/profit-groups/assign-profit-group-to-user/{user}', [ProfitGroupController::class, 'assignProfitGroupToUser']);

        Route::get('/profiles', [ProfileController::class, 'index']);
        Route::post('/profiles', [ProfileController::class, 'store']);
        Route::post('/profiles/by-admin/{user}', [ProfileController::class, 'storeByAdmin']);
        Route::patch('/profiles/by-admin/{user}', [ProfileController::class, 'updateByAdmin']);
        Route::patch('/profiles', [ProfileController::class, 'update']);

        Route::patch('/operators/{operator}', [OperatorController::class, 'update']);

        Route::get('/phone-books', [PhoneBookController::class, 'index']);
        Route::post('/phone-books', [PhoneBookController::class, 'store']);
        Route::post('/phone-books/batch', [PhoneBookController::class, 'bachStore']);
        Route::post('/phone-books/check', [PhoneBookController::class, 'checkPhoneNumberInPhoneBook']);
        Route::patch('/phone-books/{phoneBook}', [PhoneBookController::class, 'update']);
        Route::delete('/phone-books/{phoneBook}', [PhoneBookController::class, 'destroy']);

        Route::get('/irancell-offer-package', [IrancellOfferPackageController::class, 'index']);

        Route::get('/group-charge', [GroupChargeController::class, 'index']);
        Route::post('/group-charge/topup/{product}', [GroupChargeController::class, 'storeTopup']);
        Route::post('/group-charge/topup-package/{product}', [GroupChargeController::class, 'storeTopupPackage']);
        Route::post('/group-charge/cancel/{product}', [GroupChargeController::class, 'cancel']);
        Route::post('/group-charge/force/{id}', [GroupChargeController::class, 'updateForce']);
        Route::get('/clients/group-charge', [GroupChargeController::class, 'clientIndex']);


        Route::get('/clients/user/mobile-charged-before/{mobile}', [UserController::class, 'clientCheckMobileChargedBefore']);




        Route::get('/telegram/active-link/{code?}', [TelegramController::class, 'activeLink']);





        Route::get('/card-charge', [CardChargeController::class, 'index']);
        Route::post('/card-charge', [CardChargeController::class, 'store']);
        Route::get('/card-charge/destroyOpen/{cardCharge}', [CardChargeController::class, 'destroyOpen']);
        Route::get('/card-charge/freeReport', [CardChargeController::class, 'freeReport']);
        Route::get('/card-charge/findBySerial', [CardChargeController::class, 'findBySerial']);
        Route::post('/clients/card-charge/buy', [CardChargeController::class, 'clientBuy']);
        Route::get('/clients/card-charge', [CardChargeController::class, 'clientIndex']);


        Route::get('/wallet/transaction/extras', [WalletTransactionExtraController::class, 'index']);
        Route::patch('/wallet/transaction/extras/{walletTransactionExtra}', [WalletTransactionExtraController::class, 'update']);


        Route::get('/users/financial/info', [UserController::class, 'getFinancialInfo']);

        Route::get('/tickets/by-user/{user?}', [TicketController::class, 'getTickets']);
        Route::get('/tickets/conversation/{ticket}', [TicketController::class, 'getConversation']);
        Route::get('/tickets/close/{ticket}', [TicketController::class, 'closeTicket']);
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::get('/tickets/by-user-status', [TicketController::class, 'getTicketCountsGroupedByUserAndStatus']);

        Route::get('/points', [PointController::class, 'index']);
        Route::post('/points', [PointController::class, 'store']);
        Route::patch('/points/{point}', [PointController::class, 'update']);
        Route::delete('/points/{point}', [PointController::class, 'destroy']);

        Route::get('/point-histories', [PointHistoryController::class, 'index']);
        Route::get('/client/point-histories', [PointHistoryController::class, 'clientIndex']);

        Route::get('/prizes', [PrizeController::class, 'index']);
        Route::get('/clients/prizes', [PrizeController::class, 'clientIndex']);
        Route::get('/prizes-purchase', [PrizeController::class, 'purchaseIndex']);
        Route::get('/clients/prizes-purchase', [PrizeController::class, 'purchaseClientIndex']);
        Route::post('/prizes', [PrizeController::class, 'store']);
        Route::post('/prizes/items', [PrizeController::class, 'ItemsStore']);
        Route::patch('/prizes/{prize}', [PrizeController::class, 'update']);
        Route::delete('/prizes/{prize}', [PrizeController::class, 'destroy']);
        Route::patch('/prizes/status/{prize}', [PrizeController::class, 'status']);
        Route::patch('/prizes-purchase/status/{prizePurchase}', [PrizeController::class, 'purchaseStatus']);
        Route::post('/prizes/purchase/{prize}', [PrizeController::class, 'purchase']);

        Route::get('/tokens', [TokenController::class, 'index']);
        Route::get('/tokens/create', [TokenController::class, 'create']);
        Route::delete('/tokens', [TokenController::class, 'destroyAll']);
        Route::delete('/tokens/{personalAccessToken}', [TokenController::class, 'destroy']);
        Route::post('/package-list', [purchaseWithoutCartController::class, 'packageList']);

        Route::get('auth/logout', [TokenController::class, 'logout']);
        Route::get('auth/refresh-token', [TokenController::class, 'refreshToken']);

        Route::get('/tags', [TagController::class, 'index']);
        Route::post('/tags', [TagController::class, 'get']);
        Route::post('/tags/create', [TagController::class, 'store']);
        Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
        Route::delete('/tags/force/{tag}', [TagController::class, 'forceDestroy']);

        Route::post('/reports', [ReportController::class, 'dynamicAggregate']);


        Route::post('/alert/telegram', [AlertController::class, 'telegram']);

        Route::get('/scheduled-topups', [ScheduledTopupController::class, 'index']);
        Route::post('/scheduled-topups/cancel/{id}', [ScheduledTopupController::class, 'cancel']);

        Route::get('/profile', [ProfileController::class, 'getProfileOfLoggedInUser']);

        Route::get('/draft-topups', [DraftTopupController::class, 'index']);
        Route::post('/draft-topups', [DraftTopupController::class, 'store']);
        Route::delete('/draft-topups/{draftTopup?}', [DraftTopupController::class, 'destroy']);



        Route::get('/search-demo/seed',   [SearchDemoController::class, 'seed']);
        Route::get('/search-demo/search', [SearchDemoController::class, 'search']); 
    });
});


Route::middleware(['OptionalSanctum', 'validate.signature'])->group(function () {
    Route::get('/cart/all/{cart?}', [CartController::class, 'index']);
    Route::get('/cart/{product}/{cart?}', [CartController::class, 'addToCart']);
    Route::delete('/cart/{product}/{cart?}', [CartController::class, 'removeFromCart']);
    Route::middleware(['throttle:top-up-limit', 'idempotency'])->group(function () {
        Route::post('/top-up', [purchaseWithoutCartController::class, 'topUp']);
        Route::post('/top-up/package', [purchaseWithoutCartController::class, 'topUp']);
        Route::post('/top-up/bulk', [purchaseWithoutCartController::class, 'bulkTopUp']);
    });
    Route::get('/images/list/public/{group}/{id}', [ImageController::class, 'imageList']);
    Route::get('/images/public/get/{name}/{rand}', [ImageController::class, 'getPublicImage']);
    Route::get('/clients/menus', [MenuController::class, 'clientIndex']);
    Route::post('/payment/bank/increase' , [PaymentController::class, 'bank'])->name('bank');
    Route::post('/bank/payment/callback' , [PaymentController::class, 'callbackFromBank'])->name('callback');
    Route::get('/clients/landings', [LandingController::class, 'clientIndex']);
    Route::get('/clients/products', [ProductController::class, 'clientIndex']);
    Route::get('/clients/products/private', [ProductController::class, 'privateClientIndex']);
    Route::post('/irancell/bill', [purchaseWithoutCartController::class, 'irancellBill']);
    Route::post('/irancell/offers', [purchaseWithoutCartController::class, 'irancellOffers']);
    Route::post('/irancell/sim-type', [purchaseWithoutCartController::class, 'irancellSimType']);
    Route::get('/wallet/status/{res}', [WalletController::class, 'getStatus']);

//    Route::post('/search', [SearchController::class, 'search']);
    Route::post('/filter', [SearchController::class, 'filter']);
    Route::post('/telegram/save-telegram-id', [TelegramController::class, 'saveTelegramId']);

    Route::get('/username/{id}', [UsernameController::class, 'index']);
    Route::get('/operators', [OperatorController::class, 'index']);
});



