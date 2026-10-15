@extends('gentelella::layouts.blank')

@section('title', 'Data Pegawai')

@section('content')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">

    <style>
        .pid-page {
            --pp-bg: #eceff1;
            --pp-card: #ffffff;
            --pp-frame: #d3d6d9;
            --pp-text: #0d1417;
            --pp-muted: #4a5a60;
            --pp-accent: #143f45;
            --pp-line: #dde3e6;

            box-sizing: border-box;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 22px;
            padding: 28px 16px 36px;
            background: var(--pp-bg);
            color: var(--pp-text);
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .pid-page *,
        .pid-page *::before,
        .pid-page *::after {
            box-sizing: border-box;
        }

        /* Status (di atas kartu) */
        .pid-card__status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            margin: 0;
            padding: 8px 16px 8px 12px;
            border-radius: 999px;
            background: #143f45;
            color: #e9f6f4;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.3;
        }

        .pid-card__status.is-inactive {
            background: #4a2326;
            color: #fbe9ea;
        }

        .pid-card__status-dot {
            flex: none;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #5fd6a8;
            box-shadow: 0 0 0 3px rgba(95, 214, 168, .28);
        }

        .is-inactive .pid-card__status-dot {
            background: #f08a8f;
            box-shadow: 0 0 0 3px rgba(240, 138, 143, .28);
        }

        /* Kartu */
        .pid-card {
            width: 100%;
            max-width: 340px;
            padding: 16px 16px 26px;
            border-radius: 30px;
            background: var(--pp-card);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .9),
                0 1px 2px rgba(13, 20, 23, .08),
                0 10px 20px rgba(13, 20, 23, .10),
                0 28px 48px -8px rgba(13, 20, 23, .24);
        }

        .pid-card__photo-frame {
            display: flex;
            flex-direction: column;
            aspect-ratio: 5 / 6;
            padding-top: 22px;
            overflow: hidden;
            border-radius: 22px;
            background: var(--pp-frame);
            text-align: center;
        }

        .pid-card__name {
            margin: 0 0 10px;
            padding: 0 14px;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: .03em;
            text-transform: uppercase;
            text-wrap: balance;
            overflow-wrap: anywhere;
        }

        .pid-card__photo {
            display: block;
            flex: 1;
            width: 100%;
            min-height: 0;
            object-fit: cover;
            object-position: center top;
        }

        .pid-card__photo-placeholder {
            flex: 1;
            display: grid;
            place-items: center;
            color: #8a949a;
            font-size: 96px;
            font-weight: 800;
        }

        /* Jabatan & NPP */
        .pid-card__details {
            margin: 22px 0 0;
            padding: 0 8px;
            text-align: center;
        }

        .pid-card__row {
            margin: 0;
        }

        .pid-card__row+.pid-card__row {
            margin-top: 14px;
        }

        .pid-card__label {
            display: block;
            margin: 0 0 4px;
            color: var(--pp-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .pid-card__value {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .06em;
            font-variant-numeric: tabular-nums;
        }

        .pid-card__position {
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .pid-card__position::after {
            content: "";
            display: block;
            width: 28px;
            height: 3px;
            margin: 12px auto 0;
            border-radius: 3px;
            background: var(--pp-accent);
        }

        .pid-card__copy {
            min-height: 40px;
            padding: 0 16px;
            border: 1px solid var(--pp-line);
            border-radius: 8px;
            background: transparent;
            color: var(--pp-muted);
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0;
            cursor: pointer;
            transition: color .15s, border-color .15s;
        }

        .pid-card__copy:hover {
            color: var(--pp-accent);
            border-color: var(--pp-accent);
        }

        .pid-card__copy:focus-visible {
            outline: 2px solid var(--pp-accent);
            outline-offset: 2px;
        }

        @media (max-width: 360px) {
            .pid-page {
                padding: 20px 12px 28px;
            }

            .pid-card {
                padding: 12px 12px 22px;
                border-radius: 26px;
            }

            .pid-card__name {
                font-size: 16px;
            }
        }
    </style>

    <main class="pid-page">
        @if ($pegawai->status === 'aktif')
            <p class="pid-card__status is-active" role="status">
                <span class="pid-card__status-dot" aria-hidden="true"></span>
                <span>Aktif sebagai pegawai ISF Waingapu</span>
            </p>
        @else
            <p class="pid-card__status is-inactive" role="status">
                <span class="pid-card__status-dot" aria-hidden="true"></span>
                <span>Tidak aktif</span>
            </p>
        @endif

        <article class="pid-card" aria-labelledby="pegawai-name">
            <header class="pid-card__photo-frame">
                <h1 class="pid-card__name" id="pegawai-name">{{ $pegawai->nama }}</h1>

                @if ($pegawai->profile_photo)
                    <img class="pid-card__photo" src="{{ route('pegawai.profile-photo', $pegawai) }}" alt="Foto profil {{ $pegawai->nama }}">
                @else
                    <div class="pid-card__photo-placeholder" role="img" aria-label="Foto profil belum tersedia">
                        {{ mb_strtoupper(mb_substr($pegawai->nama, 0, 1)) }}
                    </div>
                @endif
            </header>

            <dl class="pid-card__details">
                <div class="pid-card__row">
                    <dt class="pid-card__label">Jabatan</dt>
                    <dd class="pid-card__value">
                        <span class="pid-card__position">{{ $pegawai->jabatan?->nama ?? '-' }}</span>
                    </dd>
                </div>
                <div class="pid-card__row">
                    <dt class="pid-card__label">NPP</dt>
                    <dd class="pid-card__value">
                        <span>{{ $pegawai->npp }}</span>
                        <button type="button" class="pid-card__copy" data-copy-npp="{{ $pegawai->npp }}" aria-label="Salin NPP">Salin</button>
                    </dd>
                </div>
            </dl>
        </article>
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-copy-npp]').forEach((button) => {
            button.addEventListener('click', async () => {
                const originalLabel = 'Salin';

                try {
                    await navigator.clipboard.writeText(button.dataset.copyNpp);
                    button.textContent = 'Tersalin';
                    button.setAttribute('aria-label', 'NPP berhasil disalin');
                } catch {
                    button.textContent = 'Gagal';
                    button.setAttribute('aria-label', 'NPP gagal disalin');
                }

                window.setTimeout(() => {
                    button.textContent = originalLabel;
                    button.setAttribute('aria-label', 'Salin NPP');
                }, 2000);
            });
        });
    </script>
@endpush
