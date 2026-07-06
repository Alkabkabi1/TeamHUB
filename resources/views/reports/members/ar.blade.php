@extends('reports.layouts.pdf')

@section('title', __('reports.members_title'))

@section('content')
    <h1>{{ __('reports.members_title') }}</h1>
    <div class="meta">
        <p><strong>{{ __('reports.workspace') }}:</strong> {{ $workspaceName }}</p>
        @if ($supervisorName)
            <p><strong>{{ __('reports.supervisor') }}:</strong> {{ $supervisorName }}</p>
        @endif
        <p><strong>{{ __('reports.generated_on') }}:</strong> {{ $generatedAt }}</p>
    </div>

    @if ($members->isEmpty())
        <p class="empty">{{ __('reports.empty_members') }}</p>
    @else
        <table>
            {{-- Columns are reversed for RTL: DomPDF lays cells out left-to-right,
                 so reversed markup puts the first column on the right. --}}
            <thead>
                <tr>
                    <th>{{ __('reports.col_status') }}</th>
                    <th>{{ __('reports.col_hours') }}</th>
                    <th>{{ __('reports.col_join_date') }}</th>
                    <th>{{ __('reports.col_major') }}</th>
                    <th>{{ __('reports.col_email') }}</th>
                    <th>{{ __('reports.col_name') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($members as $member)
                    <tr>
                        <td>{{ $member['status'] }}</td>
                        <td>{{ number_format($member['volunteerHours'], 2) }}</td>
                        <td>{{ $member['joinDate'] }}</td>
                        <td>{{ $member['major'] }}</td>
                        <td>{{ $member['email'] }}</td>
                        <td>{{ $member['name'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="summary">{{ __('reports.total_hours') }}: {{ number_format($totalHours, 2) }}</p>
    @endif
@endsection
