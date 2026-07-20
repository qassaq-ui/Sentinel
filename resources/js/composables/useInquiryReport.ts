import { useTranslations } from '@/composables/useTranslations';
import { download as reportDownload, show as reportShow, store as reportStore } from '@/routes/inquiries/report';
import { reactive, onBeforeUnmount } from 'vue';

export type InquiryReportStatus = 'none' | 'pending' | 'processing' | 'completed' | 'failed';

type ReportState = {
    status: InquiryReportStatus;
    reportId: string | null;
    error: string;
};

const cache = new Map<string, ReturnType<typeof reactive<ReportState>>>();

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function postJson(url: string, body: Record<string, unknown>): Promise<Response> {
    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
}

export function useInquiryReport(inquiryNumber: string) {
    const { t } = useTranslations();

    if (!cache.has(inquiryNumber)) {
        cache.set(inquiryNumber, reactive<ReportState>({ status: 'none', reportId: null, error: '' }));
    }

    const state = cache.get(inquiryNumber)!;

    let pollTimer: ReturnType<typeof setTimeout> | null = null;

    function apply(payload: { report_id?: string; status?: string; pdf_path?: string | null; error_message?: string | null }) {
        if (payload.report_id) {
            state.reportId = payload.report_id;
        }
        if (payload.status && ['pending', 'processing', 'completed', 'failed', null].includes(payload.status)) {
            state.status = (payload.status as InquiryReportStatus) ?? 'none';
        }
        state.error = payload.error_message ?? '';
    }

    async function refresh(): Promise<void> {
        try {
            const response = await fetch(reportShow(inquiryNumber).url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json() as { status?: string | null; report_id?: string; error_message?: string | null };
            if (response.ok) {
                apply(payload as Parameters<typeof apply>[0]);
            }
        } catch {
            // network hiccup — keep current state, retry on next tick
        }
    }

    function stopPolling(): void {
        if (pollTimer !== null) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    }

    function schedulePoll(): void {
        stopPolling();
        pollTimer = setTimeout(pollOnce, 2500);
    }

    async function pollOnce(): Promise<void> {
        await refresh();
        if (state.status === 'pending' || state.status === 'processing') {
            schedulePoll();
        } else {
            stopPolling();
        }
    }

    async function init(): Promise<void> {
        await refresh();
        if (state.status === 'pending' || state.status === 'processing') {
            schedulePoll();
        }
    }

    async function generate(language: string): Promise<void> {
        if (state.status === 'pending' || state.status === 'processing') {
            return;
        }
        state.error = '';
        state.status = 'pending';
        try {
            const response = await postJson(reportStore(inquiryNumber).url, { language });
            const payload = await response.json() as Parameters<typeof apply>[0];
            if (response.ok) {
                apply(payload);
                schedulePoll();
            } else {
                state.status = 'failed';
                state.error = (payload as { message?: string }).message ?? t('Report generation failed. Retry?');
            }
        } catch {
            state.status = 'failed';
            state.error = t('Report generation failed. Retry?');
        }
    }

    function downloadUrl(): string {
        return reportDownload({ inquiry: inquiryNumber, report: state.reportId ?? '' }).url;
    }

    function download(): void {
        window.location.href = downloadUrl();
    }

    onBeforeUnmount(stopPolling);

    return {
        state,
        init,
        generate,
        download,
        downloadUrl,
        stopPolling,
    };
}
