@props(['name'])

<svg {{ $attributes->merge(['class' => 'nav-icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('home')
            <path d="m3 11 9-8 9 8" /><path d="M5 10v10h14V10" /><path d="M9 20v-6h6v6" />
            @break
        @case('areas')
            <rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('briefcase')
            <rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2" />
            @break
        @case('fingerprint')
            <path d="M12 11a2 2 0 0 1 2 2c0 3-.5 5.3-1.5 7M8.2 19c.5-1.5.8-3.5.8-6a3 3 0 0 1 6 0c0 1.8-.2 3.6-.7 5.2M5.5 17.5c.3-1.4.5-2.9.5-4.5a6 6 0 0 1 12 0c0 2.8-.4 5.3-1.2 7.5M4 10a8 8 0 0 1 15.2-2.5" />
            @break
        @case('markings')
            <path d="M7 3h10v4H7zM5 5H4a2 2 0 0 0-2 2v13h20V7a2 2 0 0 0-2-2h-1" /><path d="m8 14 2.5 2.5L16 11" />
            @break
        @case('attendance')
            <circle cx="9" cy="8" r="4" /><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 11l2 2 4-4" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" />
            @break
        @case('requests')
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6M8 13h8M8 17h5" />
            @break
        @case('vacations')
            <path d="M12 21V10M12 10c-2.7 0-5-2.2-5-5 2.7 0 5 2.2 5 5ZM12 10c2.7 0 5-2.2 5-5-2.7 0-5 2.2-5 5ZM5 21h14" />
            @break
        @case('progress')
            <path d="M4 19V9M10 19V5M16 19v-7M22 19H2" /><path d="m4 6 5-3 6 4 6-4" />
            @break
        @case('team')
            <circle cx="12" cy="7" r="3" /><circle cx="5" cy="10" r="2" /><circle cx="19" cy="10" r="2" /><path d="M7 21v-2a5 5 0 0 1 10 0v2M1 21v-1a4 4 0 0 1 5-4M23 21v-1a4 4 0 0 0-5-4" />
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            @break
        @case('tasks')
            <path d="M9 6h11M9 12h11M9 18h11M3 6l1 1 2-2M3 12l1 1 2-2M3 18l1 1 2-2" />
            @break
        @default
            <circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" />
    @endswitch
</svg>
