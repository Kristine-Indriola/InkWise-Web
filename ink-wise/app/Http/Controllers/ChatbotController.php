<?php

namespace App\Http\Controllers;

use App\Models\ChatQA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatbotController extends Controller
{
    // Admin list view
    public function index()
    {
        $qas = ChatQA::all();
        return view('admin.chatbot.index', compact('qas'));
    }

    // JSON endpoint used by the customer widget
    public function getQAs()
    {
        $qas = ChatQA::select('id', 'question', 'answer', 'answer_image_path')
            ->orderBy('id','desc')
            ->get()
            ->map(function ($qa) {
                return [
                    'id' => $qa->id,
                    'question' => $qa->question,
                    'answer' => $qa->answer,
                    'answer_image_url' => $qa->answer_image_path ? asset('storage/' . ltrim($qa->answer_image_path, '/')) : null,
                ];
            });

        return response()->json($qas);
    }

    // Admin can add a Q&A
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
            'answer_image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
        ];

        if ($request->hasFile('answer_image')) {
            $data['answer_image_path'] = $request->file('answer_image')->store('chatbot_answers', 'public');
        }

        $qa = ChatQA::create($data);

        // return redirect for admin page or JSON if requested
        if ($request->wantsJson()) {
            return response()->json($qa, 201);
        }
        return redirect()->back()->with('success', 'Q&A added.');
    }

    public function update(Request $request, ChatQA $qa)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
            'answer_image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        $data = [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
        ];

        if ($request->boolean('remove_image') && $qa->answer_image_path) {
            Storage::disk('public')->delete($qa->answer_image_path);
            $data['answer_image_path'] = null;
        }

        if ($request->hasFile('answer_image')) {
            if ($qa->answer_image_path) {
                Storage::disk('public')->delete($qa->answer_image_path);
            }
            $data['answer_image_path'] = $request->file('answer_image')->store('chatbot_answers', 'public');
        }

        $qa->update($data);

        if ($request->wantsJson()) {
            return response()->json($qa);
        }
        return redirect()->back()->with('success', 'Q&A updated.');
    }

    public function destroy(ChatQA $qa)
    {
        if ($qa->answer_image_path) {
            Storage::disk('public')->delete($qa->answer_image_path);
        }

        $qa->delete();
        if (request()->wantsJson()) {
            return response()->json(null, 204);
        }
        return redirect()->back()->with('success', 'Q&A deleted.');
    }

    // Handle chatbot reply requests from customer widget
    public function reply(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = strtolower(trim($request->message));

        // Find matching Q&A based on question similarity
        $qas = ChatQA::all();
        $bestMatch = null;
        $bestScore = 0;

        foreach ($qas as $qa) {
            $question = strtolower($qa->question);
            similar_text($userMessage, $question, $score);
            
            if ($score > $bestScore && $score > 60) { // 60% similarity threshold
                $bestScore = $score;
                $bestMatch = $qa;
            }
        }

        if ($bestMatch) {
            return response()->json([
                'reply' => $bestMatch->answer,
                'image_url' => $bestMatch->answer_image_path ? asset('storage/' . ltrim($bestMatch->answer_image_path, '/')) : null
            ]);
        }

        // Default response if no match found
        return response()->json([
            'reply' => 'I\'m sorry, I don\'t have an answer for that question. Please contact our support team for assistance.'
        ]);
    }
}
