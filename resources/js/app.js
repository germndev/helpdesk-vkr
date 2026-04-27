import './bootstrap';

const overlay = document.querySelector('[data-logout-overlay]');
const openButton = document.querySelector('[data-logout-open]');
const closeButton = document.querySelector('[data-logout-close]');

if (overlay && openButton && closeButton) {
    const openDialog = () => {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    const closeDialog = () => {
        overlay.hidden = true;
        document.body.style.overflow = '';
    };

    openButton.addEventListener('click', openDialog);
    closeButton.addEventListener('click', closeDialog);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeDialog();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !overlay.hidden) {
            closeDialog();
        }
    });
}

const successOverlay = document.querySelector('[data-success-overlay]');
const successCloseButton = document.querySelector('[data-success-close]');

if (successOverlay && successCloseButton) {
    successCloseButton.addEventListener('click', () => {
        successOverlay.remove();
    });
}

const inlineEditForms = document.querySelectorAll('[data-inline-edit-form]');

inlineEditForms.forEach((form) => {
    const toggle = form.querySelector('[data-inline-edit-toggle]');
    const editInputs = form.querySelectorAll('[data-inline-edit-input]');
    const actionButtons = form.querySelectorAll('[data-inline-edit-action]');
    const badge = form.querySelector('[data-inline-edit-badge]');
    const startEdit = form.dataset.inlineStartEdit === '1' || window.__forceInlineEdit === true;

    const setMode = (editable) => {
        form.classList.toggle('is-editing', editable);

        editInputs.forEach((input) => {
            input.disabled = !editable;
        });

        actionButtons.forEach((button) => {
            button.disabled = !editable;
        });

        if (badge) {
            badge.hidden = !editable;
        }
    };

    setMode(startEdit);

    if (toggle) {
        toggle.addEventListener('click', () => setMode(true));
    }
});

const ticketFilterForms = document.querySelectorAll('[data-ticket-filter-form]');

ticketFilterForms.forEach((form) => {
    const resultsContainer = document.querySelector('[data-ticket-results]');
    if (!resultsContainer) {
        return;
    }

    const buildQueryString = () => {
        const params = new URLSearchParams();
        const formData = new FormData(form);

        formData.forEach((value, key) => {
            if (String(value).trim() !== '') {
                params.append(key, String(value));
            }
        });

        return params.toString();
    };

    const fetchResults = async (url) => {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        if (payload.html) {
            resultsContainer.innerHTML = payload.html;
            bindPagination();
        }

        window.history.replaceState({}, '', url);
    };

    const requestWithCurrentFilters = () => {
        const query = buildQueryString();
        const url = `${window.location.pathname}${query ? `?${query}` : ''}`;
        fetchResults(url);
    };

    const bindPagination = () => {
        resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const href = link.getAttribute('href');
                if (href) {
                    fetchResults(href);
                }
            });
        });
    };

    document.querySelectorAll('[data-multi-filter]').forEach((filter) => {
        const toggle = filter.querySelector('[data-multi-filter-toggle]');
        const menu = filter.querySelector('[data-multi-filter-menu]');
        const checkboxes = filter.querySelectorAll('input[type="checkbox"]');

        const updateLabel = () => {
            const checked = Array.from(checkboxes).filter((item) => item.checked);
            const baseLabel = toggle.dataset.baseLabel ?? toggle.textContent.trim();
            if (!toggle.dataset.baseLabel) {
                toggle.dataset.baseLabel = baseLabel;
            }
            toggle.textContent = checked.length > 0 ? `${baseLabel}: ${checked.length}` : baseLabel;
        };

        toggle.addEventListener('click', () => {
            const isOpen = menu.classList.contains('show');
            document.querySelectorAll('[data-multi-filter-menu].show').forEach((opened) => opened.classList.remove('show'));
            if (!isOpen) {
                menu.classList.add('show');
            }
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                updateLabel();
                requestWithCurrentFilters();
            });
        });

        updateLabel();
    });

    form.querySelectorAll('[data-sort-select]').forEach((sortSelect) => {
        sortSelect.addEventListener('change', requestWithCurrentFilters);
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (!event.target.closest('[data-multi-filter]')) {
            document.querySelectorAll('[data-multi-filter-menu].show').forEach((menu) => menu.classList.remove('show'));
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        requestWithCurrentFilters();
    });

    bindPagination();
});

const ruleForms = document.querySelectorAll('[data-rule-form]');

ruleForms.forEach((form) => {
    const triggerList = form.querySelector('[data-trigger-list]');
    const template = form.querySelector('[data-trigger-template]');
    const addButton = form.querySelector('[data-add-trigger]');

    if (!triggerList || !template || !addButton) {
        return;
    }

    const reindexRows = () => {
        triggerList.querySelectorAll('[data-trigger-row]').forEach((row, index) => {
            const phraseInput = row.querySelector('[data-trigger-input="phrase"], input[type="text"]');
            const weightInput = row.querySelector('[data-trigger-input="weight"], input[type="number"]');

            if (phraseInput) {
                phraseInput.name = `triggers[${index}][phrase]`;
            }

            if (weightInput) {
                weightInput.name = `triggers[${index}][weight]`;
            }
        });
    };

    const removeRow = (row) => {
        const rows = triggerList.querySelectorAll('[data-trigger-row]');
        if (rows.length === 1) {
            const phraseInput = row.querySelector('[data-trigger-input="phrase"], input[type="text"]');
            const weightInput = row.querySelector('[data-trigger-input="weight"], input[type="number"]');

            if (phraseInput) {
                phraseInput.value = '';
            }

            if (weightInput) {
                weightInput.value = '1';
            }

            return;
        }

        row.remove();
        reindexRows();
    };

    addButton.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        triggerList.appendChild(fragment);
        reindexRows();

        const lastRow = triggerList.querySelector('[data-trigger-row]:last-child');
        const lastPhraseInput = lastRow?.querySelector('[data-trigger-input="phrase"], input[type="text"]');
        lastPhraseInput?.focus();
    });

    triggerList.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const button = target.closest('[data-remove-trigger]');
        if (!button) {
            return;
        }

        const row = button.closest('[data-trigger-row]');
        if (row) {
            removeRow(row);
        }
    });

    reindexRows();
});

const chatRoots = document.querySelectorAll('[data-chat-root]');

chatRoots.forEach((root) => {
    const fetchUrl = root.getAttribute('data-chat-fetch-url');
    const ticketId = root.getAttribute('data-chat-ticket-id');
    const messagesBox = root.querySelector('[data-chat-messages]');
    const form = root.querySelector('[data-chat-form]');
    const textarea = form?.querySelector('textarea[name="message"]');
    const filesInput = form?.querySelector('[data-chat-files]');
    const filesCountInput = form?.querySelector('[data-chat-files-count]');
    let sending = false;

    if (!fetchUrl || !messagesBox || !form || !textarea) {
        return;
    }

    const scrollToBottom = () => {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    };

    const formatNow = () => {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, '0');

        return `${pad(now.getDate())}.${pad(now.getMonth() + 1)}.${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
    };

    const createPendingMessage = (messageText, selectedFiles) => {
        const pendingNode = document.createElement('div');
        pendingNode.className = 'ticket-chat-message is-own';

        const meta = document.createElement('div');
        meta.className = 'ticket-chat-meta';
        meta.innerHTML = `<span class="ticket-chat-author">Вы</span><span class="ticket-chat-time">${formatNow()}</span>`;
        pendingNode.appendChild(meta);

        if (messageText !== '') {
            const textNode = document.createElement('div');
            textNode.className = 'ticket-chat-text';
            textNode.textContent = messageText;
            pendingNode.appendChild(textNode);
        }

        const revokePreviewUrls = [];
        if (selectedFiles.length > 0) {
            const filesWrap = document.createElement('div');
            filesWrap.className = 'ticket-chat-files';

            selectedFiles.forEach((file) => {
                const item = document.createElement('div');
                item.className = 'ticket-chat-file';

                if (file.type && file.type.startsWith('image/')) {
                    item.classList.add('is-image');
                    const preview = document.createElement('img');
                    preview.className = 'ticket-chat-file-preview';
                    preview.alt = file.name;

                    const objectUrl = URL.createObjectURL(file);
                    preview.src = objectUrl;
                    revokePreviewUrls.push(objectUrl);
                    item.appendChild(preview);
                }

                const name = document.createElement('span');
                name.textContent = file.name;
                item.appendChild(name);
                filesWrap.appendChild(item);
            });

            pendingNode.appendChild(filesWrap);
        }

        return {
            node: pendingNode,
            cleanup: () => {
                revokePreviewUrls.forEach((url) => URL.revokeObjectURL(url));
            },
        };
    };

    const syncSelectedFilesCount = () => {
        if (!filesCountInput) {
            return 0;
        }

        const count = filesInput?.files ? filesInput.files.length : 0;
        filesCountInput.value = String(count);

        return count;
    };

    const refreshMessages = async () => {
        const response = await fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        if (payload.html) {
            messagesBox.innerHTML = payload.html;
            scrollToBottom();
        }
    };

    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    filesInput?.addEventListener('change', () => {
        syncSelectedFilesCount();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (sending) {
            return;
        }
        syncSelectedFilesCount();
        const selectedFiles = filesInput?.files ? Array.from(filesInput.files) : [];
        const messageText = (textarea.value ?? '').trim();

        if (messageText === '' && selectedFiles.length === 0) {
            alert('Введите сообщение или прикрепите файл.');
            return;
        }

        const pendingMessage = createPendingMessage(messageText, selectedFiles);
        messagesBox.appendChild(pendingMessage.node);
        scrollToBottom();

        sending = true;

        const body = new FormData(form);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body,
            });

            if (!response.ok) {
                try {
                    const errorPayload = await response.json();
                    const firstError = Object.values(errorPayload?.errors ?? {})[0];
                    if (Array.isArray(firstError) && firstError[0]) {
                        alert(firstError[0]);
                    }
                } catch {
                }
                pendingMessage.cleanup();
                pendingMessage.node.remove();
                return;
            }

            const payload = await response.json();
            if (payload.html) {
                messagesBox.innerHTML = payload.html;
                scrollToBottom();
            }

            textarea.value = '';
            if (filesInput) {
                filesInput.value = '';
            }
            syncSelectedFilesCount();
            pendingMessage.cleanup();
        } catch {
            pendingMessage.cleanup();
            pendingMessage.node.remove();
            alert('Не удалось отправить сообщение. Проверьте соединение и попробуйте снова.');
        } finally {
            sending = false;
        }
    });

    refreshMessages();
    if (typeof window.Echo !== 'undefined' && ticketId) {
        window.Echo.private(`ticket.${ticketId}`)
            .listen('.ticket.message.created', () => {
                refreshMessages();
            });
    }

    // Резервное обновление чата, если WebSocket временно недоступен.
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshMessages();
        }
    }, 2000);
});

const sidebar = document.querySelector('.helpdesk-sidebar');
const sidebarResizer = document.querySelector('[data-sidebar-resizer]');

if (sidebar && sidebarResizer) {
    const storageKey = 'helpdesk_sidebar_width';
    const minWidth = 210;
    const maxWidth = 420;
    let isResizing = false;

    const clampWidth = (value) => Math.max(minWidth, Math.min(maxWidth, value));

    const applyWidth = (width) => {
        sidebar.style.width = `${clampWidth(width)}px`;
    };

    const storedWidth = Number.parseInt(window.localStorage.getItem(storageKey) ?? '', 10);
    if (Number.isFinite(storedWidth)) {
        applyWidth(storedWidth);
    }

    sidebarResizer.addEventListener('mousedown', (event) => {
        if (window.innerWidth < 768) {
            return;
        }

        event.preventDefault();
        isResizing = true;
        document.body.classList.add('sidebar-resizing');
    });

    document.addEventListener('mousemove', (event) => {
        if (!isResizing) {
            return;
        }

        const shell = document.querySelector('.helpdesk-shell');
        if (!shell) {
            return;
        }

        const shellLeft = shell.getBoundingClientRect().left;
        const nextWidth = event.clientX - shellLeft;
        applyWidth(nextWidth);
    });

    document.addEventListener('mouseup', () => {
        if (!isResizing) {
            return;
        }

        isResizing = false;
        document.body.classList.remove('sidebar-resizing');

        const currentWidth = Math.round(sidebar.getBoundingClientRect().width);
        window.localStorage.setItem(storageKey, String(clampWidth(currentWidth)));
    });
}
