<script setup lang="ts">
import {
    CalendarDays,
    CircleUserRound,
    Clock3,
    Globe2,
    ListTree,
    Phone,
    Radio,
    UserCog,
} from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import InquiryAssigneeDialog from './InquiryAssigneeDialog.vue';
import InquiryCategoryAssignmentDialog from './InquiryCategoryAssignmentDialog.vue';
import InquiryDetailMetaItem from './InquiryDetailMetaItem.vue';
import InquiryStatusBadge from './InquiryStatusBadge.vue';
import type { InquiryCategory, InquiryDetail } from './types';
import type { InquiryAssigneeOption } from './types';

type Props = {
    inquiry: InquiryDetail;
    categories: InquiryCategory[];
    systemUsers: InquiryAssigneeOption[];
    canAssign: boolean;
    canAssignExecutor: boolean;
};

defineProps<Props>();

const { t } = useTranslations();

function empty(value: string | null) {
    return value === null || value.trim() === '' ? t('Not specified') : value;
}
</script>

<template>
    <section
        class="border-y border-black/8 bg-[#f7f7f8] px-4 py-4 dark:border-white/10 dark:bg-[#1a1a1c]"
    >
        <div class="grid gap-x-7 gap-y-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="space-y-4">
                <div>
                    <div
                        class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                    >
                        <Radio class="size-3.5" />
                        <span>{{ t('Status') }}</span>
                    </div>
                    <InquiryStatusBadge
                        :status="inquiry.status"
                        appearance="text"
                    />
                </div>

                <InquiryDetailMetaItem
                    :icon="CalendarDays"
                    :label="t('Inquiry date')"
                    :value="inquiry.submittedAt"
                />

                <InquiryDetailMetaItem
                    :icon="Clock3"
                    :label="t('Review deadline')"
                    :value="inquiry.reviewDueDate"
                />
            </div>

            <div class="space-y-4">
                <div class="min-w-0">
                    <div
                        class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                    >
                        <ListTree class="size-3.5" />
                        <span>{{ t('Category') }}</span>
                    </div>
                    <div class="mt-1 flex min-w-0 items-center gap-1.5">
                        <div
                            class="truncate text-sm font-semibold text-foreground"
                        >
                            {{ inquiry.categoryName }}
                        </div>
                        <InquiryCategoryAssignmentDialog
                            :inquiry-number="inquiry.number"
                            :category-id="inquiry.categoryId"
                            :categories="categories"
                            :can-assign="canAssign"
                        />
                    </div>
                </div>

                <InquiryDetailMetaItem
                    :icon="Globe2"
                    :label="t('Source')"
                    :value="t(inquiry.source)"
                />
            </div>

            <div class="space-y-4">
                <InquiryDetailMetaItem
                    :icon="CircleUserRound"
                    :label="t('Applicant full name')"
                    :value="empty(inquiry.applicantName)"
                />

                <InquiryDetailMetaItem
                    :icon="Phone"
                    :label="t('Applicant phone')"
                    :value="empty(inquiry.applicantPhone)"
                />
            </div>

            <div class="space-y-4">
                <div class="min-w-0">
                    <div
                        class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                    >
                        <UserCog class="size-3.5" />
                        <span>{{ t('Executor') }}</span>
                    </div>
                    <div v-if="inquiry.assignee" class="mt-1 min-w-0">
                        <div class="flex min-w-0 items-center gap-1.5">
                            <div
                                class="truncate text-sm font-semibold text-foreground"
                            >
                                {{ inquiry.assignee.name }}
                            </div>
                            <InquiryAssigneeDialog
                                :inquiry-number="inquiry.number"
                                :assignee="inquiry.assignee"
                                :system-users="systemUsers"
                                :can-assign="canAssignExecutor"
                            />
                        </div>
                        <div
                            v-if="inquiry.assignee.role"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ inquiry.assignee.role }}
                        </div>
                    </div>
                    <div v-else class="mt-1">
                        <InquiryAssigneeDialog
                            :inquiry-number="inquiry.number"
                            :assignee="inquiry.assignee"
                            :system-users="systemUsers"
                            :can-assign="canAssignExecutor"
                        />
                        <span
                            v-if="!canAssignExecutor"
                            class="text-sm font-semibold text-foreground"
                        >
                            {{ t('Not assigned') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
