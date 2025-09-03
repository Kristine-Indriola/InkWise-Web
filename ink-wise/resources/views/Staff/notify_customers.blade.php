@extends('layouts.Staffapp')

@section('content')
  <h1 class="text-2xl font-semibold mb-4">Notify customers</h1>
  <div class="bg-white p-6 rounded-lg shadow">
    <form action="#" method="POST">
      @csrf
      <label class="block mb-2">Message</label>
      <textarea class="w-full border rounded p-2 mb-4" rows="4" placeholder="Enter notification message..."></textarea>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Send Notification</button>
    </form>
  </div>
@endsection
