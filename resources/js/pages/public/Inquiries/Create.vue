<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ClipboardList,
    LockKeyhole,
    Mail,
    MessageCircle,
    MessageSquare,
    Phone,
    ScanLine,
    Search,
    UserRoundCheck,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import PublicInquiryEntryDialog from './PublicInquiryEntryDialog.vue';
import PublicInquiryFormDialog from './PublicInquiryFormDialog.vue';
import PublicInquiryResponseDialog from './PublicInquiryResponseDialog.vue';
import PublicInquiryStatusDialog from './PublicInquiryStatusDialog.vue';
import SpeakUpLogo from './SpeakUpLogo.vue';

type Category = {
    id: number;
    name: string;
    description: string;
};

type SubmissionFlash = {
    number: string;
    accessCode: string | null;
};

const props = defineProps<{
    categories: Category[];
    aiScreeningEnabled: boolean;
    alternativeInquiriesEmail: string;
}>();

const { t } = useTranslations();
const page = usePage();
const formRequestedOpen = ref(Object.keys(page.props.errors ?? {}).length > 0);
const initialSubmission = submissionFromFlash(
    page.flash as Record<string, unknown>,
);
const submittedInquiryNumber = ref(initialSubmission?.number ?? null);
const submittedInquiryAccessCode = ref(initialSubmission?.accessCode ?? null);
const formOpen = computed({
    get: () => formRequestedOpen.value || submittedInquiryNumber.value !== null,
    set: (open: boolean) => {
        if (!open && submittedInquiryNumber.value !== null) {
            return;
        }

        formRequestedOpen.value = open;
    },
});
let removeFlashListener: VoidFunction | null = null;

function submissionFromFlash(
    flash: Record<string, unknown>,
): SubmissionFlash | null {
    const submission = flash.submission;

    if (
        typeof submission !== 'object' ||
        submission === null ||
        !('number' in submission) ||
        typeof submission.number !== 'string'
    ) {
        return null;
    }

    return {
        number: submission.number,
        accessCode:
            'accessCode' in submission &&
            typeof submission.accessCode === 'string'
                ? submission.accessCode
                : null,
    };
}

function dismissSubmittedInquiry(): void {
    submittedInquiryNumber.value = null;
    submittedInquiryAccessCode.value = null;
    formRequestedOpen.value = false;
}

onMounted(() => {
    removeFlashListener = router.on('flash', (event) => {
        const submission = submissionFromFlash(
            event.detail.flash as Record<string, unknown>,
        );

        if (submission) {
            submittedInquiryNumber.value = submission.number;
            submittedInquiryAccessCode.value = submission.accessCode;
            formRequestedOpen.value = true;
        }
    });
});

onBeforeUnmount(() => {
    removeFlashListener?.();
});
const entryOpen = ref(false);
const statusOpen = ref(false);
const responseOpen = ref(false);
const responseCredentials = ref({ number: '', accessCode: '' });
const initialMode = ref<'anonymous' | 'identified'>('anonymous');
const openFaq = ref(0);

const informationCards = computed(() => [
    {
        icon: ClipboardList,
        title: t('For all corporate inquiries'),
        items: [
            t('Work-related questions and situations'),
            t('Ideas and suggestions for improvement'),
            t('Other inquiries related to company activities'),
        ],
    },
    {
        icon: Mail,
        title: t('Convenient inquiry submission'),
        items: [
            t('Submit inquiries at any convenient time'),
            t('A simple and clear submission process'),
            t('Describe the situation or question in detail'),
        ],
    },
    {
        icon: LockKeyhole,
        title: t('Flexible submission format'),
        items: [
            t('Submit anonymously'),
            t('Submit with contact details'),
            t('Track the inquiry status'),
        ],
    },
    {
        icon: UserRoundCheck,
        title: t('From inquiry to outcome'),
        items: [
            t('Inquiries are reviewed by authorized employees'),
            t('Information is appropriately assessed and processed'),
            t('Feedback is provided following the review'),
        ],
    },
]);

const faqs = computed(() => [
    {
        question: t('Can I submit an inquiry without an account?'),
        answer: t(
            'Yes. You can submit an inquiry without creating an account. You may submit it anonymously or provide contact details.',
        ),
    },
    {
        question: t('What materials can I attach?'),
        answer: t(
            'You can attach supporting materials, including documents, images, audio recordings, and other file types.',
        ),
    },
    {
        question: t('What is the hotline and why is it needed?'),
        answer: t(
            'The hotline is a secure channel for questions, suggestions, concerns, potential incidents, and suspected violations.',
        ),
    },
    {
        question: t('What issues can be raised through the hotline?'),
        answer: t(
            'You may report workplace matters, safety risks, ethics concerns, suspected misconduct, or ideas for improvement.',
        ),
    },
    {
        question: t('Who can use the hotline?'),
        answer: t(
            'Employees, contractors, partners, and other people connected with the company may use the hotline.',
        ),
    },
    {
        question: t('How is my information processed and stored?'),
        answer: t(
            'Information is handled confidentially and is available only to authorized employees responsible for reviewing the inquiry.',
        ),
    },
]);

function openInquiryForm(mode: 'anonymous' | 'identified' = 'anonymous') {
    entryOpen.value = false;
    initialMode.value = mode;
    formOpen.value = true;
}

function toggleFaq(index: number) {
    openFaq.value = openFaq.value === index ? -1 : index;
}

function openInquiryResponse(credentials: {
    number: string;
    accessCode: string;
}): void {
    statusOpen.value = false;
    responseCredentials.value = credentials;
    responseOpen.value = true;
}
</script>

<template>
    <div
        class="relative min-h-screen w-full overflow-x-clip bg-white font-[ui-sans-serif,-apple-system,BlinkMacSystemFont,'SF_Pro_Display','Segoe_UI',sans-serif] text-slate-900 selection:bg-blue-200 selection:text-blue-900"
    >
        <Head :title="t('Hotline')" />

        <div
            class="pointer-events-none absolute inset-0 z-0 hidden overflow-hidden md:block"
        >
            <svg
                viewBox="0 0 1440 1800"
                class="h-full min-h-[1800px] w-full opacity-30"
                preserveAspectRatio="none"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M-100 430C180 340 420 650 720 680C1030 710 1240 350 1540 290"
                    stroke="#e0e7ff"
                    stroke-width="3"
                />
                <path
                    d="M-80 1180C250 1060 440 1420 780 1390C1100 1360 1320 1570 1570 1510"
                    stroke="#f1f5f9"
                    stroke-width="2"
                />
                <path
                    d="M1210 -100C1080 320 1250 920 1360 1900"
                    stroke="#e0e7ff"
                    stroke-width="2"
                />
                <path
                    d="M300 -100C410 340 190 980 90 1880"
                    stroke="#f1f5f9"
                    stroke-width="2"
                />
            </svg>

            <div class="hidden lg:block">
                <div
                    class="absolute top-[20%] left-[11%] -rotate-12 opacity-35"
                >
                    <div
                        class="flex h-24 w-32 items-center justify-center rounded-[1.5rem] border-[10px] border-slate-100 bg-white shadow-xl shadow-slate-200/50"
                    >
                        <Phone
                            class="size-10 text-blue-400"
                            :stroke-width="1.5"
                        />
                    </div>
                </div>
                <div
                    class="absolute top-[22%] right-[11%] rotate-12 opacity-35"
                >
                    <div
                        class="flex h-24 w-32 items-center justify-center rounded-[1.5rem] border-[10px] border-slate-100 bg-white shadow-xl shadow-slate-200/50"
                    >
                        <MessageSquare
                            class="size-10 text-blue-400"
                            :stroke-width="1.5"
                        />
                    </div>
                </div>
                <div
                    class="absolute bottom-[16%] left-[8%] -rotate-[15deg] opacity-30"
                >
                    <div
                        class="flex h-24 w-32 items-center justify-center rounded-[1.5rem] border-[10px] border-slate-100 bg-white shadow-xl shadow-slate-200/50"
                    >
                        <ClipboardList
                            class="size-10 text-blue-400"
                            :stroke-width="1.5"
                        />
                    </div>
                </div>
                <div
                    class="absolute right-[14%] bottom-[12%] rotate-[10deg] opacity-30"
                >
                    <div
                        class="flex h-24 w-32 items-center justify-center rounded-[1.5rem] border-[10px] border-slate-100 bg-white shadow-xl shadow-slate-200/50"
                    >
                        <MessageCircle
                            class="size-10 text-blue-400"
                            :stroke-width="1.5"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div
            class="relative z-10 mx-auto flex w-full max-w-7xl flex-col px-4 pb-28 sm:py-4 sm:pb-4"
        >
            <header
                class="sticky top-0 z-40 -mx-4 mb-2 flex h-16 shrink-0 items-center justify-between border-b border-slate-900/10 bg-slate-50/20 px-4 backdrop-blur-lg sm:static sm:mx-0 sm:mt-2 sm:mb-12 sm:h-20 sm:rounded-2xl sm:border sm:border-slate-200 sm:px-6"
            >
                <SpeakUpLogo />
                <LanguageSwitcher />
            </header>

            <main
                class="mb-10 flex flex-col items-center px-1 pt-8 pb-5 text-center sm:mb-8 sm:px-0 sm:pt-0 sm:pb-0 md:mb-20"
            >
                <h1
                    class="mb-4 text-4xl font-semibold tracking-[-0.055em] text-slate-950 sm:text-5xl md:text-6xl lg:text-7xl"
                >
                    {{ t('Hotline') }}
                </h1>
                <p
                    class="mb-0 max-w-xl text-[0.9375rem] leading-6 font-normal text-slate-500 sm:mx-auto sm:mb-8 sm:max-w-2xl sm:px-1 sm:text-base sm:leading-7 md:mb-10 md:px-2 md:text-lg"
                >
                    {{
                        t(
                            'The hotline accepts inquiries across all areas of the company. These may include questions, suggestions, concerns, potential incidents, or suspected violations. All inquiries are reviewed and processed by authorized employees in accordance with established procedures.',
                        )
                    }}
                </p>
                <div
                    class="mx-auto hidden w-full max-w-[560px] flex-col justify-center gap-3 sm:flex sm:flex-row sm:gap-4"
                >
                    <button
                        type="button"
                        class="w-full rounded-xl bg-[#1875e6] px-5 py-3.5 text-base font-semibold whitespace-nowrap text-white shadow-sm transition-colors hover:bg-[#1267ce] active:bg-blue-800 sm:w-auto sm:min-w-[220px] sm:px-8 sm:py-4 md:text-lg"
                        @click="entryOpen = true"
                    >
                        {{ t('Submit an inquiry') }}
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-base font-semibold whitespace-nowrap text-[#1875e6] transition-colors hover:border-slate-400 hover:bg-slate-50 active:bg-slate-100 sm:w-auto sm:min-w-[220px] sm:px-8 sm:py-4 md:text-lg"
                        @click="statusOpen = true"
                    >
                        {{ t('Check inquiry status') }}
                    </button>
                </div>
            </main>

            <section class="mb-14 md:mb-24">
                <h2
                    class="mb-5 px-1 text-left text-2xl font-semibold tracking-[-0.035em] text-slate-950 sm:text-center sm:text-3xl md:mb-12 md:text-4xl lg:text-5xl"
                >
                    {{ t('Key information') }}
                </h2>
                <div
                    class="-mx-4 flex snap-x snap-mandatory [scrollbar-width:none] gap-3 overflow-x-auto px-4 pb-3 sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-4 sm:overflow-visible sm:px-0 sm:pb-0 md:gap-8 lg:gap-6 [&::-webkit-scrollbar]:hidden"
                >
                    <article
                        v-for="card in informationCards"
                        :key="card.title"
                        class="w-[84vw] max-w-sm shrink-0 snap-start rounded-2xl border border-slate-200 bg-white p-6 transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:w-auto sm:max-w-none sm:p-7 md:p-8"
                    >
                        <div class="mb-6 flex items-center gap-4">
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[#1875e6] ring-1 ring-blue-100/70"
                            >
                                <component
                                    :is="card.icon"
                                    class="size-5"
                                    :stroke-width="2.5"
                                />
                            </div>
                            <h3
                                class="text-sm font-semibold tracking-[-0.015em] text-slate-900 sm:text-base"
                            >
                                {{ card.title }}
                            </h3>
                        </div>
                        <ul class="space-y-3">
                            <li
                                v-for="item in card.items"
                                :key="item"
                                class="flex items-start gap-3 text-sm font-medium text-slate-600 md:text-base"
                            >
                                <span class="mt-[-2px] text-xl text-blue-500"
                                    >•</span
                                >
                                {{ item }}
                            </li>
                        </ul>
                    </article>
                </div>
            </section>

            <section class="mx-auto mb-8 w-full max-w-4xl sm:mb-20 sm:px-4">
                <h2
                    class="mb-5 px-1 text-left text-2xl font-semibold tracking-[-0.035em] text-slate-950 sm:mb-8 sm:text-center md:mb-12 md:text-3xl"
                >
                    {{ t('Frequently asked questions') }}
                </h2>
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                >
                    <article
                        v-for="(faq, index) in faqs"
                        :key="faq.question"
                        class="group relative border-b border-slate-200 bg-transparent p-4 transition-colors last:border-b-0 hover:bg-slate-50 sm:p-6 md:p-7"
                    >
                        <button
                            type="button"
                            class="flex w-full items-start justify-between text-left"
                            :aria-expanded="openFaq === index"
                            @click="toggleFaq(index)"
                        >
                            <div class="pr-6 sm:pr-12">
                                <h3
                                    class="mb-2 text-base font-bold text-slate-800 sm:text-lg"
                                >
                                    {{ faq.question }}
                                </h3>
                                <div
                                    class="grid transition-all duration-300 ease-in-out"
                                    :class="
                                        openFaq === index
                                            ? 'mt-3 grid-rows-[1fr] opacity-100'
                                            : 'grid-rows-[0fr] opacity-0'
                                    "
                                >
                                    <div class="overflow-hidden">
                                        <p
                                            class="text-xs leading-relaxed text-slate-500 sm:text-sm"
                                        >
                                            {{ faq.answer }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <span
                                class="mt-1 flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#1875e6] ring-1 ring-blue-100/70 transition-transform duration-300"
                                :class="openFaq === index ? 'rotate-180' : ''"
                            >
                                <ChevronDown class="size-4" />
                            </span>
                        </button>
                    </article>
                </div>
            </section>
        </div>

        <footer
            class="relative z-10 mt-4 mb-20 w-full overflow-hidden sm:mt-12 sm:mb-0"
        >
            <div
                class="border-y border-slate-200 bg-slate-50/40 px-6 py-8 sm:mx-4 sm:rounded-t-[3rem] sm:border-x sm:py-12 md:mx-8 md:rounded-t-[4rem] md:px-12 md:py-16 lg:px-20"
            >
                <div
                    class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 md:flex-row"
                >
                    <div class="flex-1 text-center md:text-left">
                        <SpeakUpLogo compact />
                    </div>
                    <div
                        class="flex items-center gap-3 text-center text-sm font-black text-slate-800 md:text-right md:text-base"
                    >
                        <Phone class="size-5 text-[#1875e6]" />
                        <span>{{ t('Contact') }}: +7 747 120 34 67</span>
                    </div>
                </div>
            </div>
        </footer>

        <button
            type="button"
            class="fixed right-5 bottom-5 z-30 hidden size-16 items-center justify-center rounded-full bg-[#2463eb] text-white shadow-2xl shadow-blue-300 transition-transform hover:scale-105 sm:flex md:right-12 md:bottom-12"
            :aria-label="t('Submit an inquiry')"
            @click="entryOpen = true"
        >
            <ScanLine class="size-7" />
        </button>

        <nav
            class="fixed right-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] left-3 z-40 rounded-2xl border border-slate-900/10 bg-slate-50/20 p-2 shadow-lg backdrop-blur-lg sm:hidden"
            :aria-label="t('Hotline')"
        >
            <div class="mx-auto grid max-w-md grid-cols-2 gap-2">
                <button
                    type="button"
                    class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#007aff] px-3 text-xs font-semibold whitespace-nowrap text-white active:bg-[#0062cc]"
                    @click="entryOpen = true"
                >
                    <ScanLine class="size-4 shrink-0" />
                    {{ t('Submit an inquiry') }}
                </button>
                <button
                    type="button"
                    class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-black/5 px-3 text-xs font-semibold whitespace-nowrap text-slate-800 active:bg-black/10"
                    @click="statusOpen = true"
                >
                    <Search class="size-4 shrink-0" />
                    {{ t('Check status') }}
                </button>
            </div>
        </nav>

        <PublicInquiryEntryDialog
            v-model:open="entryOpen"
            @select="openInquiryForm"
        />

        <PublicInquiryStatusDialog
            v-model:open="statusOpen"
            @view-response="openInquiryResponse"
        />

        <PublicInquiryResponseDialog
            v-model:open="responseOpen"
            :number="responseCredentials.number"
            :access-code="responseCredentials.accessCode"
        />

        <PublicInquiryFormDialog
            v-model:open="formOpen"
            :categories="props.categories"
            :initial-mode="initialMode"
            :ai-screening-enabled="props.aiScreeningEnabled"
            :alternative-inquiries-email="props.alternativeInquiriesEmail"
            :submission-number="submittedInquiryNumber"
            :submission-access-code="submittedInquiryAccessCode"
            @accepted="dismissSubmittedInquiry"
            @back="entryOpen = true"
        />
    </div>
</template>
