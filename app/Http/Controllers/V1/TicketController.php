<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TicketRequest;
use App\Http\Resources\V1\GeneralResource;
use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SendTelegramMessage;
use App\Services\V1\Image\Image;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Ticket
     */
    public function index(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->serverError('Access denied');
        }

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new GeneralResource(Ticket::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(GeneralResource::collection(Ticket::orderBy($order, $typeOrder)->paginate($perPage)));
    }


    /**
     * Get ticket counts grouped by user and status
     *
     * @param Request $request
     * @return JsonResponse
     * @group Ticket
     */
    public function getTicketCountsGroupedByUserAndStatus(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->serverError('Access denied');
        }

        $order = $request->query('order', 'user_id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));

        $allowedOrderFields = ['user_id', 'mobile', 'name', 'answering', 'answered', 'closed', 'last_message_at'];
        if (!in_array($order, $allowedOrderFields)) {
            $order = 'user_id';
        }

        $statuses = Ticket::select('status')->distinct()->pluck('status')->toArray();

        $query = Ticket::select(
            'tickets.user_id',
            'users.mobile',
            'users.name',
            'tickets.status',
            DB::raw('COUNT(*) as count')
        )
            ->join('users', 'users.id', '=', 'tickets.user_id')
            ->groupBy('tickets.user_id', 'tickets.status', 'users.mobile', 'users.name')
            ->get();

        $lastAnswering = Ticket::where('status', 'answering')
            ->select('user_id', DB::raw('MAX(updated_at) as last_message_at'))
            ->groupBy('user_id')
            ->pluck('last_message_at', 'user_id');

        $lastClosed = Ticket::where('status', 'closed')
            ->select('user_id', DB::raw('MAX(updated_at) as last_closed_at'))
            ->groupBy('user_id')
            ->pluck('last_closed_at', 'user_id');

        $result = [];

        foreach ($query as $row) {
            $userId = $row->user_id;
            $status = $row->status;

            if (!isset($result[$userId])) {
                $result[$userId] = [
                    'user_id'         => $userId,
                    'name'            => $row->name,
                    'mobile'          => $row->mobile,
                    'last_message_at' => $lastAnswering[$userId] ?? ($lastClosed[$userId] ?? null),
                ];
                foreach ($statuses as $s) {
                    $result[$userId][$s] = 0;
                }
            }

            $result[$userId][$status] = $row->count;
        }

        $result = array_values($result);

        usort($result, function ($a, $b) use ($order, $typeOrder) {
            if (!isset($a[$order]) || !isset($b[$order])) {
                return 0;
            }

            return $typeOrder === 'asc'
                ? $a[$order] <=> $b[$order]
                : $b[$order] <=> $a[$order];
        });

        return response()->json($result);
    }




    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @group Ticket
     */
    public function getTickets(Request $request, User $user): JsonResponse
    {
        $userId = Auth::id();
        if ($user && Auth::user()->isAdmin()) {
            $userId = $user->id;
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        return response()->jsonMacro(GeneralResource::collection(Ticket::where('user_id', $userId)->orderBy($order, $typeOrder)->paginate($perPage)));
    }



    /**
     * @param Ticket $ticket
     * @return mixed
     * @group Ticket
     */
    public function getConversation(Ticket $ticket): JsonResponse
    {
        if (!Auth::user()->isAdmin() && $ticket->user_id !== Auth::id()) {
            return response()->serverError('Access denied');
        }
        return response()->jsonMacro(GeneralResource::collection(Conversation::where('ticket_id', $ticket->id)->paginate(100)));
    }

    /**
     * @param TicketRequest $request
     * @return JsonResponse
     * @group Telegram
     */
    public function store(TicketRequest $request): JsonResponse
    {
        if ($request->has('ticket_id')) {
            $ticket = Ticket::findOrFail($request->ticket_id);

            if ($ticket->status === Ticket::STATUS_CLOSED) {
                return response()->serverError('Ticket already closed');
            }

            if ($ticket->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->serverError('Access denied');
            }
        } else {
            $ticket = new Ticket();
            $ticket->user_id = Auth::id();
            $ticket->title = $request->title;
            $ticket->category = $request->category;
        }

        $ticket->status = Auth::user()->isAdmin() ? Ticket::STATUS_ANSWERED : Ticket::STATUS_ANSWERING;

        if ($ticket->save()) {
            $conversation = new Conversation();
            $conversation->message = $request->message;
            $conversation->user_id = Auth::id();
            $conversation->ticket_id = $ticket->id;
            $conversation->is_admin = Auth::user()->isAdmin() ? true : false;
            if ($conversation->save()) {

                if($ticket->category == Ticket::CATEGORY_TECHNICAL){
                        $telegram = new SendTelegramMessage();
                        $ticketUrl = 'https://admin.esaj.ir/support/manage/tickets/'.$ticket->id;

                        $telegramMessage = "*تیکت جدید دریافت شد*\n";                
                        $telegramMessage .= "*مشاهده تیکت:* [کلیک کنید]({$ticketUrl})\n\n";
                        $telegramMessage .= "*عنوان:* {$ticket->title}\n";
                        $telegramMessage .= "*پیام:* {$conversation->message}";
                        if (Auth::user()->isWebservice()) {
                            $telegram->sendTelegramMessage($telegramMessage,'8047993174:AAHEEHW5Dzb251VJA9Kes6J69ZQNmFz054Q');
                        }

                        
                }

                if ($request->exists('images')) {
                    Image::modelImages($conversation, $request->file('images'), Image::DRIVER_LOCAL);
                }
                return response()->ok(__('general.savedSuccessfully'));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Ticket $ticket
     * @return mixed
     * @group Telegram
     */
    public function closeTicket(Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->serverError('Access denied');
        }

        $ticket->status = Ticket::STATUS_CLOSED;

        if ($ticket->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
