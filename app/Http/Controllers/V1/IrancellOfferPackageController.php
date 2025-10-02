<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\IrancellOfferPackageResource;
use App\Models\IrancellOfferPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IrancellOfferPackageController extends Controller
{
    /**
     * @param int|null $id
     * @return JsonResponse
     * @group IrancellOfferPackage
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', IrancellOfferPackage::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new IrancellOfferPackageResource(IrancellOfferPackage::where(
                [
                    'id'=> $id
                ]
            )->firstOrFail()
            ));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(IrancellOfferPackageResource::collection(IrancellOfferPackage::orderBy($order, $typeOrder)->paginate($perPage)));
    }


}
