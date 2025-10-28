<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolutions Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #1a365d;
        }

        .header p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }

        .statistics {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-item h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: #1a365d;
        }

        .stat-item.green h3 {
            color: #059669;
        }

        .stat-item.red h3 {
            color: #dc2626;
        }

        .stat-item.yellow h3 {
            color: #d97706;
        }

        .stat-item p {
            margin: 0;
            font-size: 10px;
            color: #666;
        }

        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e8f4f8;
            border-left: 4px solid #3182ce;
        }

        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #1a365d;
        }

        .filter-item {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 5px;
            font-size: 9px;
        }

        .filter-item strong {
            color: #1a365d;
        }

        .table-container {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 8px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            word-wrap: break-word;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #1a365d;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            color: white;
        }

        .badge-active {
            background-color: #10b981;
        }

        .badge-expired {
            background-color: #ef4444;
        }

        .badge-expiring {
            background-color: #f59e0b;
        }

        .badge-pending {
            background-color: #6366f1;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 8px;
            color: #666;
        }

        .page-break {
            page-break-before: always;
        }

        .summary-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .status-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }

        .status-item {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }

        .status-item.active {
            background-color: #ecfdf5;
            border-color: #10b981;
        }

        .status-item.expired {
            background-color: #fef2f2;
            border-color: #ef4444;
        }

        .status-item.expiring {
            background-color: #fffbeb;
            border-color: #f59e0b;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Resolutions Report</h1>
        <p>Comprehensive overview of all system resolutions and their status</p>
        <p>Generated on {{ $generated_at }} by {{ $generated_by }}</p>
    </div>

    @if (!empty(array_filter($filters)))
        <div class="filters">
            <h3>Applied Filters:</h3>
            @if (!empty($filters['date_from']))
                <div class="filter-item">
                    <strong>From Date:</strong> {{ $filters['date_from'] }}
                </div>
            @endif
            @if (!empty($filters['date_to']))
                <div class="filter-item">
                    <strong>To Date:</strong> {{ $filters['date_to'] }}
                </div>
            @endif
            @if (!empty($filters['search']))
                <div class="filter-item">
                    <strong>Search:</strong> {{ $filters['search'] }}
                </div>
            @endif
            @if (!empty($filters['sort_by']))
                <div class="filter-item">
                    <strong>Sort By:</strong> {{ ucfirst(str_replace('_', ' ', $filters['sort_by'])) }}
                    ({{ $filters['sort_order'] ?? 'desc' }})
                </div>
            @endif
        </div>
    @endif

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Submitted By</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resolutions as $resolution)
                    @php
                        $currentDate = \Carbon\Carbon::now();
                    @endphp
                    <tr>
                        <td style="font-weight: bold;">{{ $resolution->id }}</td>
                        <td>{{ $resolution->title ?? '-' }}</td>
                        <td>
                            <div>
                                <strong>{{ isset($resolution->user) ? $resolution->user->first_name . ' ' . $resolution->user->last_name : 'N/A' }}</strong><br>
                                <small>{{ $resolution->user->email ?? '' }}</small>
                            </div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($resolution->created_at)->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #666;">No resolutions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>
            Resolutions Report |
            Generated: {{ $generated_at }} |
            Total Resolutions in Report: {{ $resolutions->count() }}
        </p>
    </div>
</body>

</html>
