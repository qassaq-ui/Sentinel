<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    modelValue: string;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const { t } = useTranslations();
const page = usePage();
const isOpen = ref(false);
const visibleMonth = ref(
    monthStart(props.modelValue ? parseIsoDate(props.modelValue) : new Date()),
);

const locale = computed(() => String(page.props.locale.current ?? 'ru'));

const weekDays = computed(() => {
    const monday = new Date(2026, 0, 5);

    return Array.from({ length: 7 }, (_, index) =>
        addDays(monday, index).toLocaleDateString(locale.value, {
            weekday: 'short',
        }),
    );
});

const monthLabel = computed(() =>
    visibleMonth.value.toLocaleDateString(locale.value, {
        month: 'long',
        year: 'numeric',
    }),
);

const selectedLabel = computed(() => {
    if (props.modelValue === '') {
        return 'Выберите дату';
    }

    return parseIsoDate(props.modelValue).toLocaleDateString(locale.value, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
});

const calendarDays = computed(() => {
    const start = monthStart(visibleMonth.value);
    const offset = mondayOffset(start.getDay());
    const firstCell = addDays(start, -offset);

    return Array.from({ length: 42 }, (_, index) => {
        const date = addDays(firstCell, index);
        const iso = formatIsoDate(date);

        return {
            date,
            iso,
            day: date.getDate(),
            inMonth: date.getMonth() === visibleMonth.value.getMonth(),
            selected: props.modelValue === iso,
            today: formatIsoDate(new Date()) === iso,
        };
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (value !== '') {
            visibleMonth.value = monthStart(parseIsoDate(value));
        }
    },
);

function parseIsoDate(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function formatIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function monthStart(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addDays(date: Date, days: number): Date {
    const nextDate = new Date(date);
    nextDate.setDate(nextDate.getDate() + days);

    return nextDate;
}

function mondayOffset(day: number): number {
    return day === 0 ? 6 : day - 1;
}

function previousMonth() {
    visibleMonth.value = new Date(
        visibleMonth.value.getFullYear(),
        visibleMonth.value.getMonth() - 1,
        1,
    );
}

function nextMonth() {
    visibleMonth.value = new Date(
        visibleMonth.value.getFullYear(),
        visibleMonth.value.getMonth() + 1,
        1,
    );
}

function selectDate(value: string) {
    emit('update:modelValue', value);
    isOpen.value = false;
}

function clearDate() {
    emit('update:modelValue', '');
    isOpen.value = false;
}
</script>

<template>
    <div class="relative grid gap-2">
        <span
            class="px-0.5 text-[11px] font-semibold tracking-[0.04em] text-slate-500 uppercase dark:text-slate-400"
        >
            {{ label }}
        </span>

        <Button
            type="button"
            variant="outline"
            class="h-10 justify-start gap-2 rounded-[10px] border-black/10 bg-white px-3 text-left text-sm font-normal shadow-none hover:bg-white focus-visible:ring-2 focus-visible:ring-[#007aff]/15 dark:border-white/10 dark:bg-white/[0.07] dark:hover:bg-white/10"
            :class="
                modelValue === '' ? 'text-muted-foreground' : 'text-foreground'
            "
            :aria-expanded="isOpen"
            @click="isOpen = !isOpen"
        >
            <CalendarDays class="size-4 text-muted-foreground" />
            <span class="truncate">
                {{ modelValue === '' ? t('Choose date') : selectedLabel }}
            </span>
        </Button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="-translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-1 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute top-[4.5rem] left-0 z-30 w-72 rounded-2xl border border-black/8 bg-white p-3 text-slate-950 shadow-[0_18px_50px_rgba(0,0,0,0.16)] dark:border-white/10 dark:bg-[#242426] dark:text-white"
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="t('Previous month')"
                        @click="previousMonth"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>

                    <div class="text-sm font-semibold capitalize">
                        {{ monthLabel }}
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="t('Next month')"
                        @click="nextMonth"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <div
                        v-for="day in weekDays"
                        :key="day"
                        class="py-1 text-[11px] font-semibold text-muted-foreground"
                    >
                        {{ day }}
                    </div>

                    <button
                        v-for="day in calendarDays"
                        :key="day.iso"
                        type="button"
                        class="flex aspect-square items-center justify-center rounded-lg text-sm transition-colors"
                        :class="
                            cn(
                                day.inMonth
                                    ? 'text-foreground'
                                    : 'text-muted-foreground/40',
                                day.today &&
                                    !day.selected &&
                                    'border border-[var(--color-tab)]/40',
                                day.selected
                                    ? 'bg-[#007aff] text-white hover:bg-[#007aff]'
                                    : 'hover:bg-black/5 dark:hover:bg-white/10',
                            )
                        "
                        @click="selectDate(day.iso)"
                    >
                        {{ day.day }}
                    </button>
                </div>

                <div class="mt-3 flex justify-end border-t border-border pt-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="gap-1"
                        @click="clearDate"
                    >
                        <X class="size-3.5" />
                        {{ t('Clear') }}
                    </Button>
                </div>
            </div>
        </Transition>
    </div>
</template>
