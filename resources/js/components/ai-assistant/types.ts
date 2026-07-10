import type { Component } from 'vue';

export type AIAssistantJob =
    | 'translate_text'
    | 'analyze_inquiry'
    | 'recommend_assignee'
    | 'recommend_response';

export type AIAssistantJobDefinition = {
    key: AIAssistantJob;
    label: string;
    description: string;
    icon: Component;
};

export type AIAssistantAssigneeRecommendation = {
    user_id: number;
    name: string;
    email: string;
    role: string | null;
    active_assignments_count: number;
    score: number;
    reason: string;
};

export type AIAssistantContext = {
    locale: string;
    page: string;
    canAssignInquiries: boolean;
    inquiry?: {
        number: string;
        title: string;
        description: string | null;
        status: string;
        categoryName: string;
        submittedAt: string;
        reviewDueDate: string;
        applicantName: string | null;
        applicantPhone: string | null;
        assigneeName: string | null;
        attachmentsCount: number;
    };
};
