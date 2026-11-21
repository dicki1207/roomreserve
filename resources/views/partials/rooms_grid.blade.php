<div class="card">
    <div class="card-header" style="flex-wrap:wrap; gap:12px;">
        <div>
            <div class="card-title">{{ $title ?? 'Daftar Ruangan GKT & Laboratorium TI' }}</div>
            <div style="font-size:12px; color:#64748b; margin-top:2px;">
                Status khusus tanggal <strong>{{ $selectedDate }}</strong>
                @if($isToday)
                    (Hari Ini • {{ $nowTime }} WIB)
                @else
                    (Tanggal Terpilih — jadwal hari lain tidak digabung)
                @endif
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <form method="GET" action="{{ $routeUrl }}" style="display:flex; gap:6px; align-items:center;">
                <input type="hidden" name="tab" value="rooms">
                <label style="font-size:12px; color:#475569; font-weight:600;">Tanggal:</label>
                <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control" style="padding:5px 9px; font-size:12px; width:auto;">
                @if(!$isToday)
                    <a href="{{ $routeUrl }}?tab=rooms" class="btn btn-slate" style="padding:5px 9px; font-size:11.5px;">Hari Ini</a>
                @endif
            </form>
            <button type="button" onclick="filterRooms('ALL')" id="filter-ALL" class="btn btn-navy">Semua ({{ $allRooms->count() }})</button>
            <button type="button" onclick="filterRooms('GKT')" id="filter-GKT" class="btn btn-slate">Gedung GKT</button>
            <button type="button" onclick="filterRooms('TI')" id="filter-TI" class="btn btn-slate">Gedung TI (Lab)</button>
        </div>
    </div>
    <div class="card-body">
        <div class="rooms-grid">
            @foreach($allRooms as $r)
                @php
                    $st = $roomStatuses[$r->id];
                    $approvedSlots = $st['approved_slots'];
                    $classSlots = $st['class_slots'];
                    $hasSlotOnDate = $approvedSlots->isNotEmpty() || $classSlots->isNotEmpty();
                @endphp
                <div class="room-card" data-building="{{ $r->building->name }}">
                    <div style="padding:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <span class="badge badge-navy">{{ $r->room_code }}</span>
                            <span style="font-size:12px; color:#64748b;">Lantai {{ $r->floor }} • {{ $r->capacity }} Kursi/PC</span>
                        </div>
                        <div style="font-weight:600; font-size:14.5px; color:#0f172a; margin-bottom:4px;">{{ $r->room_name }}</div>
                        <div style="font-size:12px; color:#64748b;">
                            Gedung {{ $r->building->name }} • {{ $r->room_type }}
                        </div>
                        @if($r->building->name === 'TI')
                            <div style="margin-top:8px; font-size:12px; color:#475569;">
                                Kalab: {{ $r->labManager?->kalab?->full_name ?? '-' }}
                            </div>
                        @endif

                        @if($hasSlotOnDate && $r->status === 'available')
                            <div style="margin-top:10px; padding:8px 10px; border-radius:7px; background:#fffbeb; border:1px solid #fde68a; font-size:11.5px; color:#92400e; display:flex; flex-direction:column; gap:4px;">
                                <div style="font-weight:700; color:#78350f;">Jadwal Terpakai ({{ $selectedDate }}):</div>
                                @foreach($approvedSlots as $slot)
                                    @php
                                        $isNowSlot = $isToday && ($slot->start_time <= $nowTime && $slot->end_time > $nowTime);
                                    @endphp
                                    <div>
                                        • <strong>{{ $isNowSlot ? 'Sedang Dipinjam' : 'Dipinjam' }} {{ $slot->start_time }}–{{ $slot->end_time }} WIB</strong>
                                        <span style="color:#78350f;">({{ $slot->requester->full_name }})</span>
                                    </div>
                                @endforeach
                                @foreach($classSlots as $cs)
                                    @php
                                        $isNowClass = $isToday && ($cs->start_time <= $nowTime && $cs->end_time > $nowTime);
                                    @endphp
                                    <div style="color:#1e40af;">
                                        • <strong>{{ $isNowClass ? 'Sedang Kuliah' : 'Kuliah' }} {{ $cs->start_time }}–{{ $cs->end_time }} WIB</strong>
                                        — {{ $cs->course_name }}@if(!empty($cs->class_name)) ({{ $cs->class_name }})@endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div style="padding:12px 16px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span class="badge {{ $st['badge_class'] }}">
                            {{ $st['badge_label'] }}
                        </span>
                        @if($r->status === 'available' && ($canBorrow ?? true))
                            <button type="button"
                                    onclick="openReserveModal({{ $r->id }}, '{{ $selectedDate }}')"
                                    class="btn btn-navy" style="padding:6px 12px; font-size:12px;">
                                {{ $st['is_busy_now'] ? 'Pinjam Jam Lain' : 'Pinjam Ruang Ini' }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
