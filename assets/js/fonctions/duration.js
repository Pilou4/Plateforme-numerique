/**
 * Conversions et affichage des durées exprimées en minutes.
 */

/**
 * 0 → "—", 45 → "45 min", 195 → "3 h 15"
 */
export function formatDuration(totalMinutes) {
    if (!totalMinutes) {
        return '—';
    }

    const { hours, minutes } = splitDuration(totalMinutes);

    if (hours === 0) {
        return `${minutes} min`;
    }

    return `${hours} h ${String(minutes).padStart(2, '0')}`;
}

export function splitDuration(totalMinutes) {
    return {
        hours: Math.floor(totalMinutes / 60),
        minutes: totalMinutes % 60,
    };
}

export function joinDuration(hours, minutes) {
    return (hours * 60) + minutes;
}
