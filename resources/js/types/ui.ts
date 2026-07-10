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

export type Localization = {
    current: string;
    available: LocaleOption[];
    messages: Record<string, string>;
};
