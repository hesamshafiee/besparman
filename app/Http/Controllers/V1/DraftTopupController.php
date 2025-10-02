<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DraftTopupRequest;
use App\Http\Resources\V1\DraftToupResource;
use App\Models\DraftTopup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DraftTopupController extends Controller
{
    /**
     * @return JsonResponse
     * @group Draft Topups
     */
    public function index(): JsonResponse
    {
        return response()->jsonMacro(DraftToupResource::collection(DraftTopup::where('user_id', Auth::id())->where('created_at', '>=', now()->subHours(2))->orderByDesc('created_at')->paginate(100)));
    }

    /**
     * @param DraftTopupRequest $request
     * @return JsonResponse
     * @group Draft Topups
     */
    public function store(DraftTopupRequest $request): JsonResponse
    {
        $draft = new DraftTopup();
        $draft->fill($request->safe()->all());
        $draft->user_id = Auth::id();

        if ($draft->save()) {
            return response()->ok([
                'message' => __('general.savedSuccessfully'),
                'id' => $draft->id,
            ]);
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    public function destroy(?DraftTopup $draftTopup = null): JsonResponse
    {
        $userId = auth()->id();

        if ($draftTopup) {
            if ($draftTopup->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this draft.'
                ], 403);
            }

            $draftTopup->delete();

            return response()->json([
                'success' => true,
                'message' => 'Draft deleted successfully.'
            ]);
        }

        DraftTopup::where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All drafts for the authenticated user deleted successfully.'
        ]);
    }
}
