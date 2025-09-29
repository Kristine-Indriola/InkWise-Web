<?php

namespace App\Http\Controllers;

use App\Models\ChatQA;
use Illuminate\Http\Request;

class ChatQAController extends Controller
{
    public function index()
    {
        $qas = ChatQA::all();
        return view('admin.chatbot.index', compact('qas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
        ]);

        ChatQA::create($request->all());
        return back()->with('success', 'Q&A added!');
    }

    public function update(Request $request, ChatQA $qa)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
        ]);

        $qa->update($request->all());
        return back()->with('success', 'Q&A updated!');
    }

    public function destroy(ChatQA $qa)
    {
        $qa->delete();
        return back()->with('success', 'Q&A deleted!');
    }
}
