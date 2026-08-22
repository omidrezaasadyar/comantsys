{{--
    Read-only summary card for the completeTask modal (App\Filament\Pages\Dashboard).

    Receives from viewData(): $title, $creator, $due, $dueStatus, $dueColor.

    Custom Blade does NOT go through Tailwind, so all styling is plain CSS in the
    scoped block below. Class names are namespaced cs-tm-* — deliberately NOT the
    dashboard's cs-task-* / cs-* names — so nothing can leak either way.
    The card paints its own dark background rather than inheriting the modal's,
    so the light text stays readable under a light Filament theme too.
--}}
<div class="cs-tm-wrap">
    <div class="cs-tm-title">{{ $title }}</div>

    <div class="cs-tm-row">
        <div class="cs-tm-cell">
            <span class="cs-tm-label">واگذار شده توسط</span>
            <span class="cs-tm-value">{{ $creator }}</span>
        </div>

        <div class="cs-tm-cell">
            <span class="cs-tm-label">تاریخ سررسید</span>
            <span class="cs-tm-value">{{ $due }}</span>
        </div>

        <div class="cs-tm-cell">
            <span class="cs-tm-label">وضعیت</span>
            <span class="cs-tm-value cs-tm-status">
                <span class="cs-tm-dot" style="background: {{ $dueColor }};"></span>{{ $dueStatus }}
            </span>
        </div>
    </div>
</div>

<style>
    .cs-tm-wrap {
        font-family: 'Vazirmatn', sans-serif;
        direction: rtl;
        text-align: right;
        background: #1a2633;
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: 12px;
        padding: 12px 14px;
    }

    .cs-tm-title {
        font-size: 17px;
        font-weight: 700;
        color: #e0762c;
        line-height: 1.5;
        word-break: break-word;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
    }

    .cs-tm-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        margin-top: 14px;
    }

    .cs-tm-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        text-align: center;
        padding: 4px 8px;
        min-width: 0;
    }

    .cs-tm-cell + .cs-tm-cell {
        border-inline-start: 1px solid rgba(255, 255, 255, .08);
    }

    .cs-tm-label {
        font-size: 11px;
        color: #8a97a8;
        line-height: 1.6;
    }

    .cs-tm-value {
        font-size: 13px;
        font-weight: 600;
        color: #e8edf4;
        line-height: 1.6;
        word-break: break-word;
    }

    .cs-tm-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .cs-tm-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-inline-end: 6px;
        vertical-align: middle;
    }
</style>
