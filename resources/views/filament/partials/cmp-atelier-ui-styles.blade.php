<style>
    .cmp-atelier-section { margin-bottom: 2.5rem !important; }

    .cmp-atelier-loader {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .85rem 1rem;
        margin-bottom: 1rem;
        border-radius: .75rem;
        border: 1px solid rgba(123, 29, 62, .18);
        background: rgba(123, 29, 62, .06);
        color: #4b5563;
        font-size: .875rem;
    }
    .cmp-atelier-spinner {
        width: 1.1rem; height: 1.1rem;
        border: 2px solid rgba(123, 29, 62, .25);
        border-top-color: #7b1d3e;
        border-radius: 9999px;
        animation: cmp-atelier-spin .75s linear infinite;
        flex-shrink: 0;
    }
    @keyframes cmp-atelier-spin { to { transform: rotate(360deg); } }

    .cmp-pointage-wrap {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: .75rem;
        margin-bottom: 1.25rem;
    }
    .dark .cmp-pointage-wrap { border-color: #374151; }

    .cmp-pointage-table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
        font-size: .8125rem;
    }

    .cmp-pointage-table thead th {
        padding: .55rem .65rem;
        text-align: center;
        font-weight: 700;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }
    .dark .cmp-pointage-table thead th { background: #111827; border-color: #374151; }

    .cmp-pointage-table thead th.cmp-th-name { text-align: left; }
    .cmp-pointage-table thead th.cmp-th-num { width: 2.5rem; text-align: center; }

    .cmp-pointage-row td {
        padding: .5rem .65rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    .dark .cmp-pointage-row td { border-color: #1f2937; }

    .cmp-pointage-row:nth-child(even) { background: #fafafa; }
    .dark .cmp-pointage-row:nth-child(even) { background: rgba(255,255,255,.03); }
    .cmp-pointage-row:nth-child(odd) { background: #fff; }
    .dark .cmp-pointage-row:nth-child(odd) { background: transparent; }

    .cmp-pointage-row:hover { background: #fdf4f7 !important; }
    .dark .cmp-pointage-row:hover { background: rgba(123,29,62,.08) !important; }

    .cmp-pointage-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.65rem;
        height: 1.65rem;
        border-radius: 9999px;
        background: #7b1d3e;
        color: #fff;
        font-weight: 700;
        font-size: .72rem;
    }

    .cmp-pointage-name {
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
    }
    .dark .cmp-pointage-name { color: #f3f4f6; }

    .cmp-pointage-meta {
        font-size: .68rem;
        color: #6b7280;
        margin-top: .15rem;
    }

    .cmp-status-cell { text-align: center; }

    .cmp-status-check {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .25rem;
        min-width: 5.5rem;
        padding: .35rem .5rem;
        border-radius: .5rem;
        border: 2px solid var(--status-color, #d1d5db);
        background: #fff;
        color: #374151;
        font-size: .7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s ease;
        user-select: none;
    }
    .dark .cmp-status-check { background: #1f2937; color: #e5e7eb; }

    .cmp-status-check:hover {
        background: color-mix(in srgb, var(--status-color) 12%, white);
    }

    .cmp-status-check.is-active {
        background: var(--status-color);
        border-color: var(--status-color);
        color: #fff;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--status-color) 25%, transparent);
    }

    .cmp-status-check .cmp-check-box {
        width: .85rem;
        height: .85rem;
        border: 2px solid currentColor;
        border-radius: .2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: .55rem;
        line-height: 1;
    }
    .cmp-status-check.is-active .cmp-check-box {
        background: rgba(255,255,255,.25);
        border-color: #fff;
    }

    .cmp-status-check.is-loading { opacity: .5; pointer-events: none; }
    .cmp-status-check.is-readonly { cursor: default; }

    .cmp-th-present { color: #16a34a; }
    .cmp-th-absent { color: #dc2626; }
    .cmp-th-late { color: #d97706; }
    .cmp-th-excused { color: #2563eb; }
    .cmp-th-exit { color: #ea580c; }
    .cmp-th-return { color: #16a34a; }

    .cmp-excuse-row td {
        padding: 0 .65rem .65rem 3.5rem;
        background: inherit;
        border-bottom: 1px solid #e5e7eb;
    }
    .dark .cmp-excuse-row td { border-color: #374151; }

    .cmp-excuse-field {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        max-width: 28rem;
    }
    .cmp-excuse-label {
        font-size: .68rem;
        font-weight: 700;
        color: #2563eb;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .cmp-excuse-input {
        width: 100%;
        border: 2px solid #93c5fd;
        border-radius: .45rem;
        padding: .4rem .55rem;
        font-size: .8125rem;
        background: #eff6ff;
    }
    .dark .cmp-excuse-input {
        background: rgba(37,99,235,.1);
        color: #f3f4f6;
        border-color: #3b82f6;
    }

    .cmp-report-section {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 2px dashed #e5e7eb;
    }
    .dark .cmp-report-section { border-color: #374151; }

    .cmp-report-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    @media (max-width: 768px) {
        .cmp-report-grid { grid-template-columns: 1fr; }
    }

    .cmp-report-field {
        display: flex;
        flex-direction: column;
        gap: .4rem;
        padding: .75rem;
        border-radius: .65rem;
        border: 2px solid var(--field-color, #d1d5db);
        background: color-mix(in srgb, var(--field-color) 4%, white);
    }
    .dark .cmp-report-field {
        background: color-mix(in srgb, var(--field-color) 8%, #111827);
    }
    .cmp-report-field--full { grid-column: 1 / -1; }

    .cmp-report-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--field-color, #374151);
    }

    .cmp-report-input {
        width: 100%;
        border: 1px solid color-mix(in srgb, var(--field-color) 40%, #d1d5db);
        border-radius: .45rem;
        padding: .5rem .65rem;
        font-size: .875rem;
        background: #fff;
    }
    .dark .cmp-report-input {
        background: #0f172a;
        color: #f3f4f6;
        border-color: color-mix(in srgb, var(--field-color) 50%, #374151);
    }
    .cmp-report-input:disabled,
    .cmp-report-input[readonly] {
        opacity: .75;
        cursor: not-allowed;
        background: #f3f4f6;
    }

    .cmp-report-actions {
        margin-top: 1.75rem;
        padding-top: .25rem;
    }

    .cmp-report-locked {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .65rem 1rem;
        border-radius: .5rem;
        background: #fef3c7;
        border: 1px solid #f59e0b;
        color: #92400e;
        font-size: .8125rem;
        margin-bottom: 1rem;
    }
    .dark .cmp-report-locked {
        background: rgba(245,158,11,.12);
        color: #fcd34d;
    }

    .cmp-movement-reason-input {
        width: 100%;
        min-width: 8rem;
        border: 1px solid #d1d5db;
        border-radius: .45rem;
        padding: .35rem .5rem;
        font-size: .75rem;
        background: #fff;
    }
    .dark .cmp-movement-reason-input {
        background: #1f2937;
        color: #f3f4f6;
        border-color: #374151;
    }

    .cmp-movement-history {
        font-size: .68rem;
        color: #6b7280;
        margin-top: .2rem;
        max-width: 14rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
