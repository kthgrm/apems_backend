<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects Report</title>
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
            margin: 0;
            font-size: 18px;
            color: #1a365d;
        }

        .stat-item p {
            margin: 5px 0 0 0;
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

        .project-name {
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 3px;
        }

        .project-description {
            color: #666;
            font-size: 8px;
            line-height: 1.3;
        }

        .budget {
            font-family: monospace;
            font-weight: bold;
            color: #2d3748;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 60px;
        }

        .status-upcoming {
            background-color: #fed7d7;
            color: #c53030;
        }

        .status-ongoing {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .status-completed {
            background-color: #bee3f8;
            color: #2c5282;
        }

        .status-tbd {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        .category-badge {
            background-color: #edf2f7;
            color: #4a5568;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            border: 1px solid #cbd5e0;
        }

        .institution {
            font-size: 8px;
            line-height: 1.3;
        }

        .campus {
            color: #1a365d;
            font-weight: bold;
        }

        .college {
            color: #4a5568;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #cbd5e0;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Projects Report</h1>
        <p>Comprehensive overview of all system projects and their details</p>
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
            @if (!empty($filters['category']))
                <div class="filter-item">
                    <strong>Category:</strong> {{ $filters['category'] }}
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

    <!-- Projects Table -->
    <table>
        <thead>
            <tr>
                <th width="20%">Project</th>
                <th width="25%">Description</th>
                <th width="10%">Category</th>
                <th width="12%">Leader</th>
                <th width="15%">College</th>
                <th width="13%">Duration</th>
                <th width="5%">Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
                <tr>
                    <td>
                        <div class="project-name">{{ $project->name }}</div>
                    </td>
                    <td>
                        @if ($project->description)
                            <div class="project-description">
                                {{ $project->description }}
                            </div>
                        @else
                            <div class="project-description" style="color: #999;">Unspecified</div>
                        @endif
                    </td>
                    <td>
                        @if ($project->category)
                            <span class="category-badge">{{ $project->category }}</span>
                        @else
                            <span class="category-badge">Unspecified</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: bold;">{{ $project->leader ?: 'Not assigned' }}</div>
                    </td>
                    <td class="institution">
                        <div class="campus">
                            {{ $project->college?->campus?->name ?: 'N/A' }}
                        </div>
                        <div class="college">
                            {{ $project->college?->name ?: 'N/A' }}
                        </div>
                    </td>
                    <td>
                        @if ($project->start_date)
                            <div>{{ \Carbon\Carbon::parse($project->start_date)->format('M d, Y') }}</div>
                            <div style="color: #666; font-size: 8px;">
                                to
                                {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('M d, Y') : 'TBD' }}
                            </div>
                        @else
                            <div style="color: #666;">Not specified</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ \Carbon\Carbon::parse($project->created_at)->format('M d, Y') }}</div>
                        <div style="color: #666; font-size: 8px;">
                            by
                            {{ $project->user ? $project->user->first_name . ' ' . $project->user->last_name : 'N/A' }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #666;">
                        No projects found matching the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>
            Projects Report |
            Generated: {{ $generated_at }} |
            Total Projects in Report: {{ $statistics['total_projects'] }}
        </p>
    </div>
</body>

</html>
