import echo from './echo';

const messagesContainer = document.querySelector('.messages');

if (messagesContainer) {
    const conversationId = messagesContainer.dataset.conversationId;

    console.log('SUBSCRIBING TO:', `conversation.${conversationId}`);

    echo
        .channel(`conversation.${conversationId}`)
        .listen('.message.received', (event) => {
            console.log('NEW MESSAGE:', event);

            addMessage(event.message);
        });
}

function addMessage(message) {
    const container = document.querySelector('.messages');

    if (!container) {
        return;
    }

    if (
        message.id &&
        container.querySelector(
            `[data-message-id="${message.id}"]`
        )
    ) {
        return;
    }

    const row = document.createElement('div');

    row.className = 'message-row';

    row.style.display = 'flex';
    row.style.width = '100%';
    row.style.marginBottom = '18px';

    if (message.sender_type === 'operator') {
        row.style.justifyContent = 'flex-end';
    } else {
        row.style.justifyContent = 'flex-start';
    }

    const content = document.createElement('div');

    content.className = 'message-content';

    const actionWrapper = document.createElement('div');

    actionWrapper.className = 'message-with-action';

    const bubble = document.createElement('div');

    bubble.className =
        message.sender_type === 'operator'
            ? 'message-bubble operator-bubble'
            : 'message-bubble customer-bubble';

    bubble.dataset.messageId = message.id;

    const text = document.createElement('div');

    text.className = 'message-text';
    text.textContent = message.text ?? '';

    const time = document.createElement('div');

    time.className = 'message-time';

    if (message.sent_at) {
        time.textContent = new Date(message.sent_at)
            .toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
            });
    }

    bubble.appendChild(text);
    bubble.appendChild(time);

    actionWrapper.appendChild(bubble);

    /*
     * Reply button only for customer messages
     */
    if (message.sender_type === 'customer') {
        const replyButton = document.createElement('button');

        replyButton.type = 'button';
        replyButton.className = 'reply-button';

        replyButton.dataset.messageId = message.id;
        replyButton.dataset.messageText = message.text ?? '';

        replyButton.title = 'Ответить';
        replyButton.textContent = 'Ответить';

        replyButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const replyPreview = document.querySelector('#reply-preview');
            const replyPreviewText = document.querySelector('#reply-preview-text');
            const replyToMessageId = document.querySelector('#reply-to-message-id');
            const messageInput = document.querySelector('.message-input');

            if (!replyPreview || !replyPreviewText || !replyToMessageId) {
                console.error('Reply elements not found');

                return;
            }

            replyToMessageId.value = message.id;
            replyPreviewText.textContent = message.text ?? '';

            replyPreview.classList.remove('hidden');

            if (messageInput) {
                messageInput.focus();
            }
        });

        actionWrapper.appendChild(replyButton);
    }

    content.appendChild(actionWrapper);

    row.appendChild(content);

    container.appendChild(row);

    container.scrollTop = container.scrollHeight;
}
const conversationList = document.querySelector('#conversation-list');

if (conversationList) {
    echo
        .channel('conversations')
        .listen('.conversation.updated', (event) => {
            updateConversationItem(event.conversation);
        });
}
function updateConversationItem(conversation) {
    const item = document.querySelector(
        `[data-conversation-id="${conversation.id}"]`
    );

    if (!item) {
        return;
    }

    const assignment = item.querySelector(
        '.conversation-assignment'
    );

    let statusText = 'Новое';

    if (conversation.operator_status === 'in_progress') {
        statusText = 'В работе';
    } else if (conversation.operator_status === 'waiting') {
        statusText = 'Ожидает клиента';
    } else if (conversation.operator_status === 'closed') {
        statusText = 'Закрыто';
    }

    if (assignment) {
        assignment.innerHTML = conversation.assigned_operator_name
            ? `<span class="operator-status status-${conversation.operator_status}">
                    ${statusText}
               </span>
               <span class="assigned-operator">
                    · ${conversation.assigned_operator_name}
               </span>`
            : `<span class="operator-status status-${conversation.operator_status}">
                    ${statusText}
               </span>`;
    }
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

        replyToMessageId.value = messageId;
        replyPreviewText.textContent = messageText;

        replyPreview.classList.remove('hidden');

        messageInput.focus();
    });
});

if (cancelReply) {
    cancelReply.addEventListener('click', () => {
        replyToMessageId.value = '';
        replyPreviewText.textContent = '';

        replyPreview.classList.add('hidden');
    });
}




