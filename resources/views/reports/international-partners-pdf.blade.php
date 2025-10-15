<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>International Partners Report</title>
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

        .partner-name {
            font-weight: bold;
            color: #1a365d;
        }

        .activity {
            color: #666;
            font-size: 8px;
            margin-top: 2px;
        }

        .location {
            color: #2d3748;
            font-weight: 500;
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

        .participants {
            color: #2d3748;
            font-weight: 500;
        }

        .committee {
            color: #666;
            font-size: 8px;
            margin-top: 2px;
        }

        .duration {
            color: #2d3748;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 7px;
            font-weight: 500;
            display: inline-block;
        }

        .status-upcoming {
            background-color: #e2e8f0;
            color: #2d3748;
        }

        .status-ongoing {
            background-color: #3182ce;
            color: white;
        }

        .status-completed {
            background-color: #e2e8f0;
            color: #2d3748;
            border: 1px solid #cbd5e0;
        }

        .status-tbd {
            background-color: #e2e8f0;
            color: #2d3748;
        }

        .created-info {
            font-size: 8px;
            color: #666;
        }

        .created-by {
            color: #4a5568;
            font-weight: 500;
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

        .narrative {
            max-width: 150px;
            word-wrap: break-word;
            color: #4a5568;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>International Partners Report</h1>
        <p>Comprehensive overview of all international partnership activities</p>
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
            @if (!empty($filters['participants_min']))
                <div class="filter-item">
                    <strong>Min Participants:</strong> {{ $filters['participants_min'] }}
                </div>
            @endif
            @if (!empty($filters['participants_max']))
                <div class="filter-item">
                    <strong>Max Participants:</strong> {{ $filters['participants_max'] }}
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

    <!-- International Partners Table -->
    <div class="table-container">
        @if ($internationalPartners->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 22%">Partner & Activity</th>
                        <th style="width: 12%">Location</th>
                        <th style="width: 18%">College</th>
                        <th style="width: 12%">Participants</th>
                        <th style="width: 15%">Duration</th>
                        <th style="width: 13%">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($internationalPartners as $partner)
                        <tr>
                            <td>
                                <div class="partner-name">{{ $partner->agency_partner }}</div>
                                @if ($partner->activity_conducted)
                                    <div class="activity">{{ Str::limit($partner->activity_conducted, 80) }}</div>
                                @endif
                            </td>
                            <td class="location">
                                {{ $partner->location ?? 'Not specified' }}
                            </td>
                            <td class="institution">
                                <div class="campus">
                                    {{ $partner->college->campus->name ?? 'N/A' }}
                                </div>
                                <div class="college">
                                    {{ $partner->college->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                <div class="participants">
                                    {{ $partner->number_of_participants ? number_format($partner->number_of_participants) . ' participants' : 'Not specified' }}
                                </div>
                                @if ($partner->number_of_committee)
                                    <div class="committee">Committee: {{ $partner->number_of_committee }}</div>
                                @endif
                            </td>
                            <td class="duration">
                                <div>
                                    {{ $partner->start_date ? $partner->start_date->format('M d, Y') : 'Not specified' }}
                                </div>
                                <div style="color: #666;">to
                                    {{ $partner->end_date ? $partner->end_date->format('M d, Y') : 'Not specified' }}
                                </div>
                            </td>
                            <td class="created-info">
                                <div>{{ $partner->created_at->format('M d, Y') }}</div>
                                <div class="created-by">
                                    {{ $partner->user ? $partner->user->first_name . ' ' . $partner->user->last_name : 'N/A' }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No international partners found matching the specified criteria.</p>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            International Partners Report |
            Generated: {{ $generated_at }} |
            Total Partners in Report: {{ $internationalPartners->count() }}
        </p>
    </div>
</body>

</html>
