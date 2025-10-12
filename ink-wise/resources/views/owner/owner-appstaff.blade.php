@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar')

<style>
  /* Dark mode styles */
  .dark-mode .table-container { background:#374151; color:#f9fafb; }
  .dark-mode table { color:#f9fafb; }
  .dark-mode table thead th { background:#4b5563; color:#f9fafb; }
  .dark-mode table tbody td { border-color:#4b5563; }
  .dark-mode table tbody tr:hover { background:#4b5563; }
  .dark-mode body { background:#111827; }
</style>

    <section class="main-content">
    <!-- Table container for pending staff account requests -->
    <div class="table-container">
      <h3>Pending Staff Account Requests</h3>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Requested Role</th>
            <th>Date Requested</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Leanne Baribe</td>
            <td>Baribe@gmail.com</td>
            <td>Manager</td>
            <td>Active</td>
            <td>
              <button class="btn-approve">Approve</button>
              <button class="btn-reject">Reject</button>
            </td>
          </tr>
          <!-- Add more rows as necessary -->
        </tbody>
      </table>
    </div>
  </section>
  @endsection
