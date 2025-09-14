@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar') 

  <!-- Main -->
  <section class="main-content">


    <div class="panel">
      <h2>Payment Summaries</h2>

      <div class="search-wrap">
        <input class="search-input" type="text" placeholder="Search by Transaction Id" />
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Transaction ID</th>
              <th>Order ID</th>
              <th>customer</th>
              <th>Payment Method</th>
              <th>Date</th>
              <th>Amount (PHP)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>TXN10001</td>
              <td>#1001</td>
              <td>Frechy</td>
              <td>GCash</td>
              <td>2025-04-28</td>
              <td>12,000.00</td>
              <td><span class="pill pill-paid">Paid</span></td>
            </tr>
            <tr>
              <td>TXN10002</td>
              <td>#1002</td>
              <td>Kristine</td>
              <td>COD</td>
              <td>2025-04-28</td>
              <td>7,500.00</td>
              <td><span class="pill pill-pending">Pending</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection
