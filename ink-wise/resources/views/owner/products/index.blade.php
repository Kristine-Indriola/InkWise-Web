@extends('layouts.owner.app')

@section('content')
@include('layouts.owner.sidebar')

<section class="main-content">
  <div class="panel">
    <h3>Products</h3>

    <div class="table-wrap">
      <table class="inventory-table" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Name</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">SKU</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Price</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Stock</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $product)
            <tr>
              <td style="padding:8px; border-bottom:1px solid #f6f6f6;">{{ $product->name ?? '-' }}</td>
              <td style="padding:8px; border-bottom:1px solid #f6f6f6;">{{ $product->sku ?? '-' }}</td>
              <td style="padding:8px; border-bottom:1px solid #f6f6f6;">{{ $product->price ?? '-' }}</td>
              <td style="padding:8px; border-bottom:1px solid #f6f6f6;">{{ $product->stock ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" style="padding:18px; text-align:center; color:#64748b;">
                No products yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($products, 'links'))
      <div style="margin-top:12px;">
        {{ $products->links() }}
      </div>
    @endif
  </div>
</section>
@endsection