

@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
  <div class="cards">
    <div class="card">
      <div>🛒</div>
      <h3>Orders</h3>
      <p>20</p>
    </div>
    <div class="card">
      <div>⏳</div>
      <h3>Pending</h3>
      <p>35</p>
    </div>
    <div class="card">
      <div>⭐</div>
      <h3>Rating</h3>
      <p>4.0</p>
    </div>
  </div>

  <div class="stock">
    <h3>Stock Level</h3>
    <table>
      <thead>
        <tr>
          <th>Task</th>
          <th>Asset</th>
          <th>Size/Type</th>
          <th>Quantity</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Matte Paper</td>
          <td><img src="https://via.placeholder.com/40" alt=""></td>
          <td>A4 / 120gsm</td>
          <td>15 packs</td>
          <td><span class="status low">Low Stock</span></td>
        </tr>
        <tr>
          <td>Glossy Paper</td>
          <td><img src="https://via.placeholder.com/40" alt=""></td>
          <td>A4 / 150gsm</td>
          <td>60 packs</td>
          <td><span class="status in">In Stock</span></td>
        </tr>
        <tr>
          <td>Kraft Paper</td>
          <td><img src="https://via.placeholder.com/40" alt=""></td>
          <td>A5 / 100gsm</td>
          <td>5 packs</td>
          <td><span class="status critical">Critical</span></td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
