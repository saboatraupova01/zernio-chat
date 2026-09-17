@extends('layouts.chat')

@section('title', $conversation->participant_name ?? 'Чат')

@section('header')

    <div style="display: flex; align-items: center; gap: 12px;">

        <a
            href="{{ route('conversations.index') }}"
            style="
            font-size: 20px;
            color: #8b909c;
        "
        >
            ←
        </a>

        <div>

            <div style="font-weight: 700;">
                {{ $conversation->participant_name ?? 'Без имени' }}
            </div>

            <div style="
            font-size: 11px;
            color: #9297a3;
            margin-top: 2px;
        ">
                {{ ucfirst($conversation->channel) }}
            </div>

        </div>

    </div>


@endsection

@section('sidebar')


    <a
        href="{{ route('conversations.index') }}"
        style="
        display: block;
        padding: 10px 5px 16px;
        color: #6c63ff;
        font-size: 13px;
    "
    >
        ← Все чаты
    </a>

    <div class="section-title">
        Current conversation
    </div>

    <div class="conversation-item active">

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
                </div>

            </div>

        </div>

    </div>

@endsection

@section('content')


    <div style="
    height: 100%;
    display: flex;
    flex-direction: column;
">

        {{-- Messages --}}
        <div
            class="messages"
            data-conversation-id="{{ $conversation->id }}"
            style="
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        "
        >

            @forelse($conversation->messages as $message)

                <div
                    class="message-row {{ $message->sender_type === 'operator' ? 'operator-row' : 'customer-row' }}"
                    data-message-id="{{ $message->id }}"
                >

                    <div class="message-content">

                        <div class="message-with-action">

                            <div class="message-bubble {{ $message->sender_type === 'operator' ? 'operator-bubble' : 'customer-bubble' }}">

                                {{-- Quoted message --}}
                                @if($message->replyTo)

                                    <div class="quoted-message">

                                        <div class="quoted-message-label">
                                            Ответ на:
                                        </div>

                                        <div class="quoted-message-text">
                                            {{ $message->replyTo->text }}
                                        </div>

                                    </div>

                                @endif

                                {{-- Message text --}}
                                <div class="message-text">
                                    {{ $message->text }}
                                </div>

                                {{-- Message time --}}
                                <div class="message-time">
                                    {{ $message->sent_at?->format('H:i') }}
                                </div>

                            </div>

                            {{-- Reply button --}}
                            @if($message->sender_type === 'customer')

                                <button
                                    type="button"
                                    class="reply-button"
                                    data-message-id="{{ $message->id }}"
                                    data-message-text="{{ $message->text }}"
                                    title="Ответить"
                                >
                                    Ответить
                                </button>

                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div style="
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #9ca1ac;
            ">
                    Сообщений пока нет.
                </div>

            @endforelse

        </div>

        {{-- Composer --}}
        <div style="
        padding: 16px 20px 20px;
        border-top: 1px solid #e7e9ef;
        background: #fff;
    ">

            {{-- Reply preview --}}
            <div
                id="reply-preview"
                class="reply-preview hidden"
            >

                <div class="reply-preview-content">

                    <div class="reply-preview-title">
                        Ответ на сообщение
                    </div>

                    <div
                        id="reply-preview-text"
                        class="reply-preview-text"
                    ></div>

                </div>

                <button
                    type="button"
                    id="cancel-reply"
                    class="cancel-reply"
                    title="Отменить ответ"
                >
                    ×
                </button>

            </div>

            <form
                method="POST"
                action="{{ route('messages.send', $conversation) }}"
                style="
                display: flex;
                align-items: flex-end;
                gap: 10px;
            "
            >

                @csrf

                <input
                    type="hidden"
                    name="reply_to_message_id"
                    id="reply-to-message-id"
                >

                <textarea
                    name="text"
                    rows="1"
                    required
                    placeholder="Напишите сообщение..."
                    class="message-input"
                ></textarea>

                <button
                    type="submit"
                    class="send-button"
                >
                    ↑
                </button>

            </form>

        </div>

    </div>


@endsection

@section('profile')

    <aside class="profile-panel">

        <div class="profile-avatar">

            @if($conversation->channel === 'instagram')
                ◎
            @elseif($conversation->channel === 'whatsapp')
                ☎
            @else
                👤
            @endif

        </div>

        <div class="profile-name">
            {{ $conversation->participant_name ?? 'Без имени' }}
        </div>

        <div class="profile-username">
            {{ $conversation->participant_phone
                ?? $conversation->participant_id
                ?? '—' }}
        </div>

        <div style="margin-top: 25px;">

            {{-- Channel --}}
            <div class="profile-row">

                <div class="profile-label">
                    Channel
                </div>

                <div class="profile-value">
                    {{ ucfirst($conversation->channel) }}
                </div>

            </div>

            {{-- Conversation status --}}
            <div class="profile-row">

                <div class="profile-label">
                    Статус диалога
                </div>

                <form
                    method="POST"
                    action="{{ route('conversations.status', $conversation) }}"
                >

                    @csrf
                    @method('PATCH')

                    <select
                        name="operator_status"
                        class="status-select"
                        onchange="this.form.submit()"
                    >

                        <option
                            value="new"
                            {{ $conversation->operator_status === 'new' ? 'selected' : '' }}
                        >
                            Новое
                        </option>

                        <option
                            value="in_progress"
                            {{ $conversation->operator_status === 'in_progress' ? 'selected' : '' }}
                        >
                            В работе
                        </option>

                        <option
                            value="waiting"
                            {{ $conversation->operator_status === 'waiting' ? 'selected' : '' }}
                        >
                            Ожидает клиента
                        </option>

                        <option
                            value="closed"
                            {{ $conversation->operator_status === 'closed' ? 'selected' : '' }}
                        >
                            Закрыто
                        </option>

                    </select>

                </form>

            </div>

            {{-- Assigned operator --}}
            <div class="profile-row">

                <div class="profile-label">
                    Оператор
                </div>

                <form
                    method="POST"
                    action="{{ route('conversations.assign', $conversation) }}"
                >

                    @csrf
                    @method('PATCH')

                    <select
                        name="assigned_operator_id"
                        class="status-select"
                        onchange="this.form.submit()"
                    >

                        <option value="">
                            Не назначен
                        </option>

                        @foreach(\App\Models\User::query()->get() as $user)

                            <option
                                value="{{ $user->id }}"
                                {{ $conversation->assigned_operator_id === $user->id ? 'selected' : '' }}
                            >
                                {{ $user->name }}
                            </option>

                        @endforeach

                    </select>

                </form>

            </div>

            {{-- Messages count --}}
            <div class="profile-row">

                <div class="profile-label">
                    Messages
                </div>

                <div class="profile-value">
                    {{ $conversation->messages->count() }}
                </div>

            </div>

        </div>

    </aside>


@endsection

@push('scripts')


    <script>
        const messages = document.querySelector('.messages');

        if (messages) {
            messages.scrollTop = messages.scrollHeight;
        }

        const replyButtons = document.querySelectorAll('.reply-button');

        const replyPreview = document.querySelector('#reply-preview');
        const replyPreviewText = document.querySelector('#reply-preview-text');
        const replyToMessageId = document.querySelector('#reply-to-message-id');
        const cancelReply = document.querySelector('#cancel-reply');
        const messageInput = document.querySelector('.message-input');

        replyButtons.forEach((button) => {

            button.addEventListener('click', () => {

                const messageId = button.dataset.messageId;
                const messageText = button.dataset.messageText;

                const replyPreview = document.querySelector('#reply-preview');
                const replyPreviewText = document.querySelector('#reply-preview-text');
                const replyToMessageId = document.querySelector('#reply-to-message-id');
                const cancelReply = document.querySelector('#cancel-reply');
                const messageInput = document.querySelector('.message-input');
                const messagesContainer = document.querySelector('.messages');

                if (messagesContainer) {
                    messagesContainer.addEventListener('click', (event) => {

                        const button = event.target.closest('.reply-button');

                        if (!button) {
                            return;
                        }

                        const messageId = button.dataset.messageId;
                        const messageText = button.dataset.messageText;

                        replyToMessageId.value = messageId;
                        replyPreviewText.textContent = messageText;

                        replyPreview.classList.remove('hidden');

                        messageInput.focus();
                    });
                }

                if (cancelReply) {
                    cancelReply.addEventListener('click', () => {

                        replyToMessageId.value = '';
                        replyPreviewText.textContent = '';

                        replyPreview.classList.add('hidden');
                    });
                }
    </script>


@endpush

<style>

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    .message-row {
        display: flex;
        width: 100%;
        margin-bottom: 18px;
    }

    .customer-row {
        justify-content: flex-start;
    }

    .operator-row {
        justify-content: flex-end;
    }

    .message-content {
        width: 70%;
        max-width: 70%;
    }

    .customer-row .message-content {
        display: flex;
        justify-content: flex-start;
    }

    .operator-row .message-content {
        display: flex;
        justify-content: flex-end;
    }

    /*
    |--------------------------------------------------------------------------
    | Message with action
    |--------------------------------------------------------------------------
    */

    .message-with-action {
        display: flex;
        align-items: center;
        width: fit-content;
        max-width: 100%;
    }

    /*
    |--------------------------------------------------------------------------
    | Message bubble
    |--------------------------------------------------------------------------
    */

    .message-bubble {
        max-width: 65%;
        padding: 11px 14px;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .customer-bubble {
        background: #ffffff;
        color: #252832;
        border-bottom-left-radius: 4px;
    }

    .operator-bubble {
        background: #6c63ff;
        color: #ffffff;
        border-bottom-right-radius: 4px;
    }

    /*
    |--------------------------------------------------------------------------
    | Message text
    |--------------------------------------------------------------------------
    */

    .message-text {
        font-size: 13px;
        line-height: 1.45;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .message-time {
        margin-top: 5px;
        font-size: 10px;
        opacity: .65;
        text-align: right;
    }

    /*
    |--------------------------------------------------------------------------
    | Reply button
    |--------------------------------------------------------------------------
    */

    .reply-button {
        width: 30px;
        height: 30px;
        flex-shrink: 0;
        margin-left: 8px;

        display: flex;
        align-items: center;
        justify-content: center;

        border: none;
        border-radius: 50%;

        background: #f1f5f9;
        color: #64748b;

        cursor: pointer;

        opacity: 0;
        visibility: hidden;
        pointer-events: none;

        transition:
            opacity .15s ease,
            background .15s ease,
            color .15s ease;
    }

    .message-with-action:hover .reply-button {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .reply-button:hover {
        background: #e2e8f0;
        color: #2563eb;
    }

    .message-with-action:hover .reply-button {
        opacity: 1;
        visibility: visible;
    }

    .reply-button:hover {
        background: #e2e8f0;
        color: #2563eb;
    }

    /*
    |--------------------------------------------------------------------------
    | Quoted message
    |--------------------------------------------------------------------------
    */

    .quoted-message {
        margin-bottom: 8px;
        padding: 7px 10px;

        border-left: 3px solid #94a3b8;
        border-radius: 4px;

        background: rgba(0, 0, 0, 0.04);
    }

    .operator-bubble .quoted-message {
        border-left-color: rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.12);
    }

    .quoted-message-label {
        font-size: 10px;
        margin-bottom: 2px;
        opacity: .7;
    }

    .quoted-message-text {
        font-size: 11px;
        line-height: 1.35;

        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*
    |--------------------------------------------------------------------------
    | Reply preview
    |--------------------------------------------------------------------------
    */

    .reply-preview {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 12px;

        padding: 10px 14px;
        margin-bottom: 8px;

        background: #f8fafc;

        border-left: 3px solid #6c63ff;
        border-radius: 8px;
    }

    .reply-preview.hidden {
        display: none;
    }

    .reply-preview-content {
        min-width: 0;
    }

    .reply-preview-title {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 3px;
    }

    .reply-preview-text {
        font-size: 13px;
        color: #334155;

        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;

        max-width: 500px;
    }

    .cancel-reply {
        flex-shrink: 0;

        width: 28px;
        height: 28px;

        border: none;
        background: transparent;

        font-size: 20px;
        line-height: 1;

        color: #64748b;
        cursor: pointer;
    }

    .cancel-reply:hover {
        color: #ef4444;
    }

    /*
    |--------------------------------------------------------------------------
    | Composer
    |--------------------------------------------------------------------------
    */

    .message-input {
        flex: 1;
        resize: none;

        border: 1px solid #e1e4eb;
        border-radius: 12px;

        padding: 12px 14px;

        font-family: inherit;
        font-size: 13px;

        outline: none;

        min-height: 46px;
    }

    .message-input:focus {
        border-color: #aaa4ff;
    }

    .send-button {
        width: 46px;
        height: 46px;

        border: none;
        border-radius: 12px;

        background: #6c63ff;
        color: white;

        font-size: 18px;

        cursor: pointer;
    }

    .send-button:hover {
        background: #5b52e8;
    }

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    .status-select {
        width: 100%;

        padding: 9px 10px;

        border: 1px solid #e1e4ea;
        border-radius: 9px;

        background: #f9fafc;
        color: #30333a;

        font-family: inherit;
        font-size: 12px;

        outline: none;
        cursor: pointer;
    }

    .status-select:focus {
        border-color: #aaa4ff;
        background: #fff;
    }

</style>
