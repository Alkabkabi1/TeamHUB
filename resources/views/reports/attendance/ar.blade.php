@extends('reports.layouts.pdf')

@section('title', __('reports.attendance_title'))

@section('content')
    <h1>{{ __('reports.attendance_title') }}</h1>
    <div class="meta">
        <p><strong>{{ __('reports.club') }}:</strong> {{ $clubName }}</p>
        @if ($supervisorName)
            <p><strong>{{ __('reports.supervisor') }}:</strong> {{ $supervisorName }}</p>
        @endif
        <p><strong>{{ __('reports.generated_on') }}:</strong> {{ $generatedAt }}</p>
    </div>

    @if ($events->isEmpty())
        <p class="empty">{{ __('reports.empty_attendance') }}</p>
    @else
        @foreach ($events as $event)
            <div class="event-block">
                <h2>{{ $event['title'] }} — {{ $event['date'] }}</h2>
                <p>{{ __('reports.event_attendee_count', ['count' => $event['attendeeCount']]) }}</p>
                @if ($event['attendees']->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                {{-- Columns reversed for RTL (DomPDF lays cells LTR). --}}
                                <th>{{ __('reports.col_status') }}</th>
                                <th>{{ __('reports.col_email') }}</th>
                                <th>{{ __('reports.col_name') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($event['attendees'] as $attendee)
                                <tr>
                                    <td>{{ $attendee['status'] }}</td>
                                    <td>{{ $attendee['email'] }}</td>
                                    <td>{{ $attendee['name'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
        <p class="summary">{{ __('reports.total_attendees') }}: {{ $totalAttendees }}</p>
    @endif
@endsection
