@extends('reports.layouts.pdf')

@section('title', __('reports.volunteer_hours_title'))

@section('content')
    <h1>{{ __('reports.volunteer_hours_title') }}</h1>
    <div class="meta">
        <p><strong>{{ __('reports.workspace') }}:</strong> {{ $workspaceName }}</p>
        @if ($supervisorName)
            <p><strong>{{ __('reports.supervisor') }}:</strong> {{ $supervisorName }}</p>
        @endif
        <p><strong>{{ __('reports.generated_on') }}:</strong> {{ $generatedAt }}</p>
    </div>

    @if ($rows->isEmpty())
        <p class="empty">{{ __('reports.empty_hours') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('reports.col_name') }}</th>
                    <th>{{ __('reports.col_email') }}</th>
                    <th>{{ __('reports.col_event') }}</th>
                    <th>{{ __('reports.col_event_date') }}</th>
                    <th>{{ __('reports.col_hours') }}</th>
                    <th>{{ __('reports.col_approved_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['memberName'] }}</td>
                        <td>{{ $row['memberEmail'] }}</td>
                        <td>{{ $row['eventTitle'] }}</td>
                        <td>{{ $row['eventDate'] }}</td>
                        <td>{{ number_format($row['hours'], 2) }}</td>
                        <td>{{ $row['approvedAt'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="summary">{{ __('reports.total_hours') }}: {{ number_format($totalHours, 2) }}</p>
    @endif
@endsection
