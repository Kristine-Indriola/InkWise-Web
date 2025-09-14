@extends('layouts.admin')

@section('title', 'Reports Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">

@endpush

@section('content')


<section class="main-content">
    <div class="topbar">
        <h2>Reports Dashboard</h2>
    </div>

    <div class="tab-buttons">
        <button class="tab-btn active" data-tab="inventory">Inventory Report</button>
        <button class="tab-btn" data-tab="sales">Sales Report</button>
    </div>

    <!-- Inventory Report Tab -->
    <div id="inventory" class="tab-content active">
        <div class="flex gap-3 mb-3">
            <button onclick="exportTableToCSV('inventory-report.csv', 'inventoryTable')" class="export-btn">⬇ Export CSV</button>
            <button onclick="printTable('inventoryTable')" class="export-btn">🖨 Print / Save as PDF</button>
        </div>

        <h3>Inventory Usage Trend</h3>
        <canvas id="inventoryTrendChart" height="100"></canvas>

        <h3 class="mt-4">Inventory Records</h3>
        <table id="inventoryTable" class="inventory-report-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Stock Level</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    @php
                        $stock = $material->inventory->stock_level ?? 0;
                        $reorder = $material->inventory->reorder_level ?? 0;
                        $status = $stock <= 0 ? 'Out' : ($stock <= $reorder ? 'Low' : 'In Stock');
                    @endphp
                    <tr>
                        <td>{{ $material->material_name }}</td>
                        <td>{{ $material->material_type }}</td>
                        <td>{{ $stock }}</td>
                        <td>{{ $reorder }}</td>
                        <td>{{ $status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No materials found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Sales Report Tab -->
    <div id="sales" class="tab-content">
        <div class="flex gap-3 mb-3">
            <button onclick="exportTableToCSV('sales-report.csv', 'salesTable')" class="export-btn">⬇ Export CSV</button>
            <button onclick="printTable('salesTable')" class="export-btn">🖨 Print / Save as PDF</button>
        </div>

        <h3>Monthly Sales Trend</h3>
        <canvas id="salesTrendChart" height="100"></canvas>

        <h3 class="mt-4">Sales Records</h3>
        <table id="salesTable" class="sales-report-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales ?? [] as $sale)
                    <tr>
                        <td>{{ $sale->id }}</td>
                        <td>{{ $sale->customer->name ?? '-' }}</td>
                        <td>{{ $sale->items->pluck('name')->join(', ') }}</td>
                        <td>{{ $sale->items->sum('pivot.quantity') }}</td>
                        <td>{{ number_format($sale->total_price,2) }}</td>
                        <td>{{ $sale->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No sales records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Tab Switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabs = document.querySelectorAll('.tab-content');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabs.forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
        });
    });

    // Export Table to CSV
    function exportTableToCSV(filename, tableId) {
        let csv = [];
        let rows = document.querySelectorAll("#" + tableId + " tr");

        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            csv.push(row.join(","));
        }

        let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        let downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }

    // Print Table Only
    function printTable(tableId) {
        let tableContent = document.getElementById(tableId).outerHTML;
        let win = window.open("");
        win.document.write("<html><head><title>Print Report</title></head><body>");
        win.document.write("<h2>Report Export</h2>");
        win.document.write(tableContent);
        win.document.write("</body></html>");
        win.print();
        win.close();
    }

    // Sales Trend Chart
    const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyLabels ?? []) !!},
            datasets: [{
                label: 'Total Sales',
                data: {!! json_encode($monthlyTotals ?? []) !!},
                borderColor: '#4F46E5',
                backgroundColor: 'rgba(79,70,229,0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // Inventory Trend Chart
    const invCtx = document.getElementById('inventoryTrendChart').getContext('2d');
    new Chart(invCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($materialLabels) !!},
            datasets: [
                { label: 'Stock Level', data: {!! json_encode($materialStockLevels) !!}, backgroundColor: '#4F46E5' },
                { label: 'Reorder Level', data: {!! json_encode($materialReorderLevels) !!}, backgroundColor: '#FBBF24' }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush
