{{-- Charte CMP : primaire #7b1d3e, bouton de connexion fond #7b1d3e et texte blanc ; textes auth centrés. --}}
<style>
    .fi-simple-page {
        background: linear-gradient(180deg, #fafafa 0%, #f4f4f5 100%);
    }
    .dark .fi-simple-page {
        background: linear-gradient(180deg, #18181b 0%, #0a0a0a 100%);
    }

    html.fi.fi-panel-admin {
        --primary-400: #7b1d3e;
        --primary-500: #7b1d3e;
        --primary-600: #7b1d3e;
        --primary-700: #631832;
    }

    html.fi.fi-panel-admin .fi-auth-layout .fi-auth-content-section {
        text-align: center;
    }

    html.fi.fi-panel-admin .fi-auth-layout .fi-simple-header {
        align-items: center;
    }

    html.fi.fi-panel-admin .fi-auth-layout .fi-simple-header-heading {
        text-align: center;
        width: 100%;
    }

    html.fi.fi-panel-admin .fi-auth-layout .fi-auth-form-container {
        margin-left: auto;
        margin-right: auto;
    }

    html.fi.fi-panel-admin .fi-simple-page form [type='submit'],
    html.fi.fi-panel-admin .fi-simple-page form button[type='submit'],
    html.fi.fi-panel-admin .fi-auth-layout form [type='submit'],
    html.fi.fi-panel-admin .fi-auth-layout form button[type='submit'],
    html.fi.fi-panel-admin .fi-auth-layout .fi-btn,
    html.fi.fi-panel-admin .fi-simple-page .fi-btn {
        background-color: #7b1d3e !important;
        border-color: #7b1d3e !important;
        color: #ffffff !important;
        --tw-ring-color: #7b1d3e !important;
    }

    html.fi.fi-panel-admin .fi-simple-page form [type='submit']:hover,
    html.fi.fi-panel-admin .fi-simple-page form button[type='submit']:hover,
    html.fi.fi-panel-admin .fi-auth-layout form [type='submit']:hover,
    html.fi.fi-panel-admin .fi-auth-layout form button[type='submit']:hover,
    html.fi.fi-panel-admin .fi-auth-layout .fi-btn:hover,
    html.fi.fi-panel-admin .fi-simple-page .fi-btn:hover {
        background-color: #631832 !important;
        border-color: #631832 !important;
        color: #ffffff !important;
    }
</style>

