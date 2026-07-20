export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type LocaleOption = {
    code: string;
    label: string;
    uploaded: boolean;
};

export type LocalizationSettingsLocale = LocaleOption & {
    enabled: boolean;
    fallback: boolean;
};

export type LocalizationSettings = {
    fallback: string;
    locales: LocalizationSettingsLocale[];
};

export type InquirySettings = {
    numberPrefix: string;
    sequencePadding: number;
    aiScreeningEnabled: boolean;
    aiScreeningInstructions: string;
};

export type Localization = {
    current: string;
    available: LocaleOption[];
    messages: Record<string, string>;
};
