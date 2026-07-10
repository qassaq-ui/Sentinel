export type InquiryCategory = {
    id: number;
    uuid: string;
    name_key: string;
    description_key: string;
    fallback_name: string;
    fallback_description: string | null;
    localized_name: string;
    localized_description: string | null;
    review_days: number;
    is_active: boolean;
    sort_order: number;
};

export type InquiryCategoryFormData = {
    fallback_name: string;
    fallback_description: string;
    review_days: number;
    is_active: boolean;
    sort_order: number;
};

export type InquiryOutcome = {
    id: number;
    code: string;
    name_key: string;
    description_key: string;
    fallback_name: string;
    fallback_description: string | null;
    localized_name: string;
    localized_description: string | null;
    ai_instruction: string;
    is_active: boolean;
    sort_order: number;
};

export type InquiryOutcomeFormData = {
    fallback_name: string;
    fallback_description: string;
    ai_instruction: string;
    is_active: boolean;
    sort_order: number;
};
