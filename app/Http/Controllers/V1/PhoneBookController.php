<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PhoneBookRequest;
use App\Http\Resources\V1\PhoneBookResource;
use App\Models\PhoneBook;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PhoneBookController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PhoneBook
     */
    public function index(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PhoneBookResource(PhoneBook::where('user_id', Auth::id())->where('id', $id)->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PhoneBookResource::collection(PhoneBook::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param PhoneBookRequest $request
     * @return JsonResponse
     * @group PhoneBook
     */
    public function checkPhoneNumberInPhoneBook(PhoneBookRequest $request): JsonResponse
    {
        $phoneNumber = PhoneBook::where('phone_number', $request->phone_number)->where('user_id', Auth::id())->firstOrFail();

        if ($phoneNumber) {
            return response()->ok('Phone number is available');
        }

        return response()->serverError('Phone number is not available');

    }

    /**
     * @param PhoneBookRequest $request
     * @return JsonResponse
     * @group PhoneBook
     */
    public function store(PhoneBookRequest $request): JsonResponse
    {
        $data = $request->safe()->all();
        $userId = Auth::id();

        try {
            $phoneBook = PhoneBook::firstOrCreate(
                [
                    'user_id' => $userId,
                    'phone_number' => $data['phone_number'],
                ],
                array_merge($data, ['user_id' => $userId])
            );

            if (!$phoneBook->wasRecentlyCreated) {
                return response()->json([
                    'message' => __('phone_book.duplicateEntry'),
                ], 409);
            }

            return response()->jsonMacro(new PhoneBookResource($phoneBook));
        } catch (\Exception $e) {
            return response()->serverError(__('general.somethingWrong'));
        }
    }

    /**
     * @param PhoneBookRequest $request
     * @return JsonResponse
     * @group PhoneBook
     */
    public function bachStore(PhoneBookRequest $request): JsonResponse
    {
        $numbers = json_decode($request->phone_numbers, true);
        $numbersArray = [];
        $batchArray = [];

        foreach ($numbers['phone_books'] as $index => $number) {
            if (isset($number['name'], $number['phone_number'])) {
                $numbersArray[$number['name']] = $number['phone_number'];
                $batchArray[$index]['name'] = $number['name'];
                $batchArray[$index]['phone_number'] = $number['phone_number'];
                $batchArray[$index]['user_id'] = Auth::id();
                $batchArray[$index]['last_settings'] = '{}';
            }
        }

        $count = PhoneBook::whereIn('phone_number', $numbersArray)->where('user_id', Auth::id())->count();

        if ($count) {
            return response()->serverError('One or more numbers already exists');
        }

        $response = DB::table('phone_books')->insert($batchArray);

        $phoneBook = PhoneBook::where('user_id', Auth::id())->get();
        if ($response) {
            return response()->jsonMacro(new PhoneBookResource($phoneBook));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param PhoneBookRequest $request
     * @param PhoneBook $phoneBook
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PhoneBook
     */
    public function update(PhoneBookRequest $request, PhoneBook $phoneBook): JsonResponse
    {
        if ($phoneBook->user_id === Auth::id()) {
            $phoneBook->fill($request->safe()->all());

            if ($phoneBook->save()) {
                return response()->ok(__('general.updatedSuccessfully', ['id' => $phoneBook->id]));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PhoneBook $phoneBook
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PhoneBook
     */
    public function destroy(PhoneBook $phoneBook): JsonResponse
    {
        if ($phoneBook->user_id === Auth::id()) {
            if ($phoneBook->delete()) {
                return response()->ok(__('general.deletedSuccessfully', ['id' => $phoneBook->id]));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
