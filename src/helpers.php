<?php

if (!function_exists('toast')) {
    /**
     * Dispatch a toast notification.
     *
     * @param string $message The toast message
     * @param string $type The toast type (success, error, warning, info, blank)
     * @param string $description Optional description
     * @return void
     */
    function toast(string $message, string $type = 'success', string $description = ''): void
    {
        $payload = json_encode([
            'type' => $type,
            'message' => $message,
            'description' => $description,
        ]);

        // Use Livewire's js() method to dispatch the event on the client
        app('livewire')->current()->js("Livewire.dispatch('pop-toast', $payload)");
    }
}