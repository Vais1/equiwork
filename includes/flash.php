<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sets a flash message to be displayed on the next page load.
 * @param string $type The alert type (success, error, warning, info)
 * @param string $message The message body
 */
function set_flash_message($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Displays and clears all flash messages queue.
 * Outputs proper HTML structure with Tailwind classes and ARIA roles.
 */
function display_flash_messages() {
    if (!isset($_SESSION['flash_messages']) || empty($_SESSION['flash_messages'])) {
        return;
    }

    echo '<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">';

    foreach ($_SESSION['flash_messages'] as $flash) {
        $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
        
        $colors = 'bg-accent/10 border-accent/20 text-accent';
        $role = 'status';
        $ariaLive = 'polite';
        $btnClass = 'text-accent focus:ring-accent';

        if ($type === 'error') {
            $colors = 'bg-red-100 border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300';
            $role = 'alert';
            $ariaLive = 'assertive';
            $btnClass = 'text-red-700 focus:ring-red-500 focus:ring-offset-red-50 dark:text-red-300 dark:focus:ring-red-300 dark:focus:ring-offset-red-900';
        } elseif ($type === 'success') {
            $colors = 'bg-green-100 border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300';
            $btnClass = 'text-green-700 focus:ring-green-500 focus:ring-offset-green-50 dark:text-green-300 dark:focus:ring-green-300 dark:focus:ring-offset-green-900';
        } elseif ($type === 'warning') {
            $colors = 'bg-yellow-100 border-yellow-400 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300';
            $btnClass = 'text-yellow-800 focus:ring-yellow-500 focus:ring-offset-yellow-50 dark:text-yellow-300 dark:focus:ring-yellow-300 dark:focus:ring-offset-yellow-900';
        }

        echo '<div class="' . $colors . ' px-4 py-3 rounded-lg border shadow-sm relative mb-4 flex items-center justify-between" role="' . $role . '" aria-live="' . $ariaLive . '">';
        echo '<span class="block sm:inline font-medium">' . $message . '</span>';
        // Accessible dismiss button
        echo '<button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 opacity-70 transition-opacity duration-300 ease-in-out ' . $btnClass . '" onclick="this.parentElement.remove();" aria-label="Dismiss alert">';
        echo '<svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        echo '</button>';
        echo '</div>';
    }

    echo '</div>';
    unset($_SESSION['flash_messages']);
}
?>
