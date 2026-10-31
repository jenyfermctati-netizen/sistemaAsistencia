@props(['name'])

<svg {{ $attributes->merge(['class' => 'ui-icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('edit')
            <path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z" />
            @break
        @case('power')
            <path d="M12 2v10" /><path d="M18.4 6.6a9 9 0 1 1-12.8 0" />
            @break
        @case('eye')
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" /><circle cx="12" cy="12" r="2.5" />
            @break
        @case('filter')
            <path d="M4 5h16M7 12h10M10 19h4" />
            @break
        @case('reset')
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5" />
            @break
        @case('sync')
            <path d="M20 7h-5V2" /><path d="M20 7a8 8 0 0 0-14-2M4 17h5v5" /><path d="M4 17a8 8 0 0 0 14 2" />
            @break
        @case('download')
            <path d="M12 3v12" /><path d="m7 10 5 5 5-5M5 21h14" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 18l-6-6 6-6" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />
            @break
        @case('attendance')
            <circle cx="9" cy="8" r="4" /><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 11l2 2 4-4" />
            @break
        @case('alert')
            <path d="M10.3 3.6 2.4 18a2 2 0 0 0 1.8 3h15.6a2 2 0 0 0 1.8-3L13.7 3.6a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4M12 17h.01" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('report')
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Z" /><path d="M14 3v6h6M8 13h8M8 17h5" />
            @break
        @case('fingerprint')
            <path d="M12 11a2 2 0 0 1 2 2c0 3-.6 5.4-1.8 7.2M8.2 20.2A16 16 0 0 0 9 15v-2a3 3 0 0 1 6 0c0 3.3-.5 6-1.4 8" /><path d="M5.1 18.5A13 13 0 0 0 6 13a6 6 0 0 1 12 0c0 2.7-.3 5-.9 7M4 13a8 8 0 0 1 16 0" />
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" />
            @break
        @default
            <circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" />
    @endswitch
</svg>
