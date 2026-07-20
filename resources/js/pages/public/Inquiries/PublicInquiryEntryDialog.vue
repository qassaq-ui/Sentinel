<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    select: [mode: 'anonymous' | 'identified'];
}>();

const { t } = useTranslations();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogScrollContent
            overlay-class="bg-slate-950/20 backdrop-blur-sm"
            class="mx-0 mt-auto mb-0 w-full max-w-none gap-0 self-end overflow-hidden rounded-t-2xl rounded-b-none border-0 bg-white p-0 pb-[env(safe-area-inset-bottom)] text-slate-900 shadow-xl sm:mx-3 sm:my-8 sm:max-w-[34rem] sm:self-auto sm:rounded-2xl sm:border sm:border-slate-200 sm:pb-0 [&>button]:rounded-xl [&>button]:p-1.5 [&>button]:transition-colors [&>button]:hover:bg-slate-100"
        >
            <span
                class="mx-auto mt-2.5 h-1.5 w-9 rounded-full bg-slate-300 sm:hidden"
                aria-hidden="true"
            />
            <DialogHeader
                class="gap-1.5 px-5 pt-4 pb-5 text-left sm:px-8 sm:pt-8 sm:pb-6"
            >
                <DialogTitle
                    class="pr-8 text-xl leading-7 font-semibold tracking-[-0.015em] text-slate-950"
                >
                    {{ t('Choose how to submit') }}
                </DialogTitle>
                <DialogDescription class="text-sm leading-5 text-slate-500">
                    {{
                        t('Choose the option that is most convenient for you.')
                    }}
                </DialogDescription>
            </DialogHeader>

            <div
                class="mx-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/40 sm:mx-6 sm:grid sm:gap-3 sm:overflow-visible sm:border-0 sm:bg-transparent"
            >
                <button
                    type="button"
                    class="group grid min-h-20 w-full grid-cols-[3rem_1fr_1.5rem] items-center gap-3 border-b border-slate-200 bg-white px-4 py-4 text-left transition-colors last:border-b-0 focus-visible:relative focus-visible:z-10 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[#007aff] active:bg-slate-100 sm:grid-cols-[3.5rem_1fr_1.5rem] sm:rounded-2xl sm:border sm:px-5 sm:py-5 sm:last:border-b sm:hover:border-slate-300 sm:hover:bg-slate-50"
                    @click="emit('select', 'anonymous')"
                >
                    <img
                        src="/images/inquiries/anonymous-submission-icon.png"
                        alt=""
                        aria-hidden="true"
                        class="size-12 object-contain sm:size-14"
                    />

                    <span class="min-w-0">
                        <span
                            class="block text-sm leading-5 font-semibold text-slate-950"
                        >
                            {{ t('Submit anonymously') }}
                        </span>
                        <span
                            class="mt-1 block max-w-sm text-xs leading-5 text-slate-500"
                        >
                            {{
                                t(
                                    'Your name and contact details will not be requested.',
                                )
                            }}
                        </span>
                    </span>
                    <ArrowRight
                        class="size-4 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-[#1875e6]"
                        :stroke-width="1.8"
                    />
                </button>

                <button
                    type="button"
                    class="group grid min-h-20 w-full grid-cols-[3rem_1fr_1.5rem] items-center gap-3 border-b border-slate-200 bg-white px-4 py-4 text-left transition-colors last:border-b-0 focus-visible:relative focus-visible:z-10 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[#007aff] active:bg-slate-100 sm:grid-cols-[3.5rem_1fr_1.5rem] sm:rounded-2xl sm:border sm:px-5 sm:py-5 sm:last:border-b sm:hover:border-slate-300 sm:hover:bg-slate-50"
                    @click="emit('select', 'identified')"
                >
                    <img
                        src="/images/inquiries/identified-submission-icon.png"
                        alt=""
                        aria-hidden="true"
                        class="size-12 object-contain sm:size-14"
                    />

                    <span class="min-w-0">
                        <span
                            class="block text-sm leading-5 font-semibold text-slate-950"
                        >
                            {{ t('Submit with contact details') }}
                        </span>
                        <span
                            class="mt-1 block max-w-xs text-xs leading-5 text-slate-500"
                        >
                            {{
                                t(
                                    'Provide your name and at least one way to contact you.',
                                )
                            }}
                        </span>
                    </span>
                    <ArrowRight
                        class="size-4 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-[#1875e6]"
                        :stroke-width="1.8"
                    />
                </button>
            </div>

            <p
                class="px-6 py-5 text-center text-xs text-slate-500 sm:px-8 sm:pb-7"
            >
                {{ t('No account is required') }}
            </p>
        </DialogScrollContent>
    </Dialog>
</template>
