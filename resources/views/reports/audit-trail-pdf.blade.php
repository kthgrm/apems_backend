<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail Report</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9px;
        }

        th,
        td {
            border: 1px solid #cbd5e0;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #1a365d;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .timestamp {
            font-size: 9px;
            line-height: 1.3;
        }

        .date {
            font-weight: bold;
            color: #1a365d;
        }

        .time {
            color: #666;
        }

        .user-info {
            font-size: 9px;
            line-height: 1.3;
        }

        .user-name {
            font-weight: bold;
            color: #1a365d;
        }

        .user-email {
            color: #666;
        }

        .action-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 50px;
        }

        .action-create {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .action-update {
            background-color: #bee3f8;
            color: #2c5282;
        }

        .action-delete {
            background-color: #fed7d7;
            color: #c53030;
        }

        .action-login {
            background-color: #e9d8fd;
            color: #553c9a;
        }

        .action-logout {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        .model-info {
            font-size: 9px;
            line-height: 1.3;
        }

        .model-type {
            font-weight: bold;
            color: #1a365d;
        }

        .model-id {
            color: #666;
        }

        .description {
            font-size: 8px;
            color: #4a5568;
            line-height: 1.4;
            max-width: 200px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-box {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            border: 1px solid #e2e8f0;
        }

        .summary-box h4 {
            margin: 0 0 10px 0;
            font-size: 11px;
            color: #1a365d;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 9px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Audit Trail Report</h1>
        <p>Comprehensive overview of all system activities and user actions</p>
        <p>Generated on {{ $generated_at }} by {{ $generated_by }}</p>
    </div>

    <!-- Filters Applied -->
    @if (array_filter($filters))
        <div class="filters">
            <h3>Filters Applied:</h3>
            @if (!empty($filters['search']))
                <div class="filter-item">
                    <strong>Search:</strong> {{ $filters['search'] }}
                </div>
            @endif
            @if (!empty($filters['user_id']))
                <div class="filter-item">
                    <strong>User ID:</strong> {{ $filters['user_id'] }}
                </div>
            @endif
            @if (!empty($filters['action']))
                <div class="filter-item">
                    <strong>Action:</strong> {{ ucfirst($filters['action']) }}
                </div>
            @endif
            @if (!empty($filters['auditable_type']))
                <div class="filter-item">
                    <strong>Model Type:</strong> {{ $filters['auditable_type'] }}
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
        </div>
    @endif

    <!-- Summary Section -->
    <div class="summary-grid">
        <div class="summary-box">
            <h4>Actions Summary</h4>
            @foreach ($statistics['action_counts'] as $action => $count)
                <div class="summary-item">
                    <span>{{ ucfirst($action) }}:</span>
                    <span>{{ number_format($count) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Audit Logs Table -->
    <table>
        <thead>
            <tr>
                <th width="15%">Timestamp</th>
                <th width="20%">User</th>
                <th width="10%">Action</th>
                <th width="15%">Model</th>
                <th width="40%">Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($auditLogs as $log)
                <tr>
                    <td class="timestamp">
                        <div class="date">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y') }}</div>
                        <div class="time">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i:s A') }}</div>
                    </td>
                    <td class="user-info">
                        <div class="user-name">{{ $log->user?->name ?? 'System' }}</div>
                        @if ($log->user?->email)
                            <div class="user-email">{{ $log->user->email }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="action-badge action-{{ $log->action }}">
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="model-info">
                        <div class="model-type">
                            {{ $log->auditable_type ? class_basename($log->auditable_type) : 'N/A' }}
                        </div>
                        @if ($log->auditable_id)
                            <div class="model-id">ID: {{ $log->auditable_id }}</div>
                        @endif
                    </td>
                    <td class="description">
                        {{ $log->description ?: 'No description available' }}
                        @if ($log->old_values && is_array($log->old_values))
                            <br><strong>Changes:</strong> {{ count($log->old_values) }} field(s) modified
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #666;">
                        No audit log entries found matching the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>
            Audit Trail Report |
            Generated: {{ $generated_at }} |
            Total Log Entries in Report: {{ $auditLogs->count() }}
        </p>
    </div>
</body>

</html>
