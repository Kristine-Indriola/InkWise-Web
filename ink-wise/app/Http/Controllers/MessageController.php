<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Staff;
use App\Models\User;
use App\Support\MessageMetrics;

class MessageController extends Controller
{
    protected function resolveMessageActor(): ?array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user) {
            return [
                'id' => $user->getKey(),
                'type' => 'user',
                'name' => $user->name ?? 'Team',
                'email' => $user->email ?? null,
            ];
        }

        /** @var \App\Models\Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if ($staff) {
            $fullName = trim(collect([$staff->first_name, $staff->last_name])->filter()->implode(' '));
            $staffUser = $staff->relationLoaded('user') ? $staff->user : $staff->user()->first();
            return [
                'id' => $staff->getKey(),
                'type' => 'staff',
                'name' => $fullName ?: 'Staff',
                'email' => optional($staffUser)->email,
            ];
        }

        return null;
    }

    /**
     * Prepare data required to render the threaded message list for admin/staff inbox views.
     */
    protected function messageListingData(): array
    {
        // get messages (newest first)
        $messages = Message::orderBy('created_at', 'desc')->get();

        // collect customer and user ids referenced in messages
        $customerIds = $messages->filter(function ($m) {
            return strtolower($m->sender_type) === 'customer' || strtolower($m->receiver_type) === 'customer';
        })->flatMap(function ($m) {
            return [
                strtolower($m->sender_type) === 'customer' ? $m->sender_id : null,
                strtolower($m->receiver_type) === 'customer' ? $m->receiver_id : null,
            ];
        })->filter()->unique()->values()->all();

        $userIds = $messages->filter(function ($m) {
            return strtolower($m->sender_type) === 'user' || strtolower($m->receiver_type) === 'user';
        })->flatMap(function ($m) {
            return [
                strtolower($m->sender_type) === 'user' ? $m->sender_id : null,
                strtolower($m->receiver_type) === 'user' ? $m->receiver_id : null,
            ];
        })->filter()->unique()->values()->all();

        // Use model primary key names (avoid hard-coded 'id' which may not exist)
        $customerKey = (new \App\Models\Customer)->getKeyName();
        $userKey = (new \App\Models\User)->getKeyName();

        $customers = \App\Models\Customer::whereIn($customerKey, $customerIds)
            ->with('user')
            ->get()
            ->keyBy($customerKey);

        $users = \App\Models\User::whereIn($userKey, $userIds)
            ->get()
            ->keyBy($userKey);

        return compact('messages', 'customers', 'users');
    }

    protected function renderMessageIndex(string $view, array $data = [])
    {
        return view($view, $data + $this->messageListingData());
    }

    // List all messages (show newest first) with sender/receiver lookup
    public function index()
    {
        return $this->renderMessageIndex('admin.messages.index', [
            'layout' => 'layouts.admin',
            'threadRouteName' => 'admin.messages.thread',
            'replyRouteName' => 'admin.messages.reply',
        ]);
    }

    // Staff view of messages shares the same data but different layout/view path
    public function staffIndex()
    {
        return $this->renderMessageIndex('staff.messages.index', [
            'layout' => 'layouts.staffapp',
            'threadRouteName' => 'staff.messages.thread',
            'replyRouteName' => 'staff.messages.reply',
        ]);
    }

    // Show chat with a specific customer
    public function chatWithCustomer($customerId)
    {
        $customer = Customer::with('user')->findOrFail($customerId);

        $messages = Message::where(function ($q) use ($customerId) {
                $q->where('sender_id', $customerId)->whereRaw('LOWER(sender_type) = ?', ['customer']);
            })
            ->orWhere(function ($q) use ($customerId) {
                $q->where('receiver_id', $customerId)->whereRaw('LOWER(receiver_type) = ?', ['customer']);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.messages.chat', compact('messages', 'customer'));
    }

    // Admin sends message to customer
    public function sendToCustomer(Request $request, $customerId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $customer = Customer::findOrFail($customerId);
        $actor = $this->resolveMessageActor();
        abort_unless($actor, 403);

        Message::create([
            'sender_id'     => $actor['id'],
            'sender_type'   => $actor['type'],
            'receiver_id'   => $customer->getKey(),
            'receiver_type' => 'customer',
            'message'       => $request->input('message'),
            'name'          => $actor['name'],
            'email'         => $actor['email'],
        ]);

        return redirect()->route('admin.messages.chat', $customer->getKey())->with('success', 'Message sent.');
    }

    // Store message from Contact Form (public side)
    public function storeFromContact(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:1000',
        ]);

        Message::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'message'      => $validated['message'],
            'sender_id'    => null,
            'sender_type'  => 'guest',
            'receiver_id'  => 1,
            'receiver_type'=> 'user',
        ]);

        return back()->with('success', 'Your message has been sent!');
    }

    // Get message thread (all messages with a specific customer or guest)
    public function thread($messageId)
    {
        $original = Message::findOrFail($messageId);

                if (strtolower($original->sender_type ?? '') === 'guest') {
            // thread identified by guest email
            $email = $original->email;
            $thread = Message::where(function ($q) use ($email) {
                $q->where('email', $email)
                  ->orWhere(function ($q2) use ($email) {
                      $q2->where('receiver_type', 'guest')->where('email', $email);
                  });
            })->orderBy('created_at', 'asc')->get();
        } else {
            // for customers - thread by customer id
            $customerId = ($original->sender_type === 'customer') ? $original->sender_id : $original->receiver_id;
            $thread = Message::where(function ($q) use ($customerId) {
                $q->where(function ($q2) use ($customerId) {
                    $q2->where('sender_type', 'customer')->where('sender_id', $customerId);
                })->orWhere(function ($q3) use ($customerId) {
                    $q3->where('receiver_type', 'customer')->where('receiver_id', $customerId);
                });
            })->orderBy('created_at', 'asc')->get();
        }

        MessageMetrics::markThreadSeenForAdmin($original);

        // map to simple payload
        $payload = $thread->map(function ($m) {
            return [
                'id' => $m->getKey(),
                'sender_type' => strtolower($m->sender_type ?? ''),
                'name' => $m->name ?? null,
                'email' => $m->email ?? null,
                'message' => $m->message,
                'created_at' => $m->created_at->toDateTimeString(),
            ];
        });

        $unread = MessageMetrics::adminUnreadCount();

        return response()->json([
            'thread' => $payload,
            'unread_count' => $unread,
        ]);
    }

    /**
     * Reply to a specific message.
     * No email will be sent — store admin reply in messages table only.
     */
    public function replyToMessage(Request $request, $messageId)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        try {
            $original = Message::findOrFail($messageId);
            $replyText = $request->input('message');
            $actor = $this->resolveMessageActor();
            abort_unless($actor, 403);

            // Save reply only (no email)
            if (strtolower($original->sender_type ?? '') === 'guest') {
                // reply saved as from admin (user) to guest (keep guest email on original)
                $reply = Message::create([
                    'sender_id'     => $actor['id'],
                    'sender_type'   => $actor['type'],
                    'receiver_id'   => null,
                    'receiver_type' => 'guest',
                    'message'       => $replyText,
                    'email'         => $original->email,       // guest email (required)
                    'name'          => $actor['name'],
                ]);

                return response()->json(['status' => 'ok', 'reply' => $reply], 200);
            }

            if (strtolower($original->sender_type ?? '') === 'customer' && $original->sender_id) {
                $reply = Message::create([
                    'sender_id'     => $actor['id'],
                    'sender_type'   => $actor['type'],
                    'receiver_id'   => $original->sender_id,
                    'receiver_type' => 'customer',
                    'message'       => $replyText,
                    'name'          => $actor['name'],
                    'email'         => $actor['email'],
                ]);

                return response()->json(['status' => 'ok', 'reply' => $reply], 200);
            }

            // fallback - preserve original receiver_type/id but include admin name/email
            $reply = Message::create([
                'sender_id'     => $actor['id'],
                'sender_type'   => $actor['type'],
                'receiver_id'   => $original->receiver_id,
                'receiver_type' => $original->receiver_type,
                'message'       => $replyText,
                'name'          => $actor['name'],
                'email'         => $actor['email'],
            ]);

            return response()->json(['status' => 'ok', 'reply' => $reply], 200);

        } catch (\Throwable $e) {
            Log::error('replyToMessage error: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Server error: see logs'], 500);
        }
    }

    // Customer inbox: list threads involving this customer
    public function customerIndex()
    {
        $user = Auth::user();
        $customer = $user->customer;
        if (! $customer) {
            return view('customer.messages.index', ['threads' => collect()]);
        }

        $custId = $customer->getKey();

        $threads = Message::where(function($q) use ($custId) {
                $q->where('sender_type', 'customer')->where('sender_id', $custId);
            })
            ->orWhere(function($q) use ($custId) {
                $q->where('receiver_type', 'customer')->where('receiver_id', $custId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // group by conversation key (customer id or guest email)
        $grouped = $threads->groupBy(function($m) use ($custId) {
            if (strtolower($m->sender_type) === 'customer' || strtolower($m->receiver_type) === 'customer') {
                return 'customer:'.$custId;
            }
            // fallback: guest threads keyed by email
            return 'guest:'.($m->email ?? 'unknown');
        });

        return view('customer.messages.index', ['threads' => $grouped]);
    }

    // Customer view thread (messages for this customer's conversation)
    public function customerThread($messageId)
    {
        $user = Auth::user();
        $customer = $user->customer;
        abort_unless($customer, 403);

        $original = Message::findOrFail($messageId);

        // determine thread source: if original involves customer -> thread by customer id
        if ( (strtolower($original->sender_type) === 'customer' && $original->sender_id == $customer->getKey())
          || (strtolower($original->receiver_type) === 'customer' && $original->receiver_id == $customer->getKey()) ) {
            $thread = Message::where(function($q) use ($customer) {
                    $q->where('sender_type', 'customer')->where('sender_id', $customer->getKey());
                })
                ->orWhere(function($q) use ($customer) {
                    $q->where('receiver_type', 'customer')->where('receiver_id', $customer->getKey());
                })
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            // guest thread by email (if message has email)
            $email = $original->email;
            if (! $email) abort(403);
            $thread = Message::where(function($q) use ($email) {
                    $q->where('email', $email)
                      ->orWhere(function($q2) use ($email) {
                          $q2->where('receiver_type', 'guest')->where('email', $email);
                      });
                })
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('customer.messages.thread', ['thread' => $thread]);
    }

    // Customer reply — store message as customer -> user (no email sending)
    public function customerReply(Request $request, $messageId)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = Auth::user();
        $customer = $user->customer;
        abort_unless($customer, 403);

        $original = Message::findOrFail($messageId);

        // determine reply target: if original was to/from a user, reply to that user id; else null
        $targetUserId = null;
        if (strtolower($original->sender_type) === 'user' && $original->sender_id) {
            $targetUserId = $original->sender_id;
        } elseif (strtolower($original->receiver_type) === 'user' && $original->receiver_id) {
            $targetUserId = $original->receiver_id;
        } else {
            // fallback: assign to admin id 1 (adjust if you have multiple admins)
            $targetUserId = 1;
        }

        $reply = Message::create([
            'sender_id'     => $customer->getKey(),
            'sender_type'   => 'customer',
            'receiver_id'   => $targetUserId,
            'receiver_type' => 'user',
            'message'       => $request->input('message'),
            'email'         => $user->email ?? null,
            'name'          => $customer->first_name ?? $user->name ?? null,
        ]);

        // if AJAX return JSON; otherwise redirect back to thread
        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'reply' => $reply], 200);
        }

        return redirect()->route('customer.messages.thread', $messageId)->with('success', 'Reply sent.');
    }

    public function customerChatThread(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;
        if (! $customer) {
            return response()->json(['thread' => []]);
        }

        $custId = $customer->getKey();

        $thread = Message::where(function ($q) use ($custId) {
                $q->where(function ($q2) use ($custId) {
                    $q2->where('sender_type', 'customer')->where('sender_id', $custId);
                })->orWhere(function ($q3) use ($custId) {
                    $q3->where('receiver_type', 'customer')->where('receiver_id', $custId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->getKey(),
                      'sender_type' => strtolower($m->sender_type ?? ''),
                       'name' => $m->name ?? null,
                     'message' => $m->message,
                     'created_at' => $m->created_at->toDateTimeString(),
             ];
        });

        return response()->json(['thread' => $thread]);
    }


    public function customerChatSend(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = Auth::user();
        $customer = $user->customer;
        if (! $customer) {
            return response()->json(['error' => 'Customer not found'], 403);
        }

        // change adminUserId if you have a different admin assignment
        $adminUserId = 1;

        $msg = Message::create([
            'sender_id'     => $customer->getKey(),
            'sender_type'   => 'customer',
            'receiver_id'   => $adminUserId,
            'receiver_type' => 'user',
            'message'       => $request->input('message'),
            'email'         => $user->email ?? null,
            'name'          => $customer->first_name ?? $user->name ?? null,
        ]);

        return response()->json(['status' => 'ok', 'message' => [
            'id' => $msg->getKey(),
            'sender_type' => 'customer',
            'name' => $msg->name,
            'message' => $msg->message,
            'created_at' => $msg->created_at->toDateTimeString()
        ]], 201);
    }

    /**
     * Return unread message count for the authenticated customer.
     * If messages.is_read exists we use it, otherwise we fallback to recent admin messages.
     */
    public function customerUnreadCount(Request $request)
    {
        $user = Auth::user();
        $customer = $user?->customer;
        if (! $customer) {
            return response()->json(['count' => 0]);
        }

        $q = Message::whereRaw('LOWER(sender_type) = ?', ['user'])
            ->where('receiver_type', 'customer')
            ->where('receiver_id', $customer->getKey());

        if (Schema::hasColumn('messages', 'is_read')) {
            $q->where('is_read', 0);
        } else {
            // fallback heuristic: count admin messages in last 7 days
            $q->where('created_at', '>=', now()->subDays(7));
        }

        $count = $q->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark admin->customer messages as read for the authenticated customer.
     * Only updates if messages.is_read exists.
     */
    public function customerMarkRead(Request $request)
    {
        $user = Auth::user();
        $customer = $user?->customer;
        if (! $customer) {
            return response()->json(['ok' => true]);
        }

        if (Schema::hasColumn('messages', 'is_read')) {
            Message::whereRaw('LOWER(sender_type) = ?', ['user'])
                ->where('receiver_type', 'customer')
                ->where('receiver_id', $customer->getKey())
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function adminUnreadCount(Request $request)
    {
        $count = MessageMetrics::adminUnreadCount();

        return response()->json(['count' => $count]);
    }
}
