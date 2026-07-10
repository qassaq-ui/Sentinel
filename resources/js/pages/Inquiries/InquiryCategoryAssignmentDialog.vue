<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { updateCategory } from '@/actions/App/Http/Controllers/InquiriesController';
import { useForm } from '@inertiajs/vue3';
import { Check, Pencil, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import type { InquiryCategory } from './types';

type Props = {
    inquiryNumber: string;
    categoryId: number;
    categories: InquiryCategory[];
    canAssign: boolean;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const isOpen = ref(false);
const search = ref('');

const form = useForm<{ inquiry_category_id: number }>({
    inquiry_category_id: props.categoryId,
});

const filteredCategories = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (query === '') {
        return props.categories;
    }

    return props.categories.filter((category) => {
        return `${category.name} ${category.reviewDays}`.toLowerCase().includes(query);
    });
});

watch(
    () => props.categoryId,
    (categoryId) => {
        form.inquiry_category_id = categoryId;
    },
);

function selectCategory(category: InquiryCategory) {
    form.inquiry_category_id = category.id;
}

function submit() {
    form.patch(updateCategory(props.inquiryNumber).url, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
    });
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogTrigger v-if="canAssign" as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-6 shrink-0 text-[var(--color-tab)] hover:bg-[var(--color-tab)] hover:text-white"
                :aria-label="t('Change category')"
            >
                <Pencil class="size-3" />
            </Button>
        </DialogTrigger>

        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-xl">
            <DialogHeader class="border-b border-border px-5 py-4">
                <DialogTitle class="text-base font-semibold">
                    {{ t('Choose category') }}
                </DialogTitle>
                <DialogDescription class="text-xs">
                    {{ t('Changing the category will update the review deadline.') }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit">
                <div class="space-y-3 px-5 py-4">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            class="h-10 pl-9"
                            :placeholder="t('Search category')"
                        />
                    </div>

                    <div
                        v-if="categories.length"
                        class="overflow-hidden rounded-lg border border-border"
                    >
                        <div
                            class="grid grid-cols-[minmax(0,1fr)_110px_36px] gap-3 border-b border-border bg-muted/40 px-3 py-2 text-xs font-semibold text-muted-foreground"
                        >
                            <span>{{ t('Category') }}</span>
                            <span>{{ t('Review period') }}</span>
                            <span class="text-right">{{ t('Selected') }}</span>
                        </div>

                        <div class="max-h-72 overflow-y-auto">
                            <button
                                v-for="category in filteredCategories"
                                :key="category.id"
                                type="button"
                                class="grid w-full grid-cols-[minmax(0,1fr)_110px_36px] items-center gap-3 border-b border-border px-3 py-2.5 text-left transition-colors last:border-b-0 hover:bg-[var(--color-tab)]/10"
                                :class="
                                    form.inquiry_category_id === category.id
                                        ? 'bg-[var(--color-tab)]/10'
                                        : 'bg-background'
                                "
                                @click="selectCategory(category)"
                            >
                                <span class="truncate text-sm font-semibold text-foreground">
                                    {{ category.name }}
                                </span>
                                <span class="truncate text-xs font-medium text-muted-foreground">
                                    {{ t(':count days short', { count: category.reviewDays }) }}
                                </span>
                                <span
                                    class="ml-auto flex size-5 items-center justify-center rounded-sm border border-border"
                                    :class="
                                        form.inquiry_category_id === category.id
                                            ? 'border-[var(--color-tab)] bg-[var(--color-tab)] text-white'
                                            : 'bg-background'
                                    "
                                >
                                    <Check
                                        v-if="form.inquiry_category_id === category.id"
                                        class="size-3.5"
                                    />
                                </span>
                            </button>

                            <div
                                v-if="filteredCategories.length === 0"
                                class="px-3 py-8 text-center text-sm text-muted-foreground"
                            >
                                {{ t('No categories found') }}
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.inquiry_category_id" />
                </div>

                <DialogFooter class="border-t border-border bg-muted/30 px-5 py-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        class="bg-[var(--color-tab)] text-white hover:bg-[var(--color-tab)]/90"
                        :disabled="form.processing"
                    >
                        {{ t('Change category') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
