 <?php
// Define ROUTED constant to indicate this file was included via the router
define('ROUTED', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security check
require_once __DIR__ . '/app/security.php';

// Include helpers and config
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <title>Maîtrise d'énergie</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Outfit', 'sans-serif'],
                        },
                        colors: {
                            brand: {
                                50: '#eef2ff',
                                100: '#e0e7ff',
                                500: '#6366f1',
                                600: '#4f46e5'
                            }
                        }
                    }
                }
            };
        </script>
        <style type="text/tailwindcss">
            @layer base {
                html {
                    font-size: 16px; /* Base font size (was default 16px, keeping as reference) */
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                
                body {
                    @apply bg-slate-100 text-slate-900 antialiased;
                    font-size: 1.05rem; /* Increase overall font size by 5% */
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
                
                /* Responsive typography - smaller on mobile */
                h1 { 
                    font-size: 1.75rem; /* Smaller on mobile */
                }
                @media (min-width: 640px) {
                    h1 { font-size: 2.25rem; }
                }
                
                h2 { 
                    font-size: 1.5rem; /* Smaller on mobile */
                }
                @media (min-width: 640px) {
                    h2 { font-size: 1.875rem; }
                }
                
                h3 { 
                    font-size: 1.25rem; /* Smaller on mobile */
                }
                @media (min-width: 640px) {
                    h3 { font-size: 1.5rem; }
                }
                
                h4 { 
                    font-size: 1.125rem; /* Smaller on mobile */
                }
                @media (min-width: 640px) {
                    h4 { font-size: 1.25rem; }
                }
                
                h5 { 
                    font-size: 1rem; /* Smaller on mobile */
                }
                @media (min-width: 640px) {
                    h5 { font-size: 1.125rem; }
                }
                
                h6 { 
                    font-size: 0.875rem; /* Smaller on mobile */
                }
                @media (min-width: 640px) {
                    h6 { font-size: 1rem; }
                }
                
                /* Mobile-optimized text sizing */
                @media (max-width: 640px) {
                    p, label, span, div {
                        font-size: 0.95rem; /* Slightly smaller on mobile for better fit */
                    }
                    
                    a {
                        font-size: 0.95rem;
                    }
                }
                
                @media (min-width: 641px) {
                    p, label, span, div {
                        font-size: 1rem; /* Increase text elements by 5% */
                    }
                    
                    a {
                        font-size: 1rem; /* Increase links by 5% */
                    }
                }
                
                a {
                    @apply transition-colors duration-200;
                }
            }

            @layer components {
                #page-wrapper {
                    @apply min-h-screen pt-20 pb-8 px-3 sm:px-4 md:px-6 lg:px-12 md:pt-12 md:ml-64 transition-all duration-300;
                }

                .page-title {
                    @apply text-xl sm:text-2xl md:text-3xl font-semibold text-slate-900 tracking-tight;
                }

                .panel {
                    @apply bg-white shadow-sm rounded-2xl border border-slate-200 overflow-hidden;
                }

                .panel-heading {
                    @apply px-3 py-3 sm:px-4 sm:py-4 md:px-6 md:py-4 border-b border-slate-100 bg-slate-50 text-slate-700 font-semibold text-sm sm:text-base flex items-center justify-between gap-2;
                }

                .panel-body {
                    @apply px-3 py-4 sm:px-4 sm:py-5 md:px-6 md:py-6 bg-white;
                }

                .btn {
                    @apply inline-flex items-center gap-2 rounded-lg border font-medium text-sm px-4 py-2.5 sm:px-4 sm:py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition;
                    min-height: 44px; /* Minimum touch target size for mobile */
                    font-size: 0.9rem; /* Increase button font size */
                }
                
                /* Mobile-optimized button sizing */
                @media (max-width: 640px) {
                    .btn {
                        min-height: 44px;
                        padding: 0.625rem 1rem; /* Larger padding for easier tapping */
                        font-size: 0.875rem;
                    }
                }

                .btn-default {
                    @apply btn border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:ring-brand-100;
                }

                .btn-primary {
                    @apply btn border-transparent bg-brand-600 text-white hover:bg-brand-500 focus:ring-brand-100;
                }

                .btn-danger {
                    @apply btn border-transparent bg-red-600 text-white hover:bg-red-500 focus:ring-red-200;
                }

                .btn-success {
                    @apply btn border-transparent bg-emerald-600 text-white hover:bg-emerald-500 focus:ring-emerald-200;
                }

                .btn-warning {
                    @apply btn border-transparent bg-amber-500 text-white hover:bg-amber-400 focus:ring-amber-200;
                }

                .alert {
                    @apply rounded-xl border px-4 py-3 text-sm font-medium;
                }

                .alert-success {
                    @apply alert border-emerald-200 bg-emerald-50 text-emerald-700;
                }

                .alert-danger {
                    @apply alert border-red-200 bg-red-50 text-red-700;
                }

                .form-horizontal .form-group {
                    @apply mb-4 sm:mb-6 grid gap-2 md:grid-cols-[220px_1fr] md:items-center;
                }

                .form-horizontal .control-label {
                    @apply text-sm font-semibold text-slate-600 md:text-right md:pr-4;
                }

                .form-horizontal .form-group > div {
                    @apply w-full;
                }

                .form-control, select, textarea {
                    @apply w-full rounded-xl border border-slate-200 bg-white px-3 py-3 sm:px-4 sm:py-2.5 text-base sm:text-sm text-slate-700 placeholder:text-slate-400 shadow-sm focus:border-brand-500 focus:ring focus:ring-brand-100;
                    min-height: 44px; /* Minimum touch target for mobile */
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    appearance: none;
                }
                
                /* Mobile-optimized form controls */
                @media (max-width: 640px) {
                    .form-control, select, textarea {
                        font-size: 16px; /* Prevents zoom on iOS */
                        padding: 0.75rem 1rem;
                    }
                    
                    select {
                        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                        background-position: right 0.75rem center;
                        background-repeat: no-repeat;
                        background-size: 1.25em 1.25em;
                        padding-right: 2.75rem;
                    }
                }

                .form-control--enhanced {
                    @apply flex flex-col gap-2;
                }

                .form-control--enhanced > span {
                    @apply text-sm font-semibold text-slate-600;
                }

                .input-with-icon {
                    @apply relative flex items-center;
                }

                .input-icon {
                    @apply pointer-events-none absolute left-3 h-5 w-5 text-slate-400;
                }

                .input--with-icon {
                    @apply pl-10;
                }

                .search-form__row {
                    @apply flex flex-col gap-3 sm:gap-4 lg:flex-row lg:flex-wrap lg:items-end;
                }

                .search-form__field {
                    @apply flex flex-col gap-2 w-full sm:w-auto lg:min-w-[16rem];
                }

                .search-form__field > span {
                    @apply text-sm font-semibold text-slate-600;
                }

                .search-form__actions {
                    @apply flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 pt-2 lg:pt-0 w-full sm:w-auto;
                }
                
                .search-form__actions .btn {
                    @apply w-full sm:w-auto justify-center;
                }

                .ts-wrapper {
                    @apply w-full;
                }

                .ts-wrapper .ts-control {
                    @apply relative w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring focus:ring-brand-100;
                }

                .ts-wrapper .ts-control::after {
                    content: "";
                    position: absolute;
                    right: 1rem;
                    top: 50%;
                    width: 0.45rem;
                    height: 0.45rem;
                    border-right: 2px solid rgba(100, 116, 139, 0.7);
                    border-bottom: 2px solid rgba(100, 116, 139, 0.7);
                    transform: translateY(-40%) rotate(45deg);
                    pointer-events: none;
                }

                .ts-wrapper.ts-has-icon .ts-control {
                    position: relative;
                    padding-left: 2.75rem;
                }

                .table {
                    @apply min-w-full divide-y divide-slate-200;
                    width: 100%;
                }

                .table thead th {
                    @apply bg-slate-50 px-2 py-2 sm:px-3 sm:py-2.5 md:px-4 md:py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500;
                    white-space: nowrap;
                }

                .table tbody td {
                    @apply px-2 py-2 sm:px-3 sm:py-2.5 md:px-4 md:py-3 text-xs sm:text-sm text-slate-700 align-middle;
                    font-size: 0.875rem; /* Slightly smaller on mobile */
                }
                
                /* Mobile table optimizations */
                @media (max-width: 640px) {
                    .table thead th,
                    .table tbody td {
                        padding: 0.5rem 0.375rem;
                        font-size: 0.8125rem;
                    }
                    
                    .table tbody td {
                        word-break: break-word;
                        max-width: 120px;
                    }
                }

                .table tbody tr:nth-child(even) td {
                    @apply bg-slate-50/50;
                }

                .table input,
                .table select,
                .table textarea {
                    @apply rounded-lg px-3 py-2 text-sm shadow-sm;
                    font-size: 0.875rem; /* Increase form elements font size */
                }

                .table .ts-wrapper .ts-control {
                    @apply min-h-[2.5rem] rounded-lg px-3 py-2 text-sm;
                }

                .ts-wrapper.ts-has-icon .ts-control {
                    position: relative;
                    padding-left: 2.75rem;
                }

                .ts-wrapper.ts-has-icon .ts-control::before {
                    content: "";
                    position: absolute;
                    left: 0.85rem;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 1.15rem;
                    height: 1.15rem;
                    background-repeat: no-repeat;
                    background-size: contain;
                    opacity: 0.6;
                }

                .ts-wrapper[data-icon='bus'] .ts-control::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%23567ebb'%3E%3Crect x='4' y='5' width='16' height='11' rx='2'/%3E%3Cpath d='M4 12h16'/%3E%3Cpath d='M7.5 17.5v1.5'/%3E%3Cpath d='M16.5 17.5v1.5'/%3E%3Ccircle cx='8' cy='16' r='1'/%3E%3Ccircle cx='16' cy='16' r='1'/%3E%3C/svg%3E");
                }

                .ts-wrapper[data-icon='station'] .ts-control::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%23567ebb'%3E%3Cpath d='M5 4h9a2 2 0 0 1 2 2v14H5z'/%3E%3Cpath d='M9 8h2'/%3E%3Cpath d='M17 7.5 19 9v5a2 2 0 0 1-2 2h-1'/%3E%3Cpath d='M6 12h6'/%3E%3Ccircle cx='7.5' cy='17.5' r='1.5'/%3E%3Ccircle cx='13.5' cy='17.5' r='1.5'/%3E%3C/svg%3E");
                }

                .ts-wrapper[data-icon='chauffeur'] .ts-control::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%23567ebb'%3E%3Cpath d='M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z'/%3E%3Cpath d='M6 20a6 6 0 0 1 12 0'/%3E%3Cpath d='M9 10h6'/%3E%3Cpath d='M9 7h6'/%3E%3C/svg%3E");
                }

                .ts-wrapper[data-icon='document'] .ts-control::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%23567ebb'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3Cline x1='16' y1='13' x2='8' y2='13'/%3E%3Cline x1='16' y1='17' x2='8' y2='17'/%3E%3Cpolyline points='10 9 9 9 8 9'/%3E%3C/svg%3E");
                }

                .ts-wrapper[data-icon='oil'] .ts-control::before {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%23567ebb'%3E%3Cpath d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/%3E%3C/svg%3E");
                }

                .dataTables_wrapper .dataTables_length select,
                .dataTables_wrapper .dataTables_filter input,
                .dataTables_wrapper .dataTables_paginate .paginate_button {
                    @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm hover:bg-slate-100;
                }

                .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                    @apply bg-brand-600 text-white hover:bg-brand-500;
                }

                .btn-info {
                    @apply btn border-transparent bg-cyan-600 text-white hover:bg-cyan-500 focus:ring-cyan-100;
                }

                .btn-warning {
                    @apply btn border-transparent bg-amber-500 text-white hover:bg-amber-400 focus:ring-amber-100;
                }

                .btn-danger {
                    @apply btn border-transparent bg-red-600 text-white hover:bg-red-500 focus:ring-red-100;
                }

                .btn-info {
                    @apply btn border-transparent bg-blue-600 text-white hover:bg-blue-500 focus:ring-blue-100;
                }

                .btn-success {
                    @apply btn border-transparent bg-green-600 text-white hover:bg-green-500 focus:ring-green-100;
                }

                .dataTables_wrapper .dataTables_info {
                    @apply text-sm text-slate-500 mt-2;
                }

                .btn-export {
                    @apply inline-flex items-center justify-center w-10 h-10 rounded-lg border-2 bg-white shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed;
                }

                .btn-export svg {
                    @apply h-5 w-5 flex-shrink-0;
                }

                .dt-buttons {
                    @apply flex flex-wrap gap-2 mb-3 sm:mb-4;
                }
                
                /* Mobile-optimized export buttons */
                @media (max-width: 640px) {
                    .dt-buttons {
                        gap: 0.5rem;
                    }
                    
                    .dt-button,
                    .dt-btn {
                        min-height: 44px;
                        padding: 0.625rem 0.875rem;
                        font-size: 0.8125rem;
                    }
                }

                .dt-button,
                .dt-btn {
                    @apply inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-100 disabled:opacity-50 disabled:cursor-not-allowed;
                }

                .dt-btn--print,
                .dt-button.buttons-print {
                    @apply border-indigo-500 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:border-indigo-600 focus:ring-indigo-200;
                }

                .dt-btn--excel,
                .dt-button.buttons-excel {
                    @apply border-green-500 bg-green-50 text-green-600 hover:bg-green-100 hover:border-green-600 focus:ring-green-200;
                }

                .dt-btn--pdf,
                .dt-button.buttons-pdf {
                    @apply border-red-500 bg-red-50 text-red-600 hover:bg-red-100 hover:border-red-600 focus:ring-red-200;
                }

                .dropdown-menu {
                    @apply z-50 mt-2 min-w-[12rem] rounded-xl border border-slate-200 bg-white py-2 shadow-xl;
                }

                .dropdown-menu > li > a {
                    @apply block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50;
                }

                .navbar-action {
                    @apply inline-flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white;
                }

                .nav-link {
                    @apply flex items-center gap-2 sm:gap-3 rounded-lg px-3 py-2.5 sm:py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white min-h-[44px];
                }

                .nav-link-active {
                    @apply bg-white/10 text-white shadow-inner;
                }

                .nav-section-title {
                    @apply text-[0.4rem] font-semibold tracking-wide text-slate-400 mb-2.5;
                    line-height: 1.4;
                    padding: 0.5rem 0.5rem;
                    overflow: hidden;
                }
                
                .nav-section-title span:not(.iconify) {
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: block;
                }
                
                .nav-section-title .iconify {
                    flex-shrink: 0;
                }
                
                .nav-section-title > div {
                    flex: 1;
                    min-width: 0;
                    overflow: hidden;
                }
                

                
                .collapsible-menu {
                    transition: opacity 0.2s ease-in-out, max-height 0.3s ease-in-out, margin-top 0.2s ease-in-out;
                    overflow: hidden;
                    opacity: 1;
                    max-height: 1000px;
                }
                
                .collapsible-menu.collapsed {
                    opacity: 0;
                    max-height: 0;
                    margin-top: 0;
                }



                .card {
                    @apply bg-white border border-slate-200 rounded-2xl shadow-sm p-6;
                }

                .badge {
                    @apply inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600;
                }
            }

            @layer utilities {
                .btn-icon {
                    @apply inline-flex items-center justify-center rounded-lg p-2 transition-colors duration-200;
                    min-width: 44px;
                    min-height: 44px;
                }

                .btn-icon-secondary {
                    @apply text-slate-500 hover:bg-slate-100 hover:text-slate-700;
                }

                .btn-icon-primary {
                    @apply text-brand-600 hover:bg-brand-50 hover:text-brand-700;
                }

                .btn-icon-danger {
                    @apply text-red-500 hover:bg-red-50 hover:text-red-700;
                }

                #sidebar {
                    overflow-x: hidden;
                    overflow-y: auto;
                }
                
                .sidebar-gradient {
                    background-color: #0F3B4B;
                }
            }
            
            /* Additional mobile optimizations */
            @media (max-width: 640px) {
                /* Better table scrolling on mobile */
                .panel-body.overflow-x-auto {
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: thin;
                }
                
                /* Improve DataTables mobile experience */
                .dataTables_wrapper {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                
                .dataTables_wrapper .dataTables_length,
                .dataTables_wrapper .dataTables_filter {
                    margin-bottom: 0.75rem;
                }
                
                .dataTables_wrapper .dataTables_length select,
                .dataTables_wrapper .dataTables_filter input {
                    min-height: 44px;
                    font-size: 16px; /* Prevents zoom on iOS */
                    padding: 0.5rem;
                }
                
                /* Better pagination on mobile */
                .dataTables_wrapper .dataTables_paginate {
                    margin-top: 1rem;
                    flex-wrap: wrap;
                    justify-content: center;
                }
                
                .dataTables_wrapper .dataTables_paginate .paginate_button {
                    min-height: 44px;
                    min-width: 44px;
                    padding: 0.5rem 0.75rem;
                    margin: 0.125rem;
                }
                
                /* Better chart containers on mobile */
                .chart-container {
                    padding: 1rem !important;
                }
                
                .chart-container canvas {
                    max-width: 100%;
                    height: auto !important;
                }
                
                /* Card improvements for mobile */
                .card {
                    padding: 1rem !important;
                }
                
                /* Better spacing for stat cards */
                .stat-card {
                    padding: 1rem !important;
                }
                
                /* Improve badge visibility on mobile */
                .badge {
                    padding: 0.375rem 0.625rem;
                    font-size: 0.75rem;
                }
                
                /* Better alert spacing */
                .alert {
                    padding: 0.75rem 1rem;
                    font-size: 0.875rem;
                }
            }
            
            /* Tablet optimizations */
            @media (min-width: 641px) and (max-width: 1024px) {
                .panel-body {
                    padding: 1rem 1.25rem;
                }
                
                .table thead th,
                .table tbody td {
                    padding: 0.5rem 0.75rem;
                }
            }
            
            /* Prevent text selection on buttons for better mobile UX */
            .btn, .btn-icon, button {
                -webkit-tap-highlight-color: transparent;
                user-select: none;
                -webkit-user-select: none;
            }
            
            /* Improve touch targets for interactive elements */
            @media (max-width: 640px) {
                a, button, input, select, textarea {
                    min-height: 44px;
                    min-width: 44px;
                }
                
                /* Exception for text inputs that should be full width */
                input[type="text"],
                input[type="email"],
                input[type="password"],
                input[type="number"],
                input[type="date"],
                input[type="time"],
                textarea {
                    min-width: 100%;
                }
            }

            /* DataTables Custom Styling */
            .dataTables_wrapper .dataTables_length select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 0.5rem center;
                background-repeat: no-repeat;
                background-size: 1.5em 1.5em;
                padding-right: 2.5rem;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }

            .dataTables_wrapper .dataTables_filter input {
                margin-left: 0.5rem;
                display: inline-block;
                width: auto;
            }

            .dataTables_wrapper .dataTables_paginate {
                padding-top: 1rem;
                display: flex;
                justify-content: flex-end;
                gap: 0.25rem;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.5rem 1rem;
                margin: 0;
                border-radius: 0.5rem;
                border: 1px solid #e2e8f0;
                background: white;
                color: #475569 !important;
                cursor: pointer;
                text-decoration: none !important;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background: #f8fafc !important;
                color: #0f172a !important;
                border-color: #cbd5e1;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: #4f46e5 !important;
                color: white !important;
                border-color: #4f46e5;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
                background: #4338ca !important;
                color: white !important;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            div.dt-buttons {
                position: relative;
                float: left;
                margin-bottom: 1rem;
                display: flex;
                gap: 0.5rem;
            }

            button.dt-button {
                background-image: none !important;
                text-shadow: none !important;
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 0.5rem !important;
                padding: 0.5rem 1rem !important;
                background-color: white !important;
                color: #475569 !important;
                font-size: 0.875rem !important;
                font-weight: 500 !important;
                transition: all 0.2s !important;
            }

            button.dt-button:hover {
                background-color: #f8fafc !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
            }

            button.dt-button.buttons-print {
                color: #4f46e5 !important;
                background-color: #eef2ff !important;
                border-color: #e0e7ff !important;
            }

            button.dt-button.buttons-print:hover {
                background-color: #e0e7ff !important;
                border-color: #c7d2fe !important;
            }

            button.dt-button.buttons-excel {
                color: #059669 !important;
                background-color: #ecfdf5 !important;
                border-color: #d1fae5 !important;
            }

            button.dt-button.buttons-excel:hover {
                background-color: #d1fae5 !important;
                border-color: #a7f3d0 !important;
            }

            button.dt-button.buttons-pdf {
                color: #dc2626 !important;
                background-color: #fef2f2 !important;
                border-color: #fee2e2 !important;
            }

            button.dt-button.buttons-pdf:hover {
                background-color: #fee2e2 !important;
                border-color: #fecaca !important;
            }
            @media print {
                #sidebar, #header, .btn-primary, .btn-default, .btn-success, .btn-danger, .panel-heading .badge, .no-print {
                    display: none !important;
                }
                #page-wrapper {
                    margin-left: 0 !important;
                    padding: 0 !important;
                }
                .panel {
                    border: none !important;
                    box-shadow: none !important;
                }
                .panel-heading {
                    background: #f3f4f6 !important;
                    border: 1px solid #e5e7eb !important;
                    -webkit-print-color-adjust: exact;
                }
                body {
                    background: white !important;
                }
            }
        </style>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.5.2/css/buttons.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
        <script src="https://code.iconify.design/1/1.0.7/iconify.min.js"></script>
    </head>
    <body class="min-h-screen">
        <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-slate-900/60 md:hidden"></div>
        <aside id="sidebar" class="sidebar-gradient fixed inset-y-0 left-0 z-50 flex w-72 sm:w-72 flex-col border-r border-white/10 px-3 py-4 sm:px-4 sm:py-6 text-white transition-transform duration-300 ease-in-out md:translate-x-0 md:shadow-xl md:border-white/5 md:px-6 md:py-8 -translate-x-full">
            <div class="mb-8 px-3">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-white/10 rounded-xl">
                        <span class="iconify h-6 w-6 text-white" data-icon="mdi:bus-clock"></span>
                    </div>
                    <div>
                        <p class="text-xl font-black tracking-[0.2em] text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-100 to-slate-300 drop-shadow-sm uppercase">SRTJ F.M.S</p>
                    </div>
                </div>
                <hr class="border-white/30">
            </div>
            <nav class="mt-8 flex-1 space-y-6 overflow-y-auto pr-2">
                <?php
                $userProfile = $_SESSION['profile'] ?? 'NOT_SET';
                $isAdminOrResponsable = in_array($userProfile, ['admin', 'respensable_maitrise']);
                
                // Get profile label
                $profileLabels = [
                    'agent_maitrise' => 'Agent de saisie',
                    'respensable_maitrise' => 'Responsable Maitrise de l\'energie',
                    'responsable_maintenance' => 'Responsable Maintenance',
                    'respensable_maintenance_preventive' => 'Respensable maintenance préventive',
                    'controle_technique' => 'Contrôle Technique',
                    'admin' => 'Administrateur'
                ];
                $profileLabel = $profileLabels[$userProfile] ?? $userProfile;
                $isMaintenanceProfile = in_array($userProfile, ['admin', 'responsable_maintenance', 'controle_technique']);
                ?>


                <?php if (in_array($userProfile, ['agent_maitrise', 'respensable_maitrise', 'admin', 'responsable_maintenance', 'controle_technique'])): ?>
                <div>
                    <button type="button" id="gestionFlotteToggle" class="nav-section-title w-full flex items-center justify-between cursor-pointer hover:text-slate-300 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="iconify h-5 w-5" data-icon="mdi:bus-multiple"></span>
                            <span>Gestion de flotte</span>
                        </div>
                        <span id="gestionFlotteChevron" class="iconify h-4 w-4 transition-transform duration-200 rotate-180" data-icon="mdi:chevron-down"></span>
                    </button>
                    <ul id="gestionFlotteMenu" class="collapsible-menu collapsed mt-3 space-y-1">
                        <li>
                            <a href="<?= url('dashboard-maintenance') ?>" class="nav-link<?= in_array($currentPage, ['dashboard_maintenance.php', 'dashboard'], true) ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:view-dashboard-variant"></span>
                                Tableau de bord
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('liste-vehicule') ?>" class="nav-link<?= $currentPage === 'liste_bus.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:car-multiple"></span>
                                Véhicule
                            </a>
                        </li>
                        <?php if (!in_array($userProfile, ['respensable_maitrise'])): ?>
                        <li>
                            <a href="<?= url('liste-immobilisation') ?>" class="nav-link<?= $currentPage === 'liste_immobilisation.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:car-off"></span>
                                Immobilisation
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Maintenance Sub-header -->
                        <li>
                            <button type="button" id="flotteMaintenanceToggle" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-500 tracking-wider mt-4 mb-1 hover:text-slate-300 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="iconify h-4 w-4" data-icon="mdi:tools"></span>
                                    <span>Maintenance</span>
                                </div>
                                <span id="flotteMaintenanceChevron" class="iconify h-4 w-4 transition-transform duration-200 rotate-180" data-icon="mdi:chevron-down"></span>
                            </button>
                            <ul id="flotteMaintenanceMenu" class="collapsible-menu collapsed space-y-1">
                                <li>
                                    <a href="<?= url('liste-reclamation') ?>" class="nav-link<?= $currentPage === 'liste_reclamation.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:alert"></span>
                                        Réclamation d'anomalie
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-demande') ?>" class="nav-link<?= $currentPage === 'liste_demande.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:alert-circle"></span>
                                        Demande de réparation
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-ordre') ?>" class="nav-link<?= $currentPage === 'liste_ordre.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:clipboard-list-outline"></span>
                                        Ordre de travail
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('disponibilite-vehicules') ?>" class="nav-link<?= $currentPage === 'disponibilite_vehicules.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:bus-clock"></span>
                                        Disponibilité des vehicules
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Passation Sub-header -->
                        <li>
                            <button type="button" id="flottePassationToggle" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-500 tracking-wider mt-4 mb-1 hover:text-slate-300 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="iconify h-4 w-4" data-icon="mdi:handshake-outline"></span>
                                    <span>Passation</span>
                                </div>
                                <span id="flottePassationChevron" class="iconify h-4 w-4 transition-transform duration-200 rotate-180" data-icon="mdi:chevron-down"></span>
                            </button>
                            <ul id="flottePassationMenu" class="collapsible-menu collapsed space-y-1">
                                <li>
                                    <a href="<?= url('passation-demande') ?>" class="nav-link<?= $currentPage === 'passation_demande.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:file-document-edit-outline"></span>
                                        Demande de passation
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('passation-pv') ?>" class="nav-link<?= $currentPage === 'passation_pv.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:file-check-outline"></span>
                                        PV de passation
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- Rapport et analyses -->
                        <?php if ($isAdminOrResponsable || $isMaintenanceProfile): ?>
                        <li>
                            <button type="button" id="flotteRapportToggle" class="w-full flex items-center justify-between px-3 py-2 text-[0.4rem] font-semibold text-slate-500 tracking-wider mt-4 mb-1 hover:text-slate-300 transition-colors overflow-hidden">
                                <div class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden">
                                    <span class="iconify h-4 w-4 flex-shrink-0" data-icon="mdi:chart-box-outline"></span>
                                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">Rapport et analyses</span>
                                </div>
                                <span id="flotteRapportChevron" class="iconify h-4 w-4 transition-transform duration-200 flex-shrink-0 rotate-180" data-icon="mdi:chevron-down"></span>
                            </button>
                            <ul id="flotteRapportMenu" class="collapsible-menu collapsed space-y-1">

                                <li>
                                    <a href="<?= url('historique-maintenance') ?>" class="nav-link<?= $currentPage === 'historique_maintenance.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:file-chart-outline"></span>
                                        Rapport de maintenance
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('statistique-maintenance') ?>" class="nav-link<?= $currentPage === 'statistique_maintenance.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:chart-line-variant"></span>
                                        Statistique maintenance
                                    </a>
                                </li>

                            </ul>
                        </li>

                        <?php if (!in_array($userProfile, ['respensable_maitrise'])): ?>
                        <!-- Ressources -->
                        <li>
                            <button type="button" id="flotteRessourceToggle" class="w-full flex items-center justify-between px-3 py-2 text-[0.4rem] font-semibold text-slate-500 tracking-wider mt-4 mb-1 hover:text-slate-300 transition-colors overflow-hidden">
                                <div class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden">
                                    <span class="iconify h-4 w-4 flex-shrink-0" data-icon="mdi:database-outline"></span>
                                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">Ressources</span>
                                </div>
                                <span id="flotteRessourceChevron" class="iconify h-4 w-4 transition-transform duration-200 flex-shrink-0 rotate-180" data-icon="mdi:chevron-down"></span>
                            </button>
                            <ul id="flotteRessourceMenu" class="collapsible-menu collapsed space-y-1">
                                <li>
                                    <a href="<?= url('liste-checklist-items') ?>" class="nav-link<?= $currentPage === 'liste_checklist_items.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:check-circle"></span>
                                        Checklist Items
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-atelier') ?>" class="nav-link<?= $currentPage === 'liste_atelier.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:warehouse"></span>
                                        Ateliers
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-systeme') ?>" class="nav-link<?= $currentPage === 'liste_systeme.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:cogs"></span>
                                        Systèmes
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-anomalie') ?>" class="nav-link<?= $currentPage === 'liste_anomalie.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:alert-circle-check-outline"></span>
                                        Anomalies
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-maintenance') ?>" class="nav-link<?= $currentPage === 'liste_maintenance.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:account-wrench"></span>
                                        Techniciens
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-intervention') ?>" class="nav-link<?= $currentPage === 'liste_intervention.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:format-list-bulleted"></span>
                                        Interventions
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-article') ?>" class="nav-link<?= $currentPage === 'liste_article.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:package-variant-closed"></span>
                                        Articles PDR
                                    </a>
                                </li>

                            </ul>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (in_array($userProfile, ['agent_maitrise', 'respensable_maitrise', 'admin', 'respensable_maintenance_preventive'])): ?>
                <div>
                    <button type="button" id="maitriseEnergieToggle" class="nav-section-title w-full flex items-center justify-between cursor-pointer hover:text-slate-300 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="iconify h-5 w-5" data-icon="mdi:lightning-bolt-outline"></span>
                            <span>Gestion de l'energie</span>
                        </div>
                        <span id="maitriseEnergieChevron" class="iconify h-4 w-4 transition-transform duration-200 rotate-180" data-icon="mdi:chevron-down"></span>
                    </button>
                    <ul id="maitriseEnergieMenu" class="collapsible-menu collapsed mt-3 space-y-1">
                        <?php if ($isAdminOrResponsable || $isMaintenanceProfile): ?>
                        <li>
                            <a href="<?= url('dashboard') ?>" class="nav-link<?= in_array($currentPage, ['dashboard.php', 'dashboard'], true) ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:view-dashboard"></span>
                                Tableau de bord
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($userProfile, ['agent_maitrise', 'respensable_maitrise', 'admin'])): ?>
                        <li>
                            <a href="<?= url('liste-doc-carburant') ?>" class="nav-link<?= $currentPage === 'liste_doc_carburant.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:file-document-multiple"></span>
                                Documents carburant
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($isAdminOrResponsable): ?>
                        <li>
                            <a href="<?= url('liste-achat') ?>" class="nav-link<?= $currentPage === 'liste_achat.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:gas-station"></span>
                                Stock carburant
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('rapport-consommation') ?>" class="nav-link<?= $currentPage === 'recherche_avancee.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:history"></span>
                                Historique ravitaillements
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('rapport-jour') ?>" class="nav-link<?= $currentPage === 'rapport_jour.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:calendar-text"></span>
                                Rapport journalier
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Moved Maintenance Items -->
                        <li>
                            <a href="<?= url('liste-fiche-entretien') ?>" class="nav-link<?= $currentPage === 'liste_fiche_entretien.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:clipboard-text"></span>
                                Fiche d'entretien
                            </a>
                        </li>

                        <?php if ($isAdminOrResponsable || $userProfile === 'respensable_maintenance_preventive'): ?>
                        <li>
                            <a href="<?= url('planning-entretien') ?>" class="nav-link<?= $currentPage === 'planning_entretien.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:calendar-clock"></span>
                                Planning entretien
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Rapport & analyse -->
                        <?php if ($isAdminOrResponsable): ?>
                        <li>
                            <button type="button" id="maitriseRapportToggle" class="w-full flex items-center justify-between px-3 py-2 text-[0.4rem] font-semibold text-slate-500 tracking-wider mt-4 mb-1 hover:text-slate-300 transition-colors overflow-hidden">
                                <div class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden">
                                    <span class="iconify h-4 w-4 flex-shrink-0" data-icon="mdi:chart-box-outline"></span>
                                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">Rapport & analyse</span>
                                </div>
                                <span id="maitriseRapportChevron" class="iconify h-4 w-4 transition-transform duration-200 flex-shrink-0 rotate-180" data-icon="mdi:chevron-down"></span>
                            </button>
                            <ul id="maitriseRapportMenu" class="collapsible-menu collapsed space-y-1">
                                <li>
                                    <a href="<?= url('etat-consommation-huile') ?>" class="nav-link<?= $currentPage === 'etat_consommation_huile.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:chart-line"></span>
                                        Rapport de consommation
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('rapport-consommation-station') ?>" class="nav-link<?= $currentPage === 'rapport_consommation_station.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:office-building"></span>
                                        Carburant par agence
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('consommation-agence') ?>" class="nav-link<?= $currentPage === 'consommation_agence.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:fuel"></span>
                                        Sortie carburant
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('rapport-consommation-type') ?>" class="nav-link<?= $currentPage === 'rapport_consommation_type.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:chart-pie"></span>
                                        Statistiques par genre
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('rapport-consommation-marque') ?>" class="nav-link<?= $currentPage === 'rapport_consommation_marque.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:tag"></span>
                                        Statistiques par marque
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('rapport-conso-huile') ?>" class="nav-link<?= $currentPage === 'rapport_conso_huile.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:oil"></span>
                                        Statistiques lubrifiants
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('statistique-carburant') ?>" class="nav-link<?= $currentPage === 'statistique_carburant.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:chart-bar"></span>
                                        Statistique carburant
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Ressources -->
                        <li>
                            <button type="button" id="maitriseRessourceToggle" class="w-full flex items-center justify-between px-3 py-2 text-[0.4rem] font-semibold text-slate-500 tracking-wider mt-4 mb-1 hover:text-slate-300 transition-colors overflow-hidden">
                                <div class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden">
                                    <span class="iconify h-4 w-4 flex-shrink-0" data-icon="mdi:database-outline"></span>
                                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">Ressources</span>
                                </div>
                                <span id="maitriseRessourceChevron" class="iconify h-4 w-4 transition-transform duration-200 flex-shrink-0 rotate-180" data-icon="mdi:chevron-down"></span>
                            </button>
                            <ul id="maitriseRessourceMenu" class="collapsible-menu collapsed space-y-1">
                                <li>
                                    <a href="<?= url('liste-agence') ?>" class="nav-link<?= $currentPage === 'liste_station.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:office-building"></span>
                                        Agences
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-chauffeur') ?>" class="nav-link<?= $currentPage === 'liste_chauffeur.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:account"></span>
                                        Chauffeurs
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-huile') ?>" class="nav-link<?= $currentPage === 'liste_huile.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:oil"></span>
                                        Huiles
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-liquides') ?>" class="nav-link<?= $currentPage === 'liste_liquides.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:water"></span>
                                        Liquides
                                    </a>
                                </li>
                                <li>
                                    <a href="<?= url('liste-filtres') ?>" class="nav-link<?= $currentPage === 'liste_filtres.php' ? ' nav-link-active' : '' ?>">
                                        <span class="iconify h-5 w-5" data-icon="mdi:filter"></span>
                                        Filtres
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($userProfile, ['agent_maitrise', 'respensable_maitrise', 'admin'])): ?>
                <div>
                    <button type="button" id="gestionKilometrageToggle" class="nav-section-title w-full flex items-center justify-between cursor-pointer hover:text-slate-300 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="iconify h-5 w-5" data-icon="mdi:speedometer"></span>
                            <span>Gestion kilomètrage</span>
                        </div>
                        <span id="gestionKilometrageChevron" class="iconify h-4 w-4 transition-transform duration-200 rotate-180" data-icon="mdi:chevron-down"></span>
                    </button>
                    <ul id="gestionKilometrageMenu" class="collapsible-menu collapsed mt-3 space-y-1">
                        <?php if (in_array($userProfile, ['agent_maitrise', 'respensable_maitrise', 'admin'])): ?>
                        <li>
                            <a href="<?= url('liste-kilometrage') ?>" class="nav-link<?= $currentPage === 'liste_kilometrage.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:map-marker-distance"></span>
                                Kilomètrage d'exploitation
                            </a>
                        </li>
                        <li>
                            <a href="" class="nav-link<?= $currentPage === 'liste_kilometrage_gps.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:map-marker"></span>
                                Kilomètrage GPS
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($isAdminOrResponsable): ?>
                        <li>
                            <a href="<?= url('historique-kilometrage') ?>" class="nav-link<?= $currentPage === 'historique_kilometrage.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:history"></span>
                                Historique kilomètrage
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                
                

                
                <?php if ($userProfile === 'admin'): ?>
                <div>
                    <button type="button" id="gestionUtilisateursToggle" class="nav-section-title w-full flex items-center justify-between cursor-pointer hover:text-slate-300 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="iconify h-5 w-5" data-icon="mdi:account-cog"></span>
                            <span>Gestion utilisateurs</span>
                        </div>
                        <span id="gestionUtilisateursChevron" class="iconify h-4 w-4 transition-transform duration-200 rotate-180" data-icon="mdi:chevron-down"></span>
                    </button>
                    <ul id="gestionUtilisateursMenu" class="collapsible-menu collapsed mt-3 space-y-1">
                        <li>
                            <a href="<?= url('list-user') ?>" class="nav-link<?= $currentPage === 'list-user.php' ? ' nav-link-active' : '' ?>">
                                <span class="iconify h-5 w-5" data-icon="mdi:account-group"></span>
                                Gestion des utilisateurs
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </nav>
            
            <!-- Menu Search -->
            <div class="mt-2 px-2 py-2 border-t border-white/10">
                <div class="relative">
                    <input 
                        type="text" 
                        id="menuSearch" 
                        placeholder="Rechercher..." 
                        class="w-full pl-9 pr-9 py-2 text-sm bg-slate-800/50 border border-white/10 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all"
                    />
                    <button id="menuSearchClear" class="iconify absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 hover:text-white transition-colors hidden" data-icon="mdi:close"></button>
                </div>
                <div id="noResultsMessage" class="hidden mt-2 text-xs text-slate-400 text-center">
                    Aucun résultat trouvé
                </div>
            </div>
            <div class="mt-4 border-t border-white/10 pt-3">
                <div class="px-3 py-1">
                    <p class="text-xs text-slate-400 text-center leading-tight">
                        © <?php echo date('Y'); ?> SRTJ - Développé par A. Adib & R. Hatem, Encaré par Med Ali Boubakri
                    </p>
                </div>
            </div>
        </aside>
        <div class="md:ml-72">
            <header class="sticky top-0 z-30 flex items-center justify-between bg-white/80 px-3 py-2.5 sm:px-4 sm:py-3 backdrop-blur md:px-8">
                <div class="flex items-center gap-2 sm:gap-3">
                    <button id="sidebarToggle" type="button" class="inline-flex items-center justify-center rounded-lg bg-slate-900/10 px-3 py-2.5 sm:px-3 sm:py-2 text-sm font-medium text-slate-900 hover:bg-slate-900/20 md:hidden min-h-[44px] min-w-[44px]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16" />
                            <path d="M4 12h16" />
                            <path d="M4 18h16" />
                        </svg>
                        <span class="ml-2 hidden sm:inline">Menu</span>
                    </button>
                 
                    <div class="md:hidden flex items-center gap-2">
                        <svg class="h-6 w-6 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/>
                            <path d="M3 9h18"/>
                            <path d="M3 9v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/>
                            <path d="M8 19v2"/>
                            <path d="M16 19v2"/>
                            <circle cx="8" cy="21" r="1"/>
                            <circle cx="16" cy="21" r="1"/>
                            <rect x="6" y="11" width="4" height="2" rx="0.5"/>
                            <rect x="14" y="11" width="4" height="2" rx="0.5"/>
                            <rect x="10" y="11" width="4" height="2" rx="0.5"/>
                        </svg>
                        <div>
                            <p class="text-xs font-semibold  tracking-wider text-slate-400">SRTJ</p>
                            <p class="text-sm font-semibold text-slate-900">Maîtrise d'énergie</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <button id="userMenuToggle" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-2 sm:px-3 sm:py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 min-h-[44px]">
                        <span class="inline-flex h-8 w-8 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-slate-900/10 text-slate-700 flex-shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
                                <path d="M6 20a6 6 0 0 1 12 0" />
                            </svg>
                        </span>
                        <span class="hidden sm:inline">Mon compte</span>
                        <svg class="h-4 w-4 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                    <ul id="userMenu" class="dropdown-menu absolute right-0 mt-2 hidden w-48">
                        <li class="px-4 py-2 text-xs font-medium text-slate-500 border-b border-slate-100">
                            <?= htmlspecialchars($profileLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                        <li>
                            <a href="changer_pass.php" class="hover:bg-slate-100">Mot de passe</a>
                        </li>
                        <li>
                            <a href="deconnexion.php" class="text-red-600 hover:bg-red-50 hover:text-red-700">Déconnexion</a>
                        </li>
                    </ul>
                </div>
            </header>
        </div>

        <!-- Toast Container -->
        <div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-2"></div>

        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const toggle = document.getElementById('sidebarToggle');
                const userMenuToggle = document.getElementById('userMenuToggle');
                const userMenu = document.getElementById('userMenu');

                const enhanceSelects = () => {
                    if (typeof TomSelect === 'undefined') {
                        return;
                    }

                    const selects = document.querySelectorAll('select:not([data-skip-tom-select="true"])');
                    selects.forEach((select) => {
                        if (select.tomselect) {
                            return;
                        }

                        if (select.closest('.dataTables_length')) {
                            return;
                        }

                        const isMultiple = select.multiple;
                        const placeholder = select.dataset.placeholder || select.getAttribute('placeholder') || (select.options[0] ? select.options[0].textContent.trim() : 'Sélectionner...');
                        const searchPlaceholder = select.dataset.searchPlaceholder || 'Rechercher...';

                        const plugins = {
                            dropdown_input: {
                                placeholder: searchPlaceholder
                            }
                        };

                        if (isMultiple) {
                            plugins.remove_button = {
                                title: 'Retirer'
                            };
                        }

                        const instance = new TomSelect(select, {
                            create: false,
                            allowEmptyOption: true,
                            selectOnTab: true,
                            placeholder,
                            dropdownParent: 'body',
                            sortField: { field: 'text', direction: 'asc' },
                            plugins
                        });

                        const icon = select.dataset.icon;
                        if (icon && instance.wrapper) {
                            instance.wrapper.classList.add('ts-has-icon');
                            instance.wrapper.setAttribute('data-icon', icon);
                        }
                    });
                };

                if (!sidebar || !overlay) {
                    enhanceSelects();
                    return;
                }

                const openSidebar = () => {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                };

                const closeSidebar = () => {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                };

                toggle?.addEventListener('click', () => {
                    if (sidebar.classList.contains('-translate-x-full')) {
                        openSidebar();
                    } else {
                        closeSidebar();
                    }
                });

                overlay.addEventListener('click', closeSidebar);

                window.addEventListener('resize', () => {
                    if (window.matchMedia('(min-width: 768px)').matches) {
                        sidebar.classList.remove('-translate-x-full');
                        overlay.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    } else {
                        sidebar.classList.add('-translate-x-full');
                    }
                });

                if (userMenuToggle && userMenu) {
                    userMenuToggle.addEventListener('click', (event) => {
                        event.stopPropagation();
                        userMenu.classList.toggle('hidden');
                    });

                    document.addEventListener('click', (event) => {
                        if (!userMenu.contains(event.target) && !userMenuToggle.contains(event.target)) {
                            userMenu.classList.add('hidden');
                        }
                    });
                }

                // Generic collapsible functionality
                const setupCollapsible = (toggleId, menuId, chevronId, storageKey) => {
                    const toggle = document.getElementById(toggleId);
                    const menu = document.getElementById(menuId);
                    const chevron = document.getElementById(chevronId);

                    if (toggle && menu && chevron) {
                        menu.dataset.storageKey = storageKey; // Store key for accordion logic
                        
                        // Check localStorage for saved state (default: collapsed)
                        const savedState = localStorage.getItem(storageKey);
                        const isCollapsed = savedState === null || savedState === 'true';

                        if (isCollapsed) {
                            menu.classList.add('collapsed');
                            chevron.classList.add('rotate-180');
                        } else {
                            menu.classList.remove('collapsed');
                            chevron.classList.remove('rotate-180');
                        }

                        toggle.addEventListener('click', (e) => {
                            e.stopPropagation(); // Prevent bubbling issues with nested toggles
                            const isCollapsed = menu.classList.contains('collapsed');

                            if (isCollapsed) {
                                // Accordion logic: close others
                                document.querySelectorAll('.collapsible-menu').forEach(otherMenu => {
                                    if (otherMenu !== menu && !otherMenu.contains(menu)) {
                                        otherMenu.classList.add('collapsed');
                                        const otherChevronId = otherMenu.id.replace('Menu', 'Chevron');
                                        const otherChevron = document.getElementById(otherChevronId);
                                        if (otherChevron) otherChevron.classList.add('rotate-180');
                                        
                                        const otherKey = otherMenu.dataset.storageKey;
                                        if (otherKey) localStorage.setItem(otherKey, 'true');
                                    }
                                });

                                menu.classList.remove('collapsed');
                                chevron.classList.remove('rotate-180');
                                localStorage.setItem(storageKey, 'false');
                            } else {
                                menu.classList.add('collapsed');
                                chevron.classList.add('rotate-180');
                                localStorage.setItem(storageKey, 'true');
                            }
                        });
                    }
                };

                // Setup all collapsible sections
                setupCollapsible('maitriseEnergieToggle', 'maitriseEnergieMenu', 'maitriseEnergieChevron', 'maitriseEnergieCollapsed');
                setupCollapsible('maitriseRapportToggle', 'maitriseRapportMenu', 'maitriseRapportChevron', 'maitriseRapportCollapsed');
                setupCollapsible('maitriseRessourceToggle', 'maitriseRessourceMenu', 'maitriseRessourceChevron', 'maitriseRessourceCollapsed');
                
                setupCollapsible('gestionKilometrageToggle', 'gestionKilometrageMenu', 'gestionKilometrageChevron', 'gestionKilometrageCollapsed');
                
                setupCollapsible('gestionFlotteToggle', 'gestionFlotteMenu', 'gestionFlotteChevron', 'gestionFlotteCollapsed');
                setupCollapsible('flotteMaintenanceToggle', 'flotteMaintenanceMenu', 'flotteMaintenanceChevron', 'flotteMaintenanceCollapsed');
                setupCollapsible('flottePassationToggle', 'flottePassationMenu', 'flottePassationChevron', 'flottePassationCollapsed');
                setupCollapsible('flotteRapportToggle', 'flotteRapportMenu', 'flotteRapportChevron', 'flotteRapportCollapsed');
                setupCollapsible('flotteRessourceToggle', 'flotteRessourceMenu', 'flotteRessourceChevron', 'flotteRessourceCollapsed');
                
                setupCollapsible('gestionUtilisateursToggle', 'gestionUtilisateursMenu', 'gestionUtilisateursChevron', 'gestionUtilisateursCollapsed');

                // Auto-expand menu for active page
                const activeLink = document.querySelector('.nav-link-active');
                if (activeLink) {
                    let parent = activeLink.parentElement;
                    while (parent && parent !== sidebar) {
                        if (parent.classList.contains('collapsible-menu')) {
                            parent.classList.remove('collapsed');
                            const chevronId = parent.id.replace('Menu', 'Chevron');
                            const chevron = document.getElementById(chevronId);
                            if (chevron) {
                                chevron.classList.remove('rotate-180');
                            }
                        }
                        parent = parent.parentElement;
                    }
                }

                enhanceSelects();

                const mutationObserver = new MutationObserver((mutations) => {
                    for (const mutation of mutations) {
                        for (const node of mutation.addedNodes) {
                            if (node.nodeType !== Node.ELEMENT_NODE) {
                                continue;
                            }

                            if (node.matches?.('select:not([data-skip-tom-select="true"])') || node.querySelector?.('select:not([data-skip-tom-select="true"])')) {
                                enhanceSelects();
                                return;
                            }
                        }
                    }
                });

                mutationObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
            
            // Menu Search Functionality
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('menuSearch');
                const clearButton = document.getElementById('menuSearchClear');
                const noResultsMessage = document.getElementById('noResultsMessage');
                const menuLinks = document.querySelectorAll('#sidebar .nav-link');
                const menuSections = document.querySelectorAll('#sidebar nav > div');
                
                // Store original collapsed state
                const originalCollapsedState = new Map();
                document.querySelectorAll('.collapsible-menu').forEach(menu => {
                    originalCollapsedState.set(menu.id, menu.classList.contains('collapsed'));
                });
                
                function performSearch(query) {
                    query = query.toLowerCase().trim();
                    let hasResults = false;
                    
                    if (!query) {
                        // Reset: show all items and restore original collapsed state
                        menuLinks.forEach(link => {
                            link.closest('li').style.display = '';
                        });
                        menuSections.forEach(section => {
                            section.style.display = '';
                        });
                        
                        // Restore original collapsed state
                        document.querySelectorAll('.collapsible-menu').forEach(menu => {
                            const wasCollapsed = originalCollapsedState.get(menu.id);
                            const chevronId = menu.id.replace('Menu', 'Chevron');
                            const chevron = document.getElementById(chevronId);
                            
                            if (wasCollapsed) {
                                menu.classList.add('collapsed');
                                if (chevron) chevron.classList.add('rotate-180');
                            } else {
                                menu.classList.remove('collapsed');
                                if (chevron) chevron.classList.remove('rotate-180');
                            }
                        });
                        
                        noResultsMessage.classList.add('hidden');
                        return;
                    }
                    
                    // Search through menu items
                    menuLinks.forEach(link => {
                        const text = link.textContent.toLowerCase();
                        const listItem = link.closest('li');
                        
                        if (text.includes(query)) {
                            listItem.style.display = '';
                            hasResults = true;
                            
                            // Expand all parent collapsible menus recursively
                            let currentParent = link.parentElement;
                            while (currentParent && currentParent !== sidebar) {
                                if (currentParent.classList.contains('collapsible-menu')) {
                                    currentParent.classList.remove('collapsed');
                                    
                                    // Update chevron
                                    const chevronId = currentParent.id.replace('Menu', 'Chevron');
                                    const chevron = document.getElementById(chevronId);
                                    if (chevron) {
                                        chevron.classList.remove('rotate-180');
                                    }
                                }
                                currentParent = currentParent.parentElement;
                            }
                            
                            // Show parent section
                            const parentSection = link.closest('nav > div');
                            if (parentSection) {
                                parentSection.style.display = '';
                            }
                        } else {
                            listItem.style.display = 'none';
                        }
                    });
                    
                    // Hide sections with no visible items
                    menuSections.forEach(section => {
                        const visibleLinks = section.querySelectorAll('.nav-link');
                        let hasVisibleItem = false;
                        
                        visibleLinks.forEach(link => {
                            if (link.closest('li').style.display !== 'none') {
                                hasVisibleItem = true;
                            }
                        });
                        
                        section.style.display = hasVisibleItem ? '' : 'none';
                    });
                    
                    // Show/hide no results message
                    if (hasResults) {
                        noResultsMessage.classList.add('hidden');
                    } else {
                        noResultsMessage.classList.remove('hidden');
                    }
                }
                
                // Search input event
                searchInput.addEventListener('input', function(e) {
                    const query = e.target.value;
                    performSearch(query);
                    
                    // Show/hide clear button
                    if (query) {
                        clearButton.classList.remove('hidden');
                    } else {
                        clearButton.classList.add('hidden');
                    }
                });
                
                // Clear button event
                clearButton.addEventListener('click', function() {
                    searchInput.value = '';
                    clearButton.classList.add('hidden');
                    performSearch('');
                    searchInput.focus();
                });
            });

            // Toast Notification System
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border transform transition-all duration-300 translate-y-10 opacity-0 min-w-[300px] max-w-md bg-white`;
                
                let icon = 'mdi:check-circle';
                if (type === 'success') {
                    toast.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
                    icon = 'mdi:check-circle';
                } else if (type === 'error') {
                    toast.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                    icon = 'mdi:alert-circle';
                } else {
                    toast.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-800');
                    icon = 'mdi:information';
                }

                toast.innerHTML = `
                    <span class="iconify h-5 w-5 flex-shrink-0" data-icon="${icon}"></span>
                    <div class="flex-grow text-sm font-medium">${message}</div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="iconify h-4 w-4" data-icon="mdi:close"></span>
                    </button>
                `;

                container.appendChild(toast);

                // Animate in
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-10', 'opacity-0');
                });

                // Auto remove
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.classList.add('opacity-0', 'translate-x-full');
                        setTimeout(() => toast.remove(), 300);
                    }
                }, 5000);
            }
        </script>

        <?php if (isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast(<?= json_encode($_SESSION['message']) ?>);
            });
        </script>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </body>
</html>