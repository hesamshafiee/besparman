<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Verification;
use App\Services\V1\Auth\AuthFactory;
use App\Services\V1\Otp\OtpFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * @return void
     */
    public function test_getting_type_of_authentication() : void
    {
        $response = $this->getJson('/api/auth/type');

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'auth' => config('general.auth'),
            'otp' => config('general.otp'),
        ]);
    }

    /**
     * @return void
     */
    public function test_otp_sms() : void
    {
        Config::set('general.auth', AuthFactory::TYPE_OTP);
        Config::set('general.otp', OtpFactory::TYPE_SMS);

        //Validation test
        $this->validationTest();

        //register
        $response = $this->register();
        $this->assertStatusOkAndType($response);

        //login
        $this->loginSms();

        //Invalid login
        $response = $this->invalidLoginSms();
        $this->assertUnauthorizedOtpNotMatched($response);

        //reset password
        $response = $this->resetPassword();
        $this->assertOkNoPassReset($response);

        //resetGoogle2fa
        $response = $this->resetGoogle2fa();
        $this->confirmReinitGoogle2faNotSupportedMode($response);
    }

    /**
     * @return void
     */
    public function test_otp_google2fa() : void
    {
        Config::set('general.auth', AuthFactory::TYPE_OTP);
        Config::set('general.otp', OtpFactory::TYPE_GOOGLE_2FA);

        //Validation test
        $this->validationTest();

        $this->register();


        //login
        $this->loginSms();
        $response = $this->register();
        $this->assertGoogle2faInit($response);
        $this->loginGoogle2fa();

        //Invalid login
        $response = $this->invalidLoginGoogle2fa();
        $this->assertUnauthorizedOtpNotMatched($response);


        //reset password
        $response = $this->resetPassword();
        $this->assertOkNoPassReset($response);

        //resetGoogle2fa
        $this->resetGoogle2fa();
        $this->confirmReinitGoogle2fa();
    }

    /**
     * @return void
     */
    public function test_otp_or_password_sms() : void
    {
        Config::set('general.auth', AuthFactory::TYPE_OTP_OR_PASSWORD);
        Config::set('general.otp', OtpFactory::TYPE_SMS);

        //Validation test
        $this->validationTest();

        //register
        $response = $this->register();
        $this->assertStatusOkAndType($response);


        //login
        $this->loginSms();

        //Invalid login sms
        $response = $this->invalidLoginSms();
        $this->assertUnauthorizedOtpNotMatched($response);


        //reset password
        $response = $this->resetPassword();
        $this->assertStatusOkAndType($response);

        //resetGoogle2fa
        $response = $this->resetGoogle2fa();
        $this->confirmReinitGoogle2faNotSupportedMode($response);

        $this->confirmResetSms();

        //login
        $this->loginWithPassword();

        //Invalid login password
        $response = $this->invalidLoginPassword();
        $this->assertAuthNotMatch($response);

        //set password
        $this->setPassword();

        $response = $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'otpForce' => true]);
        $this->assertStatusOkAndType($response);
    }

    /**
     * @return void
     */
    public function test_otp_or_password_google2fa() : void
    {
        Config::set('general.auth', AuthFactory::TYPE_OTP_OR_PASSWORD);
        Config::set('general.otp', OtpFactory::TYPE_GOOGLE_2FA);

        //Validation test
        $this->validationTest();

        //register
        $this->register();

        //login
        $this->loginSms();
        $response = $this->register();
        $this->assertGoogle2faInit($response);
        $this->loginGoogle2fa();

        //Invalid login google2fa
        $response = $this->invalidLoginGoogle2fa();
        $this->assertUnauthorizedOtpNotMatched($response);


        //reset password
        $response = $this->resetPassword();
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('auth.google2faSendMessage'),
        ]);

        //resetGoogle2fa
        $this->resetGoogle2fa();
        $this->confirmReinitGoogle2fa();

        $this->confirmResetGoogle2fa();

        //login
        $this->loginWithPassword();

        //Invalid login password
        $response = $this->invalidLoginPassword();
        $this->assertAuthNotMatch($response);

        //set password
        $this->setPassword();

        $response = $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'otpForce' => true]);
        $this->assertStatusOkAndType($response);
    }

    /**
     * @return void
     */
    public function test_username_password_sms() : void
    {
        Config::set('general.auth', AuthFactory::TYPE_USERNAME_PASSWORD);
        Config::set('general.otp', OtpFactory::TYPE_SMS);

        //Validation test
        $this->validationTest();

        //register
        $response = $this->register();
        $this->assertStatusOkAndType($response);

        //login
        $this->loginSms();
        //set password
        $this->setPasswordForCurrentUser();
        $this->loginWithPassword();

        //Invalid login password
        $response = $this->invalidLoginPassword();
        $this->assertAuthNotMatch($response);


        //reset password
        $response = $this->resetPassword();
        $this->assertStatusOkAndType($response);

        $this->confirmResetSms();
    }

    /**
     * @return void
     */
    public function test_username_password_google2fa() : void
    {
        Config::set('general.auth', AuthFactory::TYPE_USERNAME_PASSWORD);
        Config::set('general.otp', OtpFactory::TYPE_GOOGLE_2FA);

        //Validation test
        $this->validationTest();

        //register
        $response = $this->register();

        //login
        $this->loginSms();
        //set password
        $this->setPasswordForCurrentUser();
        $this->loginWithPassword();

        //Invalid login password
        $response = $this->invalidLoginPassword();
        $this->assertAuthNotMatch($response);


        //reset password
        $response = $this->resetPassword();
        $this->assertGoogle2faInit($response);

        $this->confirmResetGoogle2fa();

        //resetGoogle2fa
        $this->resetGoogle2fa();
        $this->confirmReinitGoogle2fa();
    }

    /**
     * @return void
     */
    public function test_deleted_user() : void
    {
        //registering user
        $this->register();

        //delete user
        $this->deleteUser();

        //registering user
        $response = $this->register();

        $response->assertStatus(Response::HTTP_FORBIDDEN)->assertJson([
            'message' => __('auth.deletedUserError'),
        ]);
    }

    /**
     * @return void
     */
    public function test_auth_env() : void
    {
        $this->assertTrue(config('general.otp') === OtpFactory::TYPE_GOOGLE_2FA ||
            config('general.otp') === OtpFactory::TYPE_SMS);

        $this->assertTrue(config('general.auth') === AuthFactory::TYPE_USERNAME_PASSWORD ||
            config('general.auth') === AuthFactory::TYPE_OTP_OR_PASSWORD ||
            config('general.auth') === AuthFactory::TYPE_OTP);
    }

    /**
     * @return void
     */
    public function test_auth_check(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->getJson('/api/auth/check');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['user', 'setting'])
        );
    }


    /**
     * @return void
     */
    private function validationTest() : void
    {
        $response = $this->postJson('/api/auth');
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll('message', 'errors')
        );
    }

    /**
     * @return TestResponse
     */
    private function register() : TestResponse
    {
        $response = $this->postJson('/api/auth', ['mobile' => env('MOBILE_1')]);
        $this->assertDatabaseHas('users', [
            'mobile' => env('MOBILE_1'),
        ]);
        return $response;
    }

    /**
     * @return void
     */
    private function loginSms() : void
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        $verificationModel = Verification::where('user_id', $this->user->id)->first();
        $response = $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'code' => $verificationModel->code]);
        $this->assertOkToken($response);
    }

    /**
     * @return void
     */
    private function loginGoogle2fa() : void
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        $google2faCode = Google2FA::getCurrentOtp($this->user->google2fa);
        $response = $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'code' => $google2faCode]);
        $this->assertOkToken($response);

    }

    /**
     * @return void
     */
    private function loginWithPassword() : void
    {
        $response = $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'password' => 'H]4n$Gw2/.z8u{qMJ;}~EB']);
        $this->assertOkToken($response);
    }

    /**
     * @return TestResponse
     */
    public function invalidLoginSms() : TestResponse
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        return $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'code' => '123456']);
    }

    /**
     * @return TestResponse
     */
    public function invalidLoginGoogle2fa() : TestResponse
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        return $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'code' => '123456']);
    }

    /**
     * @return TestResponse
     */
    public function invalidLoginPassword() : TestResponse
    {
        return $this->postJson('/api/auth', ['mobile' => env('MOBILE_1'), 'password' => 'H]4n$Gw2/.z8u{qMJ;}~EB123']);
    }

    /**
     * @return void
     */
    private function deleteUser() : void
    {
        $user = User::where('mobile', env('MOBILE_1'))->first();
        $user->delete();
    }

    /**
     * @return TestResponse
     */
    private function resetPassword() : TestResponse
    {
        return $this->postJson('/api/auth/reset-password', ['mobile' => env('MOBILE_1')]);
    }

    private function setPassword() : void
    {
        $user = User::factory()->create(['password' => null]);

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->postJson('/api/auth/set-password', ['password' => 'H]4n$Gw2/.z8u{qMJ;}~EB']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
    }

    /**
     * @return void
     */
    private function setPasswordForCurrentUser() : void
    {
        $user = User::where('mobile', env('MOBILE_1'))->first();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->postJson('/api/auth/set-password', ['password' => 'H]4n$Gw2/.z8u{qMJ;}~EB']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
    }

    /**
     * @return void
     */
    private function confirmResetSms() : void
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        $verificationModel = Verification::where('user_id', $this->user->id)->first();
        $response = $this->postJson('/api/auth/reset-password', ['mobile' => env('MOBILE_1'), 'code' => $verificationModel->code, 'password' => 'H]4n$Gw2/.z8u{qMJ;}~EB']);
        $this->assertOkToken($response);
    }

    /**
     * @return void
     */
    private function confirmResetGoogle2fa() : void
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        $google2faCode = Google2FA::getCurrentOtp($this->user->google2fa);
        $response = $this->postJson('/api/auth/reset-password', ['mobile' => env('MOBILE_1'), 'code' => $google2faCode, 'password' => 'H]4n$Gw2/.z8u{qMJ;}~EB']);
        $this->assertOkToken($response);
    }

    /**
     * @return TestResponse
     */
    private function resetGoogle2fa() : TestResponse
    {
        return $this->postJson('/api/auth/reset-google2fa', ['mobile' => env('MOBILE_1')]);
    }

    /**
     * @return void
     */
    private function confirmReinitGoogle2fa() : void
    {
        $this->user = User::where('mobile', env('MOBILE_1'))->first();
        $verificationModel = Verification::where('user_id', $this->user->id)->first();
        $response = $this->postJson('/api/auth/reset-google2fa', ['mobile' => env('MOBILE_1'), 'code' => $verificationModel->code]);
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->where('status', true)
            ->hasAll('two_step', 'message', 'secret', 'QR_image')
        );
    }

    /**
     * @return void
     */
    private function confirmReinitGoogle2faNotSupportedMode($response) : void
    {
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('auth.noGoogle2faReset'),
        ]);
    }

    /**
     * @param $response
     * @return void
     */
    private function assertOkToken($response) : void
    {
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->where('status', true)
            ->hasAll('message', 'p', 'access_token', 'refresh_token', 'auth', 'otp', 'permissions', 'roles', 'balance')
        );
    }

    /**
     * @param $response
     * @return void
     */
    private function assertStatusOkAndType($response) : void
    {
        $response->assertStatus(200)->assertJson([
            'status' => true
        ]);
    }

    /**
     * @param $response
     * @return void
     */
    private function assertUnauthorizedOtpNotMatched($response) : void
    {
        $response->assertStatus(Response::HTTP_UNAUTHORIZED)->assertJson([
            'status' => false,
            'message' => __('auth.otpNotMatch'),
        ]);
    }

    /**
     * @param $response
     * @return void
     */
    private function assertOkNoPassReset($response) : void
    {
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('auth.noPassReset'),
        ]);
    }

    /**
     * @param $response
     * @return void
     */
    private function assertGoogle2faInit($response) : void
    {
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->where('status', true)
            ->where('message', config('general.otp') . '-' . config('general.auth'))
            ->hasAll('two_step', 'secret', 'QR_image')
        );
    }

    /**
     * @param $response
     * @return void
     */
    private function assertAuthNotMatch($response) : void
    {
        $response->assertStatus(Response::HTTP_UNAUTHORIZED)->assertJson([
            'status' => false,
            'message' => __('auth.authNotMatch'),
        ]);
    }
}
