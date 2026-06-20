<?php
declare(strict_types=1);

/**
 * Mature line-icon system. Stroke = currentColor. No emojis.
 * Usage: echo icon('calendar', 'nav-icon');
 */
function icon(string $name, string $class = ''): string {
    $c = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
    $open = '<svg' . $c . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

    $paths = [
        'calendar'   => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
        'navigation' => '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',
        'image'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.6"/><path d="M21 15l-5-5L5 21"/>',
        'team'       => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3.5"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'star'       => '<polygon points="12 2.5 14.85 8.27 21.2 9.2 16.6 13.68 17.69 20 12 17.02 6.31 20 7.4 13.68 2.8 9.2 9.15 8.27 12 2.5"/>',
        'camera'     => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3l2-3h8l2 3h3a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="3.6"/>',
        'trophy'     => '<path d="M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M7 5H4.5a2 2 0 0 0 0 4H7M17 5h2.5a2 2 0 0 1 0 4H17M9 19h6M8 22h8M12 14v5"/>',
        'sun'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.5 4.5l1.4 1.4M18.1 18.1l1.4 1.4M2 12h2M20 12h2M4.5 19.5l1.4-1.4M18.1 5.9l1.4-1.4"/>',
        'moon'       => '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>',
        'bulb'       => '<path d="M9.5 18h5M10.5 21.5h3M12 2.5a6.5 6.5 0 0 0-3.8 11.8c.6.45.9 1.1.9 1.8V17h5.8v-.9c0-.7.3-1.35.9-1.8A6.5 6.5 0 0 0 12 2.5z"/>',
        'upload'     => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12.5"/>',
        'check'      => '<path d="M20 6L9 17l-5-5"/>',
        'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v4.8l3 1.7"/>',
        'home'       => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/><path d="M9.5 21v-6h5v6"/>',
    ];

    if ($name === 'crown') {
        // Filled gold crown (brand)
        $cls = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
        return '<svg' . $cls . ' viewBox="0 0 100 100" aria-hidden="true">'
            . '<path fill="currentColor" d="M18 70 L14 34 L32 48 L50 22 L68 48 L86 34 L82 70 Z"/>'
            . '<rect x="18" y="72" width="64" height="9" rx="2" fill="currentColor"/>'
            . '<circle cx="14" cy="32" r="5" fill="currentColor"/>'
            . '<circle cx="50" cy="20" r="5.5" fill="currentColor"/>'
            . '<circle cx="86" cy="32" r="5" fill="currentColor"/></svg>';
    }

    return $open . ($paths[$name] ?? '') . '</svg>';
}
