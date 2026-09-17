<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Zernio Chat')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system,
            BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f7fb;
            color: #20232a;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* SIDEBAR */

        .sidebar {
            width: 300px;
            background: #ffffff;
            border-right: 1px solid #e7e9ef;
            display: flex;
            flex-direction: column;
        }

        .brand {
            height: 68px;
            padding: 0 22px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e7e9ef;
            font-size: 20px;
            font-weight: 700;
        }

        .brand span {
            color: #6c63ff;
        }

        .sidebar-content {
            padding: 18px;
            overflow-y: auto;
        }

        .search {
            width: 100%;
            border: 1px solid #e2e5ec;
            border-radius: 10px;
            padding: 11px 13px;
            font-size: 14px;
            outline: none;
            background: #f8f9fc;
        }

        .search:focus {
            border-color: #aaa3ff;
            background: #fff;
        }

        .section-title {
            margin: 24px 4px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #8b909c;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .filters {
            display: flex;
            gap: 8px;
        }

        .filter {
            padding: 7px 11px;
            border-radius: 8px;
            font-size: 13px;
            background: #f1f2f7;
            color: #626775;
        }

        .filter.active {
            background: #eceaff;
            color: #5950db;
        }

        /* CHAT LIST */

        .conversation-item {
            display: block;
            padding: 13px;
            margin-bottom: 5px;
            border-radius: 12px;
            transition: .15s;
        }

        .conversation-item:hover {
            background: #f7f7fb;
        }

        .conversation-item.active {
            background: #efeeff;
        }

        .conversation-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e9e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            font-size: 18px;
        }

        .conversation-main {
            min-width: 0;
            flex: 1;
        }

        .conversation-name {
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-preview {
            margin-top: 4px;
            font-size: 12px;
            color: #8a8f9b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .time {
            font-size: 11px;
            color: #9ca1ac;
        }

        .channel-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .channel-instagram {
            background: #fce9f4;
            color: #cf3d83;
        }

        .channel-whatsapp {
            background: #e5f7eb;
            color: #22984b;
        }

        /* CENTER */

        .main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 68px;
            background: #fff;
            border-bottom: 1px solid #e7e9ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }

        .topbar-title {
            font-weight: 700;
            font-size: 16px;
        }

        .operator {
            font-size: 13px;
            color: #707582;
        }

        .content {
            flex: 1;
            min-height: 0;
        }

        /* RIGHT PANEL */

        .profile-panel {
            width: 280px;
            background: #fff;
            border-left: 1px solid #e7e9ef;
            padding: 25px;
            overflow-y: auto;
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: #ebe9ff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 28px;
        }

        .profile-name {
            text-align: center;
            font-weight: 700;
            font-size: 16px;
        }

        .profile-username {
            text-align: center;
            color: #9297a3;
            font-size: 13px;
            margin-top: 4px;
        }

        .profile-row {
            padding: 14px 0;
            border-bottom: 1px solid #eef0f4;
        }

        .profile-label {
            font-size: 11px;
            color: #9ca1ac;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .profile-value {
            font-size: 13px;
        }

        @media (max-width: 1100px) {
            .profile-panel {
                display: none;
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 250px;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                width: 100%;
            }

            .main {
                display: none;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

<div class="app">

    <aside class="sidebar">

        <div class="brand">
            Zernio<span>Chat</span>
        </div>

        <div class="sidebar-content">
            @yield('sidebar')
        </div>

    </aside>

    <main class="main">

        <header class="topbar">
            <div class="topbar-title">
                @yield('header')
            </div>

            <div class="operator">
                Operator
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>

    </main>

    @yield('profile')

</div>

@stack('scripts')

</body>
</html>
