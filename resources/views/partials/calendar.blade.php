@php
    $calendarSchedules = isset($dailySchedules)
        ? $dailySchedules
        : $allSchedules->where('schedule_date', $selectedDate)->values();
    $calendarReservations = $allReservations
        ->where('status', 'DISETUJUI')
        ->where('reservation_date', $selectedDate)
        ->sortBy('start_time')
        ->values();
    $indoCalDays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $selectedDayLabel = $indoCalDays[\Carbon\Carbon::parse($selectedDate)->dayOfWeekIso] ?? '';
@endphp
<div class="card">
    <div class="card-header" style="flex-wrap:wrap; gap:10px;">
        <div>
            <div class="card-title">Kalender Penggunaan Ruangan</div>
            <div style="font-size:12px; color:#64748b; margin-top:2px;">
                Jadwal pada hari <strong>{{ $selectedDayLabel }}, {{ $selectedDate }}</strong>
                @if($isToday) (Hari Ini) @endif
            </div>
        </div>
        <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:6px; align-items:center;">
            <input type="hidden" name="tab" value="calendar">
            <label style="font-size:12px; color:#475569; font-weight:600;">Tanggal:</label>
            <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control" style="padding:5px 9px; font-size:12px; width:auto;">
            @if(!$isToday)
                <a href="{{ url()->current() }}?tab=calendar" class="btn btn-slate" style="padding:5px 9px; font-size:11.5px;">Hari Ini</a>
            @endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="rr-table">
            <thead>
                <tr>
                    <th>Tanggal & Waktu</th>
                    <th>Ruangan</th>
                    <th>Kegiatan</th>
                    <th>Penanggung Jawab</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calendarSchedules as $cs)
                    <tr>
                        <td>
                            <strong>{{ $selectedDayLabel }}, {{ $cs->schedule_date }}</strong><br>
                            <span style="color:#64748b; font-size:12px;">{{ $cs->start_time }}–{{ $cs->end_time }} WIB</span>
                        </td>
                        <td>
                            <span class="badge badge-navy">{{ $cs->room->room_code }}</span>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $cs->room->room_name }}</div>
                        </td>
                        <td>
                            <strong>{{ $cs->course_name }}</strong>
                            @if(!empty($cs->class_name))
                                <div style="margin-top:3px;"><span class="badge badge-royal">Kelas {{ $cs->class_name }}</span></div>
                            @endif
                        </td>
                        <td>{{ $cs->lecturer->full_name }}</td>
                        <td><span class="badge badge-royal">Perkuliahan</span></td>
                    </tr>
                @endforeach

                @foreach($calendarReservations as $res)
                    <tr>
                        <td>
                            <strong>{{ $selectedDayLabel }}, {{ $res->reservation_date }}</strong><br>
                            <span style="color:#64748b; font-size:12px;">{{ $res->start_time }}–{{ $res->end_time }} WIB</span>
                        </td>
                        <td>
                            <span class="badge badge-navy">{{ $res->room->room_code }}</span>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $res->room->room_name }}</div>
                        </td>
                        <td>
                            <strong>{{ $res->purpose }}</strong>
                            <div style="font-size:12px; color:#64748b;">Peminjam: {{ $res->requester->full_name }}</div>
                        </td>
                        <td>
                            {{ $res->approval?->approver?->full_name ?? '-' }}
                            <div style="font-size:11.5px; color:#64748b;">{{ $res->approval?->approver_type ?? '-' }}</div>
                        </td>
                        <td><span class="badge badge-emerald">Disetujui</span></td>
                    </tr>
                @endforeach

                @if($calendarSchedules->isEmpty() && $calendarReservations->isEmpty())
                    <tr>
                        <td colspan="5" style="text-align:center; color:#64748b; padding:24px;">
                            Belum ada jadwal perkuliahan maupun peminjaman ruangan pada tanggal <strong>{{ $selectedDate }}</strong>.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
