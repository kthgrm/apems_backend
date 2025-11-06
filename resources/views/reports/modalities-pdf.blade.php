<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modalities Report</title>
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
            font-size: 20px;
            color: #1a365d;
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

        .badge-tv {
            background-color: #3b82f6;
        }

        .badge-radio {
            background-color: #10b981;
        }

        .badge-online {
            background-color: #8b5cf6;
        }

        .badge-other {
            background-color: #6b7280;
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

        .charts-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }

        .chart-container {
            margin: 15px 0;
        }

        .chart-title {
            font-size: 12px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 10px;
        }

        .chart-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 9px;
        }

        .chart-bar {
            background-color: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            flex: 1;
            margin: 0 10px;
            height: 12px;
        }

        .chart-fill {
            background-color: #3b82f6;
            height: 100%;
            transition: width 0.3s ease;
        }

        .time-info {
            font-size: 8px;
            line-height: 1.4;
        }

        .time-label {
            font-weight: bold;
            color: #1a365d;
            background-color: #e8f4f8;
            padding: 1px 4px;
            border-radius: 2px;
            display: inline-block;
            margin-right: 4px;
            margin-bottom: 2px;
            vertical-align: top;
            min-width: 30px;
            text-align: center;
        }

        .period-label {
            font-weight: bold;
            color: #1a365d;
            background-color: #fef3e2;
            padding: 1px 4px;
            border-radius: 2px;
            display: inline-block;
            margin-right: 4px;
            vertical-align: top;
            min-width: 35px;
            text-align: center;
        }

        .time-value {
            display: inline-block;
            vertical-align: top;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Modalities Report</h1>
        <p>Comprehensive overview of all project modalities and delivery methods</p>
        <p>Generated on {{ $generated_at }} by {{ $generated_by }}</p>
    </div>

    @if (!empty(array_filter($filters)))
        <div class="filters">
            <h3>Applied Filters:</h3>
            @if (!empty($filters['campus_id']))
                <div class="filter-item">
                    <strong>Campus ID:</strong> {{ $filters['campus_id'] }}
                </div>
            @endif
            @if (!empty($filters['college_id']))
                <div class="filter-item">
                    <strong>College ID:</strong> {{ $filters['college_id'] }}
                </div>
            @endif
            @if (!empty($filters['modality_type']))
                <div class="filter-item">
                    <strong>Modality Type:</strong> {{ $filters['modality_type'] }}
                </div>
            @endif
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
                    <strong>Sort By:</strong> {{ $filters['sort_by'] }} ({{ $filters['sort_order'] ?? 'desc' }})
                </div>
            @endif
        </div>
    @endif

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Extension Modality</th>
                    <th>Modality Type</th>
                    <th>Project</th>
                    <th>College</th>
                    <th>TV Channel</th>
                    <th>Radio</th>
                    <th>Online Link</th>
                    <th>Time/Period</th>
                    <th>Partner Agency</th>
                    <th>Hosted By</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modalities as $modality)
                    <tr>
                        <td>
                            {{ $modality->techTransfer->name ?? 'N/A' }}<br>
                            <small style="color: #666;">ID: {{ $modality->id }}</small>
                        </td>
                        <td>
                            @php
                                $badgeClass = match (strtolower($modality->modality)) {
                                    'tv' => 'badge-tv',
                                    'radio' => 'badge-radio',
                                    'online' => 'badge-online',
                                    default => 'badge-other',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $modality->modality }}</span>
                        </td>
                        <td>{{ $modality->techTransfer->name ?? 'N/A' }}</td>
                        <td>
                            @if ($modality->techTransfer->college->name ?? false)
                                <small
                                    style="color: #666;">{{ $modality->techTransfer->college->campus->name ?? 'N/A' }}</small><br>
                                {{ $modality->techTransfer->college->name }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $modality->tv_channel ?? '-' }}</td>
                        <td>{{ $modality->radio ?? '-' }}</td>
                        <td style="word-break: break-all;">{{ $modality->online_link ?? '-' }}</td>
                        <td>
                            @if ($modality->time_air || $modality->period)
                                <div class="time-info">
                                    @if ($modality->time_air)
                                        <div style="margin-bottom: 2px;">
                                            <span class="time-label">Time</span><span
                                                class="time-value">{{ $modality->time_air }}</span>
                                        </div>
                                    @endif
                                    @if ($modality->period)
                                        <div>
                                            <span class="period-label">Period</span><span
                                                class="time-value">{{ $modality->period }}</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $modality->partner_agency ?? '-' }}</td>
                        <td>{{ $modality->hosted_by ?? '-' }}</td>
                        <td>
                            {{ $modality->user ? $modality->user->first_name . ' ' . $modality->user->last_name : 'N/A' }}<br>
                            <small
                                style="color: #666;">{{ \Carbon\Carbon::parse($modality->created_at)->format('M d, Y') }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; color: #666;">No modalities found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>
            Modalities Report |
            Generated: {{ $generated_at }} |
            Total Modalities in Report: {{ $modalities->count() }}
        </p>
    </div>
</body>

</html>
