<div class="card">
    <div class="card-header">
        <div class="card-title">Kalender Penggunaan Ruangan</div>
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
                @foreach($allSchedules as $cs)
                    <tr>
                        <td>
                            <strong>{{ $cs->schedule_date }}</strong><br>
                            <span style="color:#64748b; font-size:12px;">{{ $cs->start_time }}–{{ $cs->end_time }} WIB</span>
                        </td>
                        <td>
                            <span class="badge badge-navy">{{ $cs->room->room_code }}</span>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $cs->room->room_name }}</div>
                        </td>
                        <td>
                            <strong>{{ $cs->course_name }}</strong>
                        </td>
                        <td>{{ $cs->lecturer->full_name }}</td>
                        <td><span class="badge badge-royal">Perkuliahan</span></td>
                    </tr>
                @endforeach

                @foreach($allReservations->where('status', 'DISETUJUI') as $res)
                    <tr>
                        <td>
                            <strong>{{ $res->reservation_date }}</strong><br>
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
            </tbody>
        </table>
    </div>
</div>
