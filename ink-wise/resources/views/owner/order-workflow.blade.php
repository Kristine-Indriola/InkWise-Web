@extends('layouts.owner.app')
@section('content')
@include('layouts.owner.sidebar') 

  <!-- Main -->
  <section class="main-content">

    <!-- Orders Panel + Search -->
    <div class="orders-table-container">
      <h3 class="orders-title">Confirmed Orders &amp; Status</h3>

      <div class="search-wrap">
        <input class="search-input" type="text" placeholder="Search by order Id, customer, Product" />
      </div>

      <table class="orders-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Date Ordered</th>
            <th>Order Details</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#1001</td>
            <td>Leanne Mae</td>
            <td>2025-04-25</td>
            <td>Wedding Invitations - 100 pcs</td>
            <td><span class="status confirmed">Confirmed</span></td>
          </tr>
          <tr>
            <td>#1002</td>
            <td>Kristine Mae</td>
            <td>2025-04-26</td>
            <td>Keychains - 20 pcs</td>
            <td><span class="status pending">Pending</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection
