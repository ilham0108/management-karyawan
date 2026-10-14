// Gentelella v4 — Vite entry.
//
// The npm package's main entry mounts the shell, wires the topbar, theme
// toggle, command palette and delegated interactions, and imports its own SCSS.
// Page modules (charts, tables, calendar, kanban, …) lazy-load themselves when
// their host element is present, so nothing unused ships.
import 'gentelella';
import { closeModal, showModal } from 'gentelella/v4/modal';
import { showToast } from 'gentelella/v4/toast';

// Your overrides, loaded after the design system so they win.
import '../css/gentelella.scss';

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
                'Kosongkan jika foto saat ini tidak ingin diganti. Format JPG, PNG, atau WebP; maksimal 2 MB.';
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
    const photoButton = event.target instanceof Element
        ? event.target.closest('[data-pegawai-profile-photo]')
        : null;

    if (!photoButton) {
        return;
    }

    const body = document.createElement('div');
    const image = document.createElement('img');
    const employeeName = photoButton.dataset.pegawaiName;

    image.src = photoButton.dataset.photoUrl;
    image.alt = `Foto profil ${employeeName}`;
    image.className = 'pegawai-profile-photo-preview';
    image.addEventListener('error', () => {
        showToast('Foto profil pegawai gagal dimuat.', { variant: 'error' });
    }, { once: true });
    body.append(image);

    showModal({
        title: `Foto Profil: ${employeeName}`,
        body,
        size: 'md',
        actions: [
            { label: 'Tutup', variant: 'outline' },
        ],
    });
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

    description.textContent = `${employeeName} (${employeeNpp})`;
    image.src = qrUrl;
    image.alt = `QR Code untuk ${employeeName}`;
    image.width = 320;
    image.height = 320;
    image.style.maxWidth = '100%';
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
    body.append(description, image);

    showModal({
        title: 'QR Code Pegawai',
        body,
        size: 'md',
        actions: [
            {
                label: 'Salin URL QR',
                variant: 'outline',
                action: () => {
                    if (!navigator.clipboard?.writeText) {
                        showToast('Browser tidak mendukung penyalinan otomatis.', { variant: 'error' });

                        return false;
                    }

                    navigator.clipboard.writeText(new URL(qrUrl, window.location.href).href)
                        .then(() => showToast('URL QR Code berhasil disalin.', { variant: 'success' }))
                        .catch(() => showToast('URL QR Code gagal disalin.', { variant: 'error' }));

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

const qrScanner = document.querySelector('[data-qr-scanner]');

if (qrScanner) {
    const video = qrScanner.querySelector('[data-scanner-video]');
    const status = qrScanner.querySelector('[data-scanner-status]');
    const statusText = qrScanner.querySelector('[data-scanner-status-text]');
    const loadingOverlay = qrScanner.querySelector('[data-scanner-loading]');
    const startButton = qrScanner.querySelector('[data-scanner-start]');
    const stopButton = qrScanner.querySelector('[data-scanner-stop]');
    const scanAgainButton = qrScanner.querySelector('[data-scanner-again]');
    const resultSection = qrScanner.querySelector('[data-scanner-result]');
    const resultPhoto = qrScanner.querySelector('[data-result-photo]');
    const resultPhotoEmpty = qrScanner.querySelector('[data-result-photo-empty]');
    const activeMessage = qrScanner.querySelector('[data-result-active-message]');
    let codeReader = null;
    let scannerControls = null;
    let cameraStream = null;
    let lastScannedPayload = null;
    let isLookingUp = false;

    const setScannerStatus = (message, isError = false, isLoading = false) => {
        statusText.textContent = message;
        status.classList.toggle('is-error', isError);
        status.setAttribute('aria-busy', String(isLoading));
        loadingOverlay.hidden = !isLoading;
        loadingOverlay.setAttribute('aria-busy', String(isLoading));
    };

    const stopCamera = (controls = scannerControls) => {
        controls?.stop();
        scannerControls = null;
        cameraStream?.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        video.srcObject = null;
        startButton.disabled = false;
        stopButton.disabled = true;
    };

    const waitForVideoFrame = () => new Promise((resolve, reject) => {
        if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA && video.videoWidth > 0) {
            resolve();

            return;
        }

        let timeout;
        const cleanup = () => {
            window.clearTimeout(timeout);
            video.removeEventListener('loadeddata', checkVideoFrame);
            video.removeEventListener('resize', checkVideoFrame);
            video.removeEventListener('error', handleVideoError);
        };
        const checkVideoFrame = () => {
            if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA && video.videoWidth > 0) {
                cleanup();
                resolve();
            }
        };
        const handleVideoError = () => {
            cleanup();
            reject(new Error('Browser gagal menerima gambar dari kamera.'));
        };

        video.addEventListener('loadeddata', checkVideoFrame);
        video.addEventListener('resize', checkVideoFrame);
        video.addEventListener('error', handleVideoError);
        timeout = window.setTimeout(() => {
            cleanup();
            reject(new Error('Kamera aktif, tetapi belum mengirim gambar. Periksa kamera yang dipilih dan izin browser.'));
        }, 10000);
        checkVideoFrame();
    });

    resultPhoto.addEventListener('error', () => {
        resultPhoto.hidden = true;
        resultPhotoEmpty.hidden = false;
    });

    const showPegawai = (pegawai) => {
        qrScanner.querySelector('[data-result-name]').textContent = pegawai.nama;
        qrScanner.querySelector('[data-result-npp]').textContent = pegawai.npp;
        qrScanner.querySelector('[data-result-jabatan]').textContent = pegawai.jabatan || '-';
        qrScanner.querySelector('[data-result-status]').textContent = pegawai.status;
        resultPhoto.hidden = !pegawai.profile_photo_url;
        resultPhotoEmpty.hidden = Boolean(pegawai.profile_photo_url);
        resultPhoto.alt = `Foto profil ${pegawai.nama}`;
        activeMessage.hidden = pegawai.status !== 'aktif';

        if (pegawai.profile_photo_url) {
            resultPhoto.src = new URL(pegawai.profile_photo_url, window.location.href).href;
        } else {
            resultPhoto.removeAttribute('src');
        }

        resultSection.hidden = false;
        scanAgainButton.disabled = false;
    };

    const lookupPayload = async (payload) => {
        isLookingUp = true;
        lastScannedPayload = payload;
        setScannerStatus('QR terbaca. Mengambil data pegawai terbaru...', false, true);

        try {
            const response = await fetch(qrScanner.dataset.lookupUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': qrScanner.dataset.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ payload }),
                credentials: 'same-origin',
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Data pegawai tidak dapat ditemukan.');
            }

            showPegawai(result.data);
            setScannerStatus('Data pegawai berhasil diperbarui.');
        } catch (error) {
            setScannerStatus(
                error instanceof Error ? error.message : 'Gagal mengambil data pegawai.',
                true,
            );
        } finally {
            isLookingUp = false;
        }
    };

    startButton.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setScannerStatus('Kamera memerlukan koneksi HTTPS atau localhost.', true);

            return;
        }

        startButton.disabled = true;
        setScannerStatus('Menghubungkan ke kamera...');

        try {
            if (!codeReader) {
                const { BrowserQRCodeReader } = await import('@zxing/browser');
                codeReader = new BrowserQRCodeReader();
            }

            cameraStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            });
            video.srcObject = cameraStream;
            await video.play();
            await waitForVideoFrame();

            let scannerError = null;
            const controls = await codeReader.decodeFromVideoElement(video, (result, error, decodeControls) => {
                if (result) {
                    const payload = result.getText();

                    if (!isLookingUp && payload !== lastScannedPayload) {
                        lookupPayload(payload);
                    }

                    return;
                }

                const errorKind = error?.getKind?.() || error?.name;

                if (error && !['NotFoundException', 'ChecksumException', 'FormatException'].includes(errorKind)) {
                    scannerError = error;
                    stopCamera(decodeControls);
                    setScannerStatus(`Pemindai QR gagal membaca gambar: ${error.message || errorKind}`, true);
                } else if (!lastScannedPayload) {
                    setScannerStatus('Arahkan QR Code ke dalam bingkai kamera.');
                }
            });

            if (scannerError) {
                return;
            }

            scannerControls = controls;
            stopButton.disabled = false;
            setScannerStatus('Kamera aktif. Arahkan QR Code ke dalam bingkai.');
        } catch (error) {
            stopCamera();
            if (error instanceof Error && error.name === 'NotAllowedError') {
                setScannerStatus('Izin kamera ditolak. Izinkan akses kamera lalu coba lagi.', true);
            } else {
                const detail = error instanceof Error ? ` ${error.message}` : '';
                setScannerStatus(`Kamera tidak dapat dibuka.${detail}`, true);
            }
        }
    });

    stopButton.addEventListener('click', () => {
        stopCamera();
        setScannerStatus('Kamera dimatikan.');
    });

    scanAgainButton.addEventListener('click', () => {
        lastScannedPayload = null;
        resultSection.hidden = true;
        scanAgainButton.disabled = true;
        setScannerStatus('Arahkan QR Code pegawai berikutnya ke dalam bingkai.');
    });

    window.addEventListener('pagehide', stopCamera);
}
