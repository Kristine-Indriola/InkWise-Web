@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar') 

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
