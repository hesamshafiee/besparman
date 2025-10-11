<?php

namespace App\Http\Controllers\V1;

use App\Services\V1\ElasticSearch\SearchClient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class SearchDemoController extends Controller
{
    public function seed(SearchClient $search)
    {
        // داده‌ی نمونه‌ی شما
        $doc = [
            'id'      => '1',
            'title'   => 'Hello World',
            'content' => 'این یک متن نمونه است',
        ];

        $res = $search->upsertDocument($doc);
        return response()->json([
            'message' => 'Seeded sample document',
            'result'  => $res,
        ]);
    }

    public function search(Request $request, SearchClient $search)
    {
        $q = (string) $request->query('q', 'نمونه'); // پیش‌فرض همون کلمه‌ی شما
        $res = $search->searchByContent($q, size: 10);

        // بسته به خروجی سرویس، ممکنه ساختار متفاوت باشه
        // اینجا همون خام رو برمی‌گردونیم:
        return response()->json([
            'query'  => $q,
            'result' => $res,
        ]);
    }
}
