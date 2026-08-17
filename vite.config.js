import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin-calendar.js',
                'resources/js/admin-panel.js',
                'resources/js/admin-reservas.js',
                'resources/js/admin-datatables.js',
                'resources/js/admin-reports.js',
                'resources/js/student-calendar.js',
                'resources/js/student-booking.js',
                'resources/js/instructor-calendar.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
