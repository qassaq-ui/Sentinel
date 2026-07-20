<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div aria-hidden="true">
        <div class="mb-4 flex items-center justify-between gap-4">
            <Skeleton
                class="h-6 w-52 bg-muted-foreground/15 dark:bg-muted-foreground/20"
            />
            <Skeleton
                class="h-4 w-24 bg-muted-foreground/15 dark:bg-muted-foreground/20"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
            <div
                class="flex min-h-64 flex-col overflow-hidden rounded-lg border border-border bg-background"
            >
                <div class="border-b border-border px-4 py-3">
                    <Skeleton
                        class="h-4 w-20 bg-muted-foreground/15 dark:bg-muted-foreground/20"
                    />
                </div>

                <div class="space-y-2 p-3">
                    <div
                        v-for="role in 6"
                        :key="role"
                        class="flex min-h-14 items-center gap-3 rounded-md border border-border px-3 py-3"
                    >
                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <Skeleton
                                class="h-4 bg-muted-foreground/15 dark:bg-muted-foreground/20"
                                :class="role % 2 === 0 ? 'w-28' : 'w-36'"
                            />
                            <Skeleton
                                v-if="role % 2 !== 0"
                                class="h-3 w-48 max-w-full bg-muted-foreground/15 dark:bg-muted-foreground/20"
                            />
                        </div>
                        <Skeleton
                            v-if="role > 1"
                            class="size-7 shrink-0 bg-muted-foreground/15 dark:bg-muted-foreground/20"
                        />
                    </div>
                </div>
            </div>

            <div
                class="flex min-h-64 min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-background"
            >
                <div class="border-b border-border px-4 py-3">
                    <Skeleton
                        class="h-4 w-28 bg-muted-foreground/15 dark:bg-muted-foreground/20"
                    />
                </div>

                <div
                    v-for="group in 3"
                    :key="group"
                    class="border-b border-border"
                >
                    <div class="border-b border-border bg-muted/30 px-4 py-2.5">
                        <Skeleton
                            class="h-4 bg-muted-foreground/15 dark:bg-muted-foreground/20"
                            :class="group === 2 ? 'w-36' : 'w-28'"
                        />
                    </div>

                    <div
                        v-for="permission in 2"
                        :key="permission"
                        class="flex min-h-16 items-center justify-between gap-4 px-4 py-3"
                    >
                        <Skeleton
                            class="h-4 bg-muted-foreground/15 dark:bg-muted-foreground/20"
                            :class="permission === 1 ? 'w-44' : 'w-32'"
                        />
                        <Skeleton
                            class="h-5 w-9 shrink-0 rounded-full bg-muted-foreground/15 dark:bg-muted-foreground/20"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
