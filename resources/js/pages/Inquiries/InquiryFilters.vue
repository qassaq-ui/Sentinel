<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import { computed } from 'vue';
import InquiryDateFilter from './InquiryDateFilter.vue';
import InquiryFilterSelect from './InquiryFilterSelect.vue';
import type { InquiryCategory, InquiryFilterOption } from './types';

type Props = {
    age: string;
    status: string;
    category: string;
    categories: InquiryCategory[];
    submittedDate: string;
    sort: string;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:age': [value: string];
    'update:status': [value: string];
    'update:category': [value: string];
    'update:submittedDate': [value: string];
    'update:sort': [value: string];
}>();

const { t } = useTranslations();

const ageOptions: InquiryFilterOption[] = [
    { value: 'all', label: t('All') },
    { value: 'new', label: t('New first') },
    { value: 'old', label: t('Old first') },
];

const statusOptions: InquiryFilterOption[] = [
    { value: 'all', label: t('All statuses') },
    { value: 'new', label: t('New') },
    { value: 'in_progress', label: t('In progress') },
    { value: 'suspended', label: t('Suspended') },
    { value: 'completed', label: t('Completed') },
    { value: 'rejected', label: t('Rejected') },
    { value: 'withdrawn', label: t('Withdrawn by applicant') },
];

const categoryOptions = computed<InquiryFilterOption[]>(() => [
    { value: 'all', label: t('All categories') },
    ...props.categories.map((category) => ({
        value: String(category.id),
        label: category.name,
    })),
]);

const sortOptions: InquiryFilterOption[] = [
    { value: 'newest', label: t('Newest first') },
    { value: 'oldest', label: t('Oldest first') },
    { value: 'days', label: t('Fewest days left') },
];
</script>

<template>
    <div class="rounded-lg border border-border bg-background p-3">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <InquiryFilterSelect
                :label="t('New / old')"
                :model-value="age"
                :options="ageOptions"
                @update:model-value="emit('update:age', $event)"
            />
            <InquiryFilterSelect
                :label="t('Inquiry status')"
                :model-value="status"
                :options="statusOptions"
                @update:model-value="emit('update:status', $event)"
            />
            <InquiryFilterSelect
                :label="t('Category')"
                :model-value="category"
                :options="categoryOptions"
                @update:model-value="emit('update:category', $event)"
            />
            <InquiryDateFilter
                :label="t('Inquiry date')"
                :model-value="submittedDate"
                @update:model-value="emit('update:submittedDate', $event)"
            />
            <InquiryFilterSelect
                :label="t('Sorting')"
                :model-value="sort"
                :options="sortOptions"
                @update:model-value="emit('update:sort', $event)"
            />
        </div>
    </div>
</template>
