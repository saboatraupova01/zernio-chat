@extends('layouts.chat')

@section('title', 'Inbox')

@section('header', 'Inbox')

@section('sidebar')

    <input
        type="text"
        class="search"
        placeholder="Поиск разговоров..."
    >

    <div class="filters">
        <a
            href="{{ route('conversations.index') }}"
            class="filter {{ !request('channel') ? 'active' : '' }}"
        >
            Все
        </a>

        <a
            href="{{ route('conversations.index', ['channel' => 'whatsapp']) }}"
            class="filter {{ request('channel') === 'whatsapp' ? 'active' : '' }}"
        >
            WhatsApp
        </a>

        <a
            href="{{ route('conversations.index', ['channel' => 'instagram']) }}"
            class="filter {{ request('channel') === 'instagram' ? 'active' : '' }}"
        >
            Instagram
        </a>
            </div>

    <div class="section-title">
        Chats
    </div>
    <div id="conversation-list">
    @forelse($conversations as $conversation)

            <a
                href="{{ route('conversations.show', $conversation) }}"
                class="conversation-item"
                data-conversation-id="{{ $conversation->id }}"
            >

            <div class="conversation-top">

                <div class="avatar">
                    @if($conversation->channel === 'instagram')
                        ◎
                    @elseif($conversation->channel === 'whatsapp')
                        ☎
                    @else
                        💬
                    @endif
                </div>

                <div class="conversation-main">

                    <div class="conversation-name">
                        {{ $conversation->participant_name ?? 'Без имени' }}
                    </div>

                    <div class="conversation-preview">
                        {{ ucfirst($conversation->channel) }}
                        ·
                        {{ $conversation->status }}
                    </div>
                    <div class="conversation-assignment">

    <span class="operator-status status-{{ $conversation->operator_status }}">
        @switch($conversation->operator_status)
            @case('new')
                Новое
                @break

            @case('in_progress')
                В работе
                @break

            @case('waiting')
                Ожидает клиента
                @break

            @case('closed')
                Закрыто
                @break

            @default
                {{ $conversation->operator_status }}
        @endswitch
    </span>
                        @if($conversation->assignedOperator)
                            <span class="assigned-operator">
            · {{ $conversation->assignedOperator->name }}
        </span>
                        @endif

                    </div>
                </div>

                <div class="conversation-meta">

                    <div class="time">
                        {{ $conversation->last_message_at?->format('H:i') }}
                    </div>

                    <div
                        class="channel-badge
                        channel-{{ $conversation->channel }}"
                    >
                        {{ $conversation->channel }}
                    </div>

                </div>

            </div>

        </a>

    @empty

        <div style="padding: 20px; color: #888;">
            Чатов пока нет.
        </div>

    @endforelse

@endsection

@section('content')

    <div style="
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9aa0ad;
    ">
        <div style="text-align: center;">
            <div style="font-size: 40px; margin-bottom: 12px;">
                💬
            </div>

            <div style="font-size: 16px; font-weight: 600;">
                Выберите разговор
            </div>

            <div style="font-size: 13px; margin-top: 5px;">
                Откройте чат слева, чтобы начать работу
            </div>
        </div>
    </div>

@endsection

@section('profile')

    <aside class="profile-panel">

        <div class="profile-avatar">
            👤
        </div>

        <div class="profile-name">
            Zernio Chat
        </div>

        <div class="profile-username">
            Unified inbox
        </div>

        <div style="margin-top: 25px;">

            <div class="profile-row">
                <div class="profile-label">
                    Channels
                </div>

                <div class="profile-value">
                    WhatsApp · Instagram
                </div>
            </div>

            <div class="profile-row">
                <div class="profile-label">
                    Conversations
                </div>

                <div class="profile-value">
                    {{ $conversations->count() }}
                </div>
            </div>

        </div>

    </aside>

@endsection

<style>
    .conversation-assignment {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 5px;
        font-size: 11px;
    }

    .operator-status {
        font-weight: 600;
    }

    .status-new {
        color: #6c63ff;
    }

    .status-in_progress {
        color: #d88900;
    }

    .status-waiting {
        color: #6f7785;
    }

    .status-closed {
        color: #53a36b;
    }

    .assigned-operator {
        color: #8f95a1;
    }
</style>
