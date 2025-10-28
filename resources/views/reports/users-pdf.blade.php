<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Report</title>
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
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .statistics table {
            border: none;
            margin: 0;
        }

        .statistics td {
            width: 25%;
            text-align: center;
            border: none;
            padding: 0 10px;
        }

        .stat-item {
            text-align: center;
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
            font-size: 9px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #1a365d;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .user-name {
            font-weight: bold;
            color: #1a365d;
        }

        .user-email {
            color: #666;
            font-size: 8px;
            margin-top: 2px;
        }

        .institution {
            font-size: 8px;
        }

        .campus {
            color: #2d3748;
            font-weight: 500;
        }

        .college {
            color: #666;
            font-style: italic;
        }

        .type-badge {
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 7px;
            font-weight: 500;
            display: inline-block;
        }

        .type-admin {
            background-color: #3182ce;
            color: white;
        }

        .type-regular {
            background-color: #e2e8f0;
            color: #2d3748;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 7px;
            font-weight: 500;
            display: inline-block;
        }

        .status-active {
            background-color: #22c55e;
            color: white;
        }

        .status-inactive {
            background-color: #ef4444;
            color: white;
        }

        .date-info {
            font-size: 8px;
            color: #2d3748;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .page-break {
            page-break-before: always;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .summary-section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f7fafc;
            border-radius: 5px;
        }

        .summary-section h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #1a365d;
            border-bottom: 1px solid #cbd5e0;
            padding-bottom: 5px;
        }

        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .summary-item {
            flex: 1;
            min-width: 120px;
            padding: 8px;
            background-color: white;
            border-radius: 3px;
            border-left: 3px solid #3182ce;
        }

        .summary-item .label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-item .value {
            font-size: 12px;
            font-weight: bold;
            color: #1a365d;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>Users Report</h1>
        <p>Comprehensive overview of all system users and their activity</p>
        <p>Generated on {{ $generated_at }} by {{ $generated_by }}</p>
    </div>

    <!-- Statistics -->
    {{-- <div class="statistics">
        <table>
            <tr>
                <td>
                    <div class="stat-item">
                        <h3>{{ number_format($statistics['total_users']) }}</h3>
                        <p>Total Users</p>
                    </div>
                </td>
                <td>
                    <div class="stat-item">
                        <h3>{{ number_format($statistics['admin_users']) }}</h3>
                        <p>Admin Users</p>
                    </div>
                </td>
                <td>
                    <div class="stat-item">
                        <h3>{{ number_format($statistics['active_users']) }}</h3>
                        <p>Active Users</p>
                    </div>
                </td>
                <td>
                    <div class="stat-item">
                        <h3>{{ number_format($statistics['inactive_users']) }}</h3>
                        <p>Inactive Users</p>
                    </div>
                </td>
            </tr>
        </table>
    </div> --}}

    <!-- Filters Applied -->
    @if (array_filter($filters))
        <div class="filters">
            <h3>Filters Applied:</h3>
            @if (!empty($filters['search']))
                <div class="filter-item">
                    <strong>Search:</strong> {{ $filters['search'] }}
                </div>
            @endif
            @if (!empty($filters['user_type']))
                <div class="filter-item">
                    <strong>User Type:</strong> {{ ucfirst($filters['user_type']) }}
                </div>
            @endif
            @if (!empty($filters['status']))
                <div class="filter-item">
                    <strong>Status:</strong> {{ ucfirst($filters['status']) }}
                </div>
            @endif
            @if (!empty($filters['campus_id']))
                <div class="filter-item">
                    <strong>Campus:</strong> {{ $filters['campus_id'] }}
                </div>
            @endif
            @if (!empty($filters['college_id']))
                <div class="filter-item">
                    <strong>College:</strong> {{ $filters['college_id'] }}
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
            @if (!empty($filters['sort_by']))
                <div class="filter-item">
                    <strong>Sort By:</strong> {{ ucfirst(str_replace('_', ' ', $filters['sort_by'])) }}
                    ({{ $filters['sort_order'] ?? 'desc' }})
                </div>
            @endif
        </div>
    @endif

    <!-- Users Table -->
    <div class="table-container">
        @if ($users->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%">User</th>
                        <th style="width: 12%">Type</th>
                        <th style="width: 12%">Status</th>
                        <th style="width: 25%">College</th>
                        <th style="width: 13%">Registration Date</th>
                        <th style="width: 13%">Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div class="user-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                                <div class="user-email">{{ $user->email }}</div>
                            </td>
                            <td>
                                @if ($user->role === 'admin')
                                    <div class="type-badge type-admin">Admin</div>
                                @else
                                    <div class="type-badge type-regular">CESU</div>
                                @endif
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <div class="status-badge status-active">Active</div>
                                @else
                                    <div class="status-badge status-inactive">Inactive</div>
                                @endif
                            </td>
                            <td class="institution">
                                <div class="campus">
                                    {{ $user->college->campus->name ?? 'N/A' }}
                                </div>
                                <div class="college">
                                    {{ $user->college->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="date-info">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="date-info">
                                {{ $user->updated_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No users found matching the specified criteria.</p>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            Users Report |
            Generated: {{ $generated_at }} |
            Total Users in Report: {{ $users->count() }}
        </p>
    </div>
</body>

</html>
