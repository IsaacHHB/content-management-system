export function toDatetimeLocal(
    value: string | null | undefined,
    timeZone?: string,
): string {
    if (!value) {
        return '';
    }

    const d = new Date(value);

    if (Number.isNaN(d.getTime())) {
        return '';
    }

    if (timeZone) {
        const parts = new Intl.DateTimeFormat('en-CA', {
            timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).formatToParts(d);
        const part = (type: Intl.DateTimeFormatPartTypes) =>
            parts.find((item) => item.type === type)?.value ?? '';

        return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`;
    }

    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/**
 * Turn a `datetime-local` value (naive browser wall-clock) back into an absolute
 * UTC instant. Without this the server re-parses the naive string in app.timezone
 * (UTC) and the timestamp walks by the browser's offset on every save.
 */
export function fromDatetimeLocal(
    value: string | null | undefined,
): string | null {
    if (!value) {
        return null;
    }

    const d = new Date(value);

    return Number.isNaN(d.getTime()) ? null : d.toISOString();
}

/**
 * Date-only columns are cast to `date` but still serialize as a full ISO instant
 * ("2026-07-14T00:00:00.000000Z"), so take the calendar date off the front rather
 * than letting the browser shift it into the previous day.
 */
export function toDateInput(value: string | null | undefined): string {
    return value?.slice(0, 10) ?? '';
}

/** Format an absolute instant (published_at, created_at) as a local calendar date. */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const d = new Date(value);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

/**
 * Format a date-only column (all-day event start_date/end_date). These carry no
 * time zone, so anchor at local noon — reading the raw "…T00:00:00Z" through the
 * browser renders the previous day anywhere west of UTC.
 */
export function formatDateOnly(value: string | null | undefined): string {
    const dateOnly = toDateInput(value);

    if (!dateOnly) {
        return '—';
    }

    const d = new Date(`${dateOnly}T12:00:00`);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function formatDateTime(
    value: string | null | undefined,
    timeZone?: string,
): string {
    if (!value) {
        return '—';
    }

    const d = new Date(value);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZone,
    });
}
