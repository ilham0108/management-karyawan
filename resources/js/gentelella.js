// Gentelella v4 — Vite entry.
//
// The npm package's main entry mounts the shell, wires the topbar, theme
// toggle, command palette and delegated interactions, and imports its own SCSS.
// Page modules (charts, tables, calendar, kanban, …) lazy-load themselves when
// their host element is present, so nothing unused ships.
import 'gentelella';
import { closeModal, showModal } from 'gentelella/v4/modal';
import { openMenu } from 'gentelella/v4/menus';
import { signOut } from 'gentelella/v4/shell-config';
import { showToast } from 'gentelella/v4/toast';

// Your overrides, loaded after the design system so they win.
import '../css/gentelella.scss';

function showAccountSignOutConfirmation() {
    showModal({
        title: 'Sign out?',
        size: 'sm',
        body: '<p style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin:0">You\'ll need to sign back in to access your dashboard. Any unsaved changes will be lost.</p>',
        actions: [
            { label: 'Cancel', variant: 'ghost' },
            {
                label: 'Sign out',
                variant: 'primary',
                action: () => {
                    showToast('Signed out', { variant: 'success' });
                    setTimeout(() => signOut('login.html'), 600);
                },
            },
        ],
    });
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const accountMenuTrigger = event.target.closest('.tb-avatar, .sidebar-user .more-btn');

    if (!(accountMenuTrigger instanceof HTMLElement)) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    openMenu(accountMenuTrigger, [
        { label: 'Sign out', action: showAccountSignOutConfirmation },
    ]);
}, true);

const addPegawaiButton = document.querySelector('[data-open-pegawai-modal]');
const pegawaiFormTemplate = document.querySelector('#pegawai-modal-template');

async function reloadPegawaiTable() {
    const table = document.querySelector('table[data-ajax][data-export="pegawai"]');

    if (!table) {
        return;
    }

    const { default: DataTable } = await import('datatables.net');

    if (DataTable.isDataTable(table)) {
        new DataTable(table, { retrieve: true }).ajax.reload(null, false);
    }
}

function clearFormErrors(form) {
    form.querySelectorAll('[data-ajax-error]').forEach((error) => error.remove());
}

function copyTextToClipboard(text) {
    const textArea = document.createElement('textarea');

    textArea.value = text;
    textArea.setAttribute('readonly', '');
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.append(textArea);
    textArea.select();
    textArea.setSelectionRange(0, textArea.value.length);

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    } finally {
        textArea.remove();
    }

    return copied;
}

function showFormErrors(form, errors) {
    Object.entries(errors).forEach(([name, messages]) => {
        const input = form.elements.namedItem(name);
        const message = Array.isArray(messages) ? messages[0] : messages;

        if (!(input instanceof HTMLElement) || typeof message !== 'string') {
            return;
        }

        const error = document.createElement('div');
        error.className = 'form-error';
        error.setAttribute('role', 'alert');
        error.dataset.ajaxError = '';
        error.textContent = message;
        input.insertAdjacentElement('afterend', error);
    });
}

document.addEventListener('change', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || !input.matches('[data-profile-photo]')) {
        return;
    }

    const fileName = input.closest('.file-input')?.querySelector('[data-profile-photo-name]');

    if (fileName) {
        fileName.textContent = input.files?.[0]?.name || 'Belum ada file dipilih';
    }
});

if (addPegawaiButton && pegawaiFormTemplate) {
    const openPegawaiModal = (editButton = null) => {
        const form = pegawaiFormTemplate.content.querySelector('form').cloneNode(true);
        const isEditing = editButton instanceof HTMLButtonElement;

        form.dataset.submitLabel = isEditing ? 'Simpan Perubahan' : 'Simpan Pegawai';

        if (isEditing) {
            form.action = editButton.dataset.updateUrl;
            const methodInput = form.querySelector('[data-pegawai-method]');
            methodInput.value = 'PUT';
            methodInput.disabled = false;
            form.elements.namedItem('nama').value = editButton.dataset.pegawaiNama;
            form.elements.namedItem('npp').value = editButton.dataset.pegawaiNpp;
            form.elements.namedItem('jabatan_id').value = editButton.dataset.pegawaiJabatanId;
            form.elements.namedItem('status').value = editButton.dataset.pegawaiStatus;
            form.querySelector('[data-profile-photo-name]').textContent =
                'Pilih foto baru jika ingin mengganti foto saat ini';
            form.querySelector('[data-profile-photo-help]').textContent =
                'Kosongkan jika foto saat ini tidak ingin diganti. Format JPG, PNG, atau WebP; maksimal 5 MB.';
        }

        showModal({
            title: isEditing ? 'Edit Pegawai' : 'Tambah Pegawai',
            body: form,
            size: 'md',
            actions: [
                { label: 'Batal', variant: 'outline' },
                {
                    label: isEditing ? 'Simpan Perubahan' : 'Simpan Pegawai',
                    variant: 'primary',
                    action: ({ body }) => {
                        body.querySelector('form').requestSubmit();

                        return false;
                    },
                },
            ],
        });
    };

    addPegawaiButton.addEventListener('click', () => openPegawaiModal());
    document.addEventListener('click', (event) => {
        const editButton = event.target instanceof Element
            ? event.target.closest('[data-edit-pegawai]')
            : null;

        if (editButton instanceof HTMLButtonElement) {
            openPegawaiModal(editButton);
        }
    });

    if (addPegawaiButton.hasAttribute('data-open-on-load')) {
        openPegawaiModal();
    }
}

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-pegawai-ajax-form]')) {
        return;
    }

    event.preventDefault();

    if (form.dataset.submitting === 'true') {
        return;
    }

    form.dataset.submitting = 'true';
    clearFormErrors(form);
    const submitButton = form.closest('.modal-dialog')?.querySelector('.modal-footer .btn-primary');

    if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan...';
    }

    try {
        const response = await fetch(form.action, {
            method: form.method,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
            credentials: 'same-origin',
        });
        const result = await response.json();

        if (response.status === 422 && result.errors) {
            showFormErrors(form, result.errors);
            showToast('Periksa kembali data pegawai.', { variant: 'error' });
            return;
        }

        if (!response.ok) {
            throw new Error(result.message || 'Data pegawai gagal disimpan.');
        }

        closeModal();
        await reloadPegawaiTable();
        showToast(result.message, { variant: 'success' });
    } catch (error) {
        showToast(
            error instanceof Error ? error.message : 'Terjadi kesalahan saat menyimpan pegawai.',
            { variant: 'error' },
        );
    } finally {
        delete form.dataset.submitting;

        if (submitButton instanceof HTMLButtonElement && submitButton.isConnected) {
            submitButton.disabled = false;
            submitButton.textContent = form.dataset.submitLabel || 'Simpan Pegawai';
        }
    }
});

document.addEventListener('click', (event) => {
    const deleteButton = event.target instanceof Element
        ? event.target.closest('[data-delete-pegawai]')
        : null;

    if (!(deleteButton instanceof HTMLButtonElement)) {
        return;
    }

    const message = document.createElement('p');
    message.textContent = `Yakin ingin menghapus data pegawai "${deleteButton.dataset.pegawaiName}"?`;

    showModal({
        title: 'Konfirmasi Hapus Pegawai',
        body: message,
        size: 'sm',
        actions: [
            { label: 'Batal', variant: 'outline' },
            {
                label: 'Hapus Pegawai',
                variant: 'danger',
                action: ({ dialog }) => {
                    const confirmButton = dialog.querySelector('.modal-footer .btn-danger');
                    const table = deleteButton.closest('table');
                    confirmButton.disabled = true;
                    confirmButton.textContent = 'Menghapus...';

                    fetch(deleteButton.dataset.deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': table.dataset.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    })
                        .then(async (response) => {
                            const result = await response.json();

                            if (!response.ok) {
                                throw new Error(result.message || 'Data pegawai gagal dihapus.');
                            }

                            closeModal();
                            await reloadPegawaiTable();
                            showToast(result.message, { variant: 'success' });
                        })
                        .catch((error) => {
                            showToast(
                                error instanceof Error ? error.message : 'Terjadi kesalahan saat menghapus pegawai.',
                                { variant: 'error' },
                            );
                        })
                        .finally(() => {
                            if (confirmButton.isConnected) {
                                confirmButton.disabled = false;
                                confirmButton.textContent = 'Hapus Pegawai';
                            }
                        });

                    return false;
                },
            },
        ],
    });
});

const addJabatanButton = document.querySelector('[data-open-jabatan-modal]');
const jabatanFormTemplate = document.querySelector('#jabatan-modal-template');

if (addJabatanButton && jabatanFormTemplate) {
    const templateForm = jabatanFormTemplate.content.querySelector('[data-jabatan-form]');

    const openJabatanModal = (editButton = null, preserveInput = false) => {
        const form = jabatanFormTemplate.content.querySelector('form').cloneNode(true);
        const methodInput = form.querySelector('[data-jabatan-method]');
        const editingIdInput = form.querySelector('[data-jabatan-editing-id]');
        const nameInput = form.elements.namedItem('nama');
        const isEditing = editButton instanceof HTMLButtonElement;

        if (isEditing) {
            form.action = editButton.dataset.updateUrl;
            methodInput.value = 'PUT';
            methodInput.disabled = false;
            editingIdInput.value = editButton.dataset.jabatanId;

            if (!preserveInput) {
                nameInput.value = editButton.dataset.jabatanName;
            }
        }

        showModal({
            title: isEditing ? 'Edit Jabatan' : 'Tambah Jabatan',
            body: form,
            size: 'sm',
            actions: [
                { label: 'Batal', variant: 'outline' },
                {
                    label: isEditing ? 'Simpan Perubahan' : 'Simpan Jabatan',
                    variant: 'primary',
                    action: ({ body }) => {
                        body.querySelector('form').requestSubmit();

                        return false;
                    },
                },
            ],
        });
    };

    addJabatanButton.addEventListener('click', () => openJabatanModal());

    document.querySelectorAll('[data-edit-jabatan]').forEach((editButton) => {
        editButton.addEventListener('click', () => openJabatanModal(editButton));
    });

    if (addJabatanButton.hasAttribute('data-open-on-load')) {
        const editingId = templateForm.querySelector('[data-jabatan-editing-id]').value;
        const editButton = [...document.querySelectorAll('[data-edit-jabatan]')]
            .find((button) => button.dataset.jabatanId === editingId);

        openJabatanModal(editButton || null, Boolean(editButton));
    }
}

const addUserButton = document.querySelector('[data-open-user-modal]');
const userFormTemplate = document.querySelector('#user-modal-template');

if (addUserButton && userFormTemplate) {
    const templateForm = userFormTemplate.content.querySelector('[data-user-form]');

    const openUserModal = (editButton = null, preserveInput = false) => {
        const form = templateForm.cloneNode(true);
        const methodInput = form.querySelector('[data-user-method]');
        const editingIdInput = form.querySelector('[data-user-editing-id]');
        const isEditing = editButton instanceof HTMLButtonElement;

        if (isEditing) {
            form.action = editButton.dataset.updateUrl;
            methodInput.value = 'PUT';
            methodInput.disabled = false;
            editingIdInput.value = editButton.dataset.userId;
            form.elements.namedItem('password').required = false;
            form.elements.namedItem('password_confirmation').required = false;
            form.querySelector('[data-user-password-help]').textContent =
                'Kosongkan jika password tidak ingin diubah.';

            if (!preserveInput) {
                form.elements.namedItem('name').value = editButton.dataset.userName;
                form.elements.namedItem('email').value = editButton.dataset.userEmail;
                form.elements.namedItem('role').value = editButton.dataset.userRole;
            }
        }

        showModal({
            title: isEditing ? 'Edit User' : 'Tambah User',
            body: form,
            size: 'sm',
            actions: [
                { label: 'Batal', variant: 'outline' },
                {
                    label: isEditing ? 'Simpan Perubahan' : 'Simpan User',
                    variant: 'primary',
                    action: ({ body }) => {
                        body.querySelector('form').requestSubmit();

                        return false;
                    },
                },
            ],
        });
    };

    addUserButton.addEventListener('click', () => openUserModal());

    document.querySelectorAll('[data-edit-user]').forEach((editButton) => {
        editButton.addEventListener('click', () => openUserModal(editButton));
    });

    if (addUserButton.hasAttribute('data-open-on-load')) {
        const editingId = templateForm.querySelector('[data-user-editing-id]').value;
        const editButton = [...document.querySelectorAll('[data-edit-user]')]
            .find((button) => button.dataset.userId === editingId);

        openUserModal(editButton || null, Boolean(editButton));
    }
}

document.addEventListener('click', (event) => {
    const deleteButton = event.target instanceof Element
        ? event.target.closest('[data-delete-jabatan]')
        : null;

    if (!deleteButton) {
        return;
    }

    const deleteForm = deleteButton.closest('form');
    const message = document.createElement('p');

    message.textContent = `Yakin ingin menghapus jabatan "${deleteButton.dataset.jabatanName}"?`;

    showModal({
        title: 'Konfirmasi Hapus Jabatan',
        body: message,
        size: 'sm',
        actions: [
            { label: 'Batal', variant: 'outline' },
            {
                label: 'Hapus Jabatan',
                variant: 'danger',
                action: () => {
                    deleteForm.requestSubmit();

                    return false;
                },
            },
        ],
    });
});

document.addEventListener('click', (event) => {
    const deleteButton = event.target instanceof Element
        ? event.target.closest('[data-delete-user]')
        : null;

    if (!deleteButton) {
        return;
    }

    const deleteForm = deleteButton.closest('form');
    const message = document.createElement('p');

    message.textContent = `Yakin ingin menghapus user "${deleteButton.dataset.userName}"?`;

    showModal({
        title: 'Konfirmasi Hapus User',
        body: message,
        size: 'sm',
        actions: [
            { label: 'Batal', variant: 'outline' },
            {
                label: 'Hapus User',
                variant: 'danger',
                action: () => {
                    deleteForm.requestSubmit();

                    return false;
                },
            },
        ],
    });
});

document.addEventListener('click', (event) => {
    const photoButton = event.target instanceof Element
        ? event.target.closest('[data-pegawai-profile-photo]')
        : null;

    if (!photoButton) {
        return;
    }

    const body = document.createElement('div');
    const loadingOverlay = document.createElement('div');
    const spinner = document.createElement('span');
    const loadingMessage = document.createElement('span');
    const image = document.createElement('img');
    const employeeName = photoButton.dataset.pegawaiName;

    body.className = 'pegawai-photo-container';
    body.setAttribute('aria-busy', 'true');
    loadingOverlay.className = 'pegawai-photo-loading';
    loadingOverlay.setAttribute('role', 'status');
    loadingOverlay.setAttribute('aria-live', 'polite');
    spinner.className = 'pegawai-photo-spinner';
    spinner.setAttribute('aria-hidden', 'true');
    loadingMessage.textContent = 'Memuat foto...';
    loadingOverlay.append(spinner, loadingMessage);
    image.alt = `Foto profil ${employeeName}`;
    image.className = 'pegawai-profile-photo-preview';
    image.addEventListener('load', () => {
        loadingOverlay.hidden = true;
        body.setAttribute('aria-busy', 'false');
    }, { once: true });
    image.addEventListener('error', () => {
        spinner.hidden = true;
        loadingMessage.textContent = 'Foto gagal dimuat.';
        loadingOverlay.classList.add('is-error');
        body.setAttribute('aria-busy', 'false');
        showToast('Foto profil pegawai gagal dimuat.', { variant: 'error' });
    }, { once: true });
    body.append(loadingOverlay, image);

    showModal({
        title: `Foto Profil: ${employeeName}`,
        body,
        size: 'md',
        actions: [
            { label: 'Tutup', variant: 'outline' },
        ],
    });

    image.src = photoButton.dataset.photoUrl;
});

document.addEventListener('click', (event) => {
    const qrButton = event.target instanceof Element
        ? event.target.closest('[data-pegawai-qr]')
        : null;

    if (!qrButton) {
        return;
    }

    const qrUrl = qrButton.dataset.pegawaiQrUrl;
    const qrDownloadUrl = qrButton.dataset.pegawaiQrDownloadUrl;
    const employeeName = qrButton.dataset.pegawaiName;
    const employeeNpp = qrButton.dataset.pegawaiNpp;
    const body = document.createElement('div');
    const description = document.createElement('p');
    const image = document.createElement('img');
    const urlLabel = document.createElement('label');
    const urlInput = document.createElement('input');

    body.className = 'pegawai-qr-modal';
    description.textContent = `${employeeName} (${employeeNpp})`;
    description.className = 'pegawai-qr-modal__description';
    image.src = qrUrl;
    image.alt = `QR Code untuk ${employeeName}`;
    image.className = 'pegawai-qr-modal__image';
    image.width = 320;
    image.height = 320;
    urlLabel.className = 'form-label pegawai-qr-modal__url-label';
    urlLabel.htmlFor = 'pegawai-qr-url';
    urlLabel.textContent = 'URL QR Code';
    urlInput.id = 'pegawai-qr-url';
    urlInput.className = 'form-control pegawai-qr-modal__url';
    urlInput.type = 'text';
    urlInput.value = new URL(qrUrl, window.location.href).href;
    urlInput.readOnly = true;
    urlInput.setAttribute('aria-label', 'URL QR Code pegawai');
    urlInput.addEventListener('focus', () => urlInput.select());
    image.addEventListener('load', () => {
        reloadPegawaiTable().catch((error) => {
            showToast(
                error instanceof Error ? error.message : 'Tabel pegawai gagal diperbarui.',
                { variant: 'error' },
            );
        });
    }, { once: true });
    image.addEventListener('error', () => {
        showToast('QR Code pegawai gagal dimuat.', { variant: 'error' });
    }, { once: true });
    body.append(description, image, urlLabel, urlInput);

    showModal({
        title: 'QR Code Pegawai',
        body,
        size: 'md',
        actions: [
            {
                label: 'Salin URL QR',
                variant: 'outline',
                action: () => {
                    const copyUrl = urlInput.value;
                    const copyWithLegacyApi = () => {
                        if (copyTextToClipboard(copyUrl)) {
                            showToast('URL QR Code berhasil disalin.', { variant: 'success' });

                            return true;
                        }

                        urlInput.focus();
                        urlInput.select();
                        showToast('URL QR Code gagal disalin otomatis. Salin URL yang sudah dipilih secara manual.', {
                            variant: 'error',
                        });

                        return false;
                    };

                    if (!navigator.clipboard?.writeText) {
                        copyWithLegacyApi();

                        return false;
                    }

                    try {
                        navigator.clipboard.writeText(copyUrl)
                            .then(() => showToast('URL QR Code berhasil disalin.', { variant: 'success' }))
                            .catch(() => copyWithLegacyApi());
                    } catch {
                        copyWithLegacyApi();
                    }

                    return false;
                },
            },
            {
                label: 'Unduh QR',
                variant: 'primary',
                action: () => {
                    const downloadLink = document.createElement('a');

                    downloadLink.href = qrDownloadUrl;
                    downloadLink.download = `qr-pegawai-${qrButton.dataset.pegawaiId}.png`;
                    downloadLink.click();

                    return false;
                },
            },
            { label: 'Tutup', variant: 'outline' },
        ],
    });
});
